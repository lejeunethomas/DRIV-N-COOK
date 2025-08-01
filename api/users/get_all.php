<?php
require_once '../../includes/db.php';
header('Content-Type: application/json');

$conn = Database::getInstance()->getConnection();
$stmt = $conn->query("
    SELECT id, nom, prenom, email, telephone, lieu_installation, statut, date_inscription 
    FROM users 
    WHERE role='franchise' 
    ORDER BY date_inscription DESC
");

echo json_encode($stmt->fetchAll());
?>