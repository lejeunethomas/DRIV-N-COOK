<?php
require_once '../../includes/db.php';
header('Content-Type: application/json');
$conn = Database::getInstance()->getConnection();
$stmt = $conn->query("SELECT id, nom, email, date_inscription FROM users WHERE role='franchise'");
echo json_encode($stmt->fetchAll());