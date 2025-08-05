<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();
    
    // Déterminer la table selon le rôle
    if ($_SESSION['role'] === 'client') {
        $stmt = $conn->prepare("
            SELECT id, nom, prenom, email, telephone, 
                   created_at as date_inscription 
            FROM clients 
            WHERE id = ?
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT id, nom, prenom, email, telephone, lieu_installation, 
                   motivation, statut, date_inscription 
            FROM users 
            WHERE id = ?
        ");
    }
    
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($profile) {
        echo json_encode(['success' => true, 'profile' => $profile]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Profil non trouvé']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>