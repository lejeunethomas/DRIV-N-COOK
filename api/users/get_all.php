<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

try {
    $conn = Database::getInstance()->getConnection();
    $stmt = $conn->query("
        SELECT id, nom, prenom, email, telephone, lieu_installation, statut, date_inscription 
        FROM users 
        WHERE role='franchise' 
        ORDER BY date_inscription DESC
    ");

    echo json_encode($stmt->fetchAll());
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>