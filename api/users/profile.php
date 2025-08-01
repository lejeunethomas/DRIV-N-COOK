<?php
require_once '../../includes/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Récupérer les informations du profil
        $stmt = $conn->prepare("SELECT nom, prenom, email, telephone, lieu_installation, motivation, numero_permis, date_inscription FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo json_encode($user);
        } else {
            echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Mettre à jour le profil
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Données manquantes']);
            exit;
        }
        
        $nom = isset($data['nom']) ? trim($data['nom']) : '';
        $prenom = isset($data['prenom']) ? trim($data['prenom']) : '';
        $telephone = isset($data['telephone']) ? trim($data['telephone']) : '';
        $lieu_installation = isset($data['lieu_installation']) ? trim($data['lieu_installation']) : '';
        $motivation = isset($data['motivation']) ? trim($data['motivation']) : '';
        $numero_permis = isset($data['numero_permis']) ? trim($data['numero_permis']) : '';
        
        if (!$nom || !$prenom || !$telephone || !$lieu_installation || !$motivation) {
            echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis']);
            exit;
        }
        
        // Vérifier si le numéro de permis n'est pas déjà utilisé par un autre utilisateur
        if ($numero_permis) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE numero_permis = ? AND id != ?");
            $stmt->execute([$numero_permis, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Ce numéro de permis est déjà utilisé par un autre utilisateur']);
                exit;
            }
        }
        
        // Mettre à jour les informations
        $stmt = $conn->prepare("UPDATE users SET nom = ?, prenom = ?, telephone = ?, lieu_installation = ?, motivation = ?, numero_permis = ? WHERE id = ?");
        $stmt->execute([$nom, $prenom, $telephone, $lieu_installation, $motivation, $numero_permis, $_SESSION['user_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>