<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['id']) || empty($data['nom']) || empty($data['adresse']) || empty($data['ville']) || empty($data['code_postal'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        
        // Mise à jour avec coordonnées si fournies
        $latitude = isset($data['latitude']) ? floatval($data['latitude']) : null;
        $longitude = isset($data['longitude']) ? floatval($data['longitude']) : null;
        
        $stmt = $conn->prepare("UPDATE entrepots SET nom = ?, adresse = ?, ville = ?, code_postal = ?, latitude = ?, longitude = ? WHERE id = ?");
        $stmt->execute([$data['nom'], $data['adresse'], $data['ville'], $data['code_postal'], $latitude, $longitude, $data['id']]);
        
        echo json_encode(['success' => true, 'message' => 'Entrepôt mis à jour avec succès']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>