<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID manquant']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        
        // Vérifier si l'entrepôt a des stocks
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM stocks WHERE entrepot_id = ?");
        $stmt->execute([$data['id']]);
        $usage = $stmt->fetch();
        
        if ($usage['count'] > 0) {
            // Marquer comme inactif au lieu de supprimer
            $stmt = $conn->prepare("UPDATE entrepots SET actif = 0 WHERE id = ?");
            $stmt->execute([$data['id']]);
            echo json_encode(['success' => true, 'message' => 'Entrepôt désactivé avec succès (il contenait des stocks)']);
        } else {
            // Supprimer complètement
            $stmt = $conn->prepare("DELETE FROM entrepots WHERE id = ?");
            $stmt->execute([$data['id']]);
            echo json_encode(['success' => true, 'message' => 'Entrepôt supprimé avec succès']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>