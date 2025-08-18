<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];
$isAdmin = (isset($_SESSION['role']) ? $_SESSION['role'] : '') === 'admin';

switch ($method) {
    case 'GET':
        handleGetProfile($userId, $isAdmin);
        break;
        
    case 'PUT':
        handleUpdateProfile($userId, $isAdmin);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}

function handleGetProfile($userId, $isAdmin) {
    try {
        $conn = Database::getInstance()->getConnection();
        
        // Profil demandé (admin peut voir autres profils)
        $targetUserId = $isAdmin && isset($_GET['user_id']) ? $_GET['user_id'] : $userId;
        
        $stmt = $conn->prepare("
            SELECT id, nom, prenom, email, telephone, numero_permis, lieu_installation, 
                   motivation,
                   role, statut, date_inscription 
            FROM users WHERE id = ?
        ");
        $stmt->execute([$targetUserId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
            return;
        }
        
        echo json_encode(['success' => true, 'user' => $user]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
}

function handleUpdateProfile($userId, $isAdmin) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        
        // Utilisateur cible (admin peut modifier autres profils)
        $targetUserId = $isAdmin && isset($data['user_id']) ? $data['user_id'] : $userId;
        
        $stmt = $conn->prepare("
            UPDATE users 
            SET nom = ?, prenom = ?, telephone = ?, numero_permis = ?, lieu_installation = ?, motivation = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            isset($data['nom']) ? $data['nom'] : '',
            isset($data['prenom']) ? $data['prenom'] : '',
            isset($data['telephone']) ? $data['telephone'] : null,
            isset($data['numero_permis']) ? $data['numero_permis'] : null,
            isset($data['lieu_installation']) ? $data['lieu_installation'] : '',
            isset($data['motivation']) ? $data['motivation'] : '',
            $targetUserId
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
}
?>