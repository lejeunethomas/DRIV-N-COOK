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
        
        // Vérifier les dépendances
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM camions WHERE user_id = ?");
        $stmt->execute([$data['id']]);
        $camions = $stmt->fetch();
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM commandes WHERE user_id = ?");
        $stmt->execute([$data['id']]);
        $commandes = $stmt->fetch();
        
        if ($camions['count'] > 0 || $commandes['count'] > 0) {
            // Désactiver au lieu de supprimer
            $stmt = $conn->prepare("UPDATE users SET statut = 'desactive' WHERE id = ?");
            $stmt->execute([$data['id']]);
            echo json_encode(['success' => true, 'message' => 'Utilisateur désactivé (il avait des camions/commandes)']);
        } else {
            // Supprimer complètement
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$data['id']]);
            echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>