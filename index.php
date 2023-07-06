<?php
require_once 'config.php';


if(isset($_POST['text'])) {
    session_start();

    $text = $_POST['text'];
    $url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($text);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36');
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch);
        exit();
    }
    $lat = 0;
    $lon = 0;
    $results = json_decode($response, true);
    if (isset($results[0]['lat']) && isset($results[0]['lon'])) {
        $lat = $results[0]['lat'];
        $lon = $results[0]['lon'];
        $display_name = explode(",", $results[0]['display_name']);
        $city = trim($display_name[0]);
        $_SESSION['city'] = $city;
    } else {
    }
    curl_close($ch);

    if($lat != 0 || $lon != 0){
        $url = "https://nominatim.openstreetmap.org/reverse?lat=".$lat."&lon=".$lon."&format=jsonv2";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'User-Agent: Your User-Agent',
            'Referer: Your Referer'
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response, true);
        $country = $json['address']['country'];
        $country_code = $json['address']['country_code'];
        $response = file_get_contents("https://restcountries.com/v3/alpha/{$country_code}");
        $data = json_decode($response, true);
        $capital = $data[0]['capital'][0];
        $userId = putUserInDb();
        putCityInDb($city, $country, $capital, $lat, $lon, $userId, $lat, $lon, $country_code,);
    }
}
function putCityInDb(mixed $city, mixed $country, mixed $capital, mixed $lat, mixed $lon, mixed $userId, mixed $latitude, mixed $longitude, mixed $code)
{
    $apiKey = "YOURAPIKEY";

    $url = "http://api.timezonedb.com/v2.1/get-time-zone?key=$apiKey&format=json&by=position&lat=$latitude&lng=$longitude";

    $response = file_get_contents($url);
    $data = json_decode($response, true);
    $timezone = $data['zoneName'];
    $date = new DateTime('now', new DateTimeZone($timezone));
    $time = $date->format('Y-m-d H:i:s');

    include 'config.php';

    try {
        $db = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "INSERT INTO city (city_name, latitude, longitude, country, capital, user_id, visit_time, code) VALUES (:city_name, :latitude, :longitude, :country, :capital, :user_id, :visit_time, :code)";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(":city_name", $city, PDO::PARAM_STR);
        $stmt->bindParam(":latitude", $lat, PDO::PARAM_STR);
        $stmt->bindParam(":longitude", $lon, PDO::PARAM_STR);
        $stmt->bindParam(":country", $country, PDO::PARAM_STR);
        $stmt->bindParam(":capital", $capital, PDO::PARAM_STR);
        $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
        $stmt->bindParam(":visit_time", $time, PDO::PARAM_STR);
        $stmt->bindParam(":code", $code, PDO::PARAM_STR);

        $stmt->execute();
    } catch(PDOException $e) {
        echo $e->getMessage();
    }
}
function putUserInDb() {
    $ip = $_SERVER['REMOTE_ADDR'];

    include 'config.php';
    try {
        $db = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if user with the same IP already exists
        $checkSql = "SELECT id FROM user WHERE ip = :ip";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(":ip", $ip, PDO::PARAM_STR);
        $checkStmt->execute();
        $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            // User already exists, return the existing user's ID
            return $existingUser['id'];
        } else {
            // Insert new user and return the last inserted ID
            $insertSql = "INSERT INTO user (ip) VALUES (:ip)";
            $insertStmt = $db->prepare($insertSql);
            $insertStmt->bindParam(":ip", $ip, PDO::PARAM_STR);
            $insertStmt->execute();
            return $db->lastInsertId();
        }
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="sk">
<head>
    <title>City input</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link href="https://unpkg.com/tabulator-tables@5.4.4/dist/css/tabulator.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class=" navbar-collapse justify-content-md-center" id="navbarsExample08">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" href="./index.php">Search city</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="./weather.php">Weather</a>
            </li>
            <li class="nav-iteme">
                <a class="nav-link" href="./statistics.php">Statistics</a>
            </li>
        </ul>
    </div>
</nav>
<div class="container">
        <div id="middle" class="text-center align-items-center">
            <h1>City form</h1>
            <br>
            <form method="POST">
                <label for="text" class="form-label">Insert city name:</label><br>
                <input type="text" class="form-control" id="text" name="text"><br>
                <input class="btn-primary" type="submit" value="Submit">
            </form>
        </div>
</div>
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tabulator/4.9.3/js/tabulator.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
<script type="text/javascript" src="https://unpkg.com/tabulator-tables@5.4.4/dist/js/tabulator.min.js"></script>
</body>
</html>