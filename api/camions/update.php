<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID manquant']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        
        $stmt = $conn->prepare("
            UPDATE camions 
            SET nom_camion = ?, etat = ?, emplacement = ?, menu = ?, jours = ?, date_livraison = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['nom_camion'] ?? '',
            $data['etat'] ?? 'en_preparation',
            $data['emplacement'] ?? '',
            $data['menu'] ?? '',
            $data['jours'] ?? '',
            $data['date_livraison'] ?? null,
            $data['id']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Camion mis à jour avec succès']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>