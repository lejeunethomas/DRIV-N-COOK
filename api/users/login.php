<?php
// Gestion des erreurs pour PHP 8.3
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers CORS pour éviter les problèmes
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    require_once '../../includes/db.php';
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['email']) || !isset($data['password'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }
    
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $password = $data['password'];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email invalide']);
        exit;
    }
    
    // Démarrer la session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $conn = Database::getInstance()->getConnection();
    
    // Vérifier d'abord dans clients (admin + clients)
    $stmt = $conn->prepare("SELECT id, nom, mot_de_passe, role FROM clients WHERE email = ?");
    $stmt->execute([$email]);
    $client = $stmt->fetch();
    
    if ($client && password_verify($password, $client['mot_de_passe'])) {
        $_SESSION['user_id'] = $client['id'];
        $_SESSION['role'] = $client['role'];
        $_SESSION['nom'] = $client['nom'];
        echo json_encode(['success' => true, 'role' => $client['role'], 'message' => 'Connexion réussie']);
        exit;
    }
    
    // Vérifier ensuite dans users (franchisés)
    $stmt = $conn->prepare("SELECT id, nom, mot_de_passe, role, statut FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['mot_de_passe'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['statut'] = $user['statut']; 
    
        $message = 'Connexion réussie';
        if ($user['statut'] === 'en_attente') {
            $message = 'Connexion réussie. Votre compte est en cours de validation.';
        }
        
        echo json_encode([
            'success' => true, 
            'role' => $user['role'], 
            'statut' => $user['statut'], 
            'message' => $message
        ]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>