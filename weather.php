<?php

$APIkey = "YOURAPIKEY";

session_start();
if (!isset($_SESSION['city'])) {

} else {
    $city = $_SESSION['city'];

    $latitude = 0;
    $longitude = 0;

    include 'config.php';
    try {
        $db = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM city WHERE city_name = :city_name";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(":city_name", $city, PDO::PARAM_STR);

        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        echo $e->getMessage();
    }

    $latitude = $result[0]['latitude'];
    $longitude = $result[0]['longitude'];



    $url = "https://api.openweathermap.org/data/2.5/forecast?lat=$latitude&lon=$longitude&units=metric&appid=$APIkey";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    //$cityName = $data['city']['name'] ?? '';
    $cityName = $result[0]['city_name'];
    $latitude = $data['city']['coord']['lat'] ?? '';
    $longitude = $data['city']['coord']['lon'] ?? '';

    try {
        $updateSql = "UPDATE city SET latitude = :latitude, longitude = :longitude WHERE id = :id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->bindParam(":latitude", $latitude, PDO::PARAM_STR);
        $updateStmt->bindParam(":longitude", $longitude, PDO::PARAM_STR);
        $updateStmt->bindParam(":id", $result[0]['id'], PDO::PARAM_INT); // Assuming 'id' is the primary key column
        $updateStmt->execute();
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

    $forecast = array();
    for ($i = 0; $i < count($data['list']); $i++) {
        $forecastDate = date('Y-m-d', $data['list'][$i]['dt']);
        if (!isset($forecast[$forecastDate])) {
            $forecast[$forecastDate] = array(
                'min_temp' => $data['list'][$i]['main']['temp_min'],
                'max_temp' => $data['list'][$i]['main']['temp_max'],
                'description' => $data['list'][$i]['weather'][0]['description']
            );
        } else {
            $forecast[$forecastDate]['min_temp'] = min($forecast[$forecastDate]['min_temp'], $data['list'][$i]['main']['temp_min']);
            $forecast[$forecastDate]['max_temp'] = max($forecast[$forecastDate]['max_temp'], $data['list'][$i]['main']['temp_max']);
            $forecast[$forecastDate]['description'] = $data['list'][$i]['weather'][0]['description'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sk">
<head>
    <title>Weather</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        .card-body {background-color: lightgray;}

    </style>
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
<div class="container mt-3">
    <div id="middle" class="text-center align-items-center">
        <?php if (!isset($_SESSION['city'])): ?>
            <p>Please enter a city to display the weather.</p>
        <?php else: ?>
            <h1 class="text-center"><?php echo $cityName; ?></h1>
            <h2 class="text-center">Latitude: <?php echo $latitude; ?></h2>
            <h2 class="text-center">Longitude: <?php echo $longitude; ?></h2>
            <h2 class="text-center">Country: <?php echo $result[0]["country"]; ?></h2>
            <h2 class="text-center">Capital: <?php echo $result[0]["capital"]; ?></h2>
            <div class="row">
                <?php foreach ($forecast as $date => $data): ?>
                    <?php if (strtotime($date) < strtotime("+4 days")): ?>
                        <div class="col-md-4">
                            <div class="card mb-4 box-shadow">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo date('l', strtotime($date)); ?></h5>
                                    <p class="card-text"><?php echo $data['description']; ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Average temperature for the day: <?php echo round(($data['min_temp'] + $data['max_temp']) / 2); ?>°C</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>