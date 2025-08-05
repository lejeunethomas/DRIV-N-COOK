<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['nom']) || empty($data['prenom'])) {
        echo json_encode(['success' => false, 'message' => 'Nom et prénom requis']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        
        // Déterminer la table selon le rôle
        if ($_SESSION['role'] === 'client') {
            $table = 'clients';
        } else {
            $table = 'users';
        }
        
        $stmt = $conn->prepare("
            UPDATE {$table} 
            SET nom = ?, prenom = ?, telephone = ? 
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['nom'],
            $data['prenom'],
            isset($data['telephone']) ? $data['telephone'] : null,
            $_SESSION['user_id']
        ]);
        
        // Mettre à jour la session
        $_SESSION['nom'] = $data['nom'];
        
        echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>