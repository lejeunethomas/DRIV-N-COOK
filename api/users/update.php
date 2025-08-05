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
            UPDATE users 
            SET nom = ?, prenom = ?, email = ?, telephone = ?, lieu_installation = ?, statut = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['nom'],
            $data['prenom'], 
            $data['email'],
            $data['telephone'] ?? null,
            $data['lieu_installation'] ?? null,
            $data['statut'] ?? 'en_attente',
            $data['id']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour avec succès']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>