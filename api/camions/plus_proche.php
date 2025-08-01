<?php
require_once '../../includes/db.php';
header('Content-Type: application/json');

$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;

if ($lat === null || $lng === null) {
    echo json_encode([]);
    exit;
}

$conn = Database::getInstance()->getConnection();
$stmt = $conn->query("SELECT id, nom_camion, latitude, longitude FROM camions WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
$camions = $stmt->fetchAll();

foreach ($camions as &$camion) {
    // Formule de Haversine
    $theta = $lng - $camion['longitude'];
    $dist = sin(deg2rad($lat)) * sin(deg2rad($camion['latitude'])) +  cos(deg2rad($lat)) * cos(deg2rad($camion['latitude'])) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $km = $dist * 111.13384;
    $camion['distance_km'] = round($km, 2);
}
usort($camions, function($a, $b) {
    if ($a['distance_km'] == $b['distance_km']) {
        return 0;
    }
    return ($a['distance_km'] < $b['distance_km']) ? -1 : 1;
});

echo json_encode($camions);