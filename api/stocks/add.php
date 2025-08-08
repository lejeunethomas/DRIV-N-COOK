<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['entrepot_id']) || empty($data['produit_id'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        
        // Vérifier si le stock existe déjà
        $stmt = $conn->prepare("SELECT id FROM stocks WHERE entrepot_id = ? AND produit_id = ?");
        $stmt->execute([$data['entrepot_id'], $data['produit_id']]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ce stock existe déjà']);
            exit;
        }
        
        $stmt = $conn->prepare("
            INSERT INTO stocks (entrepot_id, produit_id, quantite, unite, seuil_alerte) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['entrepot_id'],
            $data['produit_id'],
            isset($data['quantite']) ? $data['quantite'] : 0,
            isset($data['unite']) ? $data['unite'] : 'unité',
            isset($data['seuil_alerte']) ? $data['seuil_alerte'] : 10
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Stock créé avec succès']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>