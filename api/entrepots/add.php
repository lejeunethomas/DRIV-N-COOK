<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_admin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['nom']) || empty($data['adresse']) || empty($data['ville']) || empty($data['code_postal'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        
        $latitude = null;
        $longitude = null;
        
        if (isset($data['latitude']) && isset($data['longitude'])) {
            $latitude = floatval($data['latitude']);
            $longitude = floatval($data['longitude']);
        }
        
        $stmt = $conn->prepare("
            INSERT INTO entrepots (nom, adresse, ville, code_postal, telephone, email, responsable, latitude, longitude, actif) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        $stmt->execute([
            $data['nom'],
            $data['adresse'],
            $data['ville'],
            $data['code_postal'],
            isset($data['telephone']) ? $data['telephone'] : null,
            isset($data['email']) ? $data['email'] : null,
            isset($data['responsable']) ? $data['responsable'] : null,
            $latitude,
            $longitude
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Entrepôt créé avec succès',
            'id' => $conn->lastInsertId()
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>