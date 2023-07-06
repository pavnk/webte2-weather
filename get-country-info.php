<?php
require_once('config.php');

try {
    $db = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $query = $db->prepare("SELECT city.id, city.user_id, city.city_name, city.latitude, city.longitude, city.country, city.capital, city.code, user.id, city.visit_time, user.ip
    FROM city
    JOIN user ON user.id = city.user_id
    WHERE city.country = ?");
    $query->execute([$_GET['name']]);
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
} catch(PDOException $e) {
    echo $e->getMessage();
}
header('Content-Type: application/json');
?>