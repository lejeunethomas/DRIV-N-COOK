<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    error_log("Aucune donnée reçue dans register.php");
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$role = isset($data['role']) ? $data['role'] : '';
$nom = isset($data['nom']) ? trim($data['nom']) : '';
$prenom = isset($data['prenom']) ? trim($data['prenom']) : '';
$email = isset($data['email']) ? trim($data['email']) : '';
$password = isset($data['password']) ? $data['password'] : '';

if ($role === 'franchise') {
    $telephone = isset($data['telephone']) ? trim($data['telephone']) : '';
    $lieu = isset($data['lieu']) ? trim($data['lieu']) : '';
    $motivation = isset($data['motivation']) ? trim($data['motivation']) : '';
    
    if (!$role || !$nom || !$prenom || !$email || !$password || !$telephone || !$lieu || !$motivation) {
        echo json_encode(['success' => false, 'message' => 'Tous les champs sont obligatoires pour l\'inscription franchisé']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();

        // Vérifier si l'email existe déjà dans users
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email déjà utilisé']);
            exit;
        }
        
        // Insérer dans users 
        $stmt = $conn->prepare("INSERT INTO users (nom, prenom, email, mot_de_passe, telephone, lieu_installation, motivation, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'franchise')");
        $stmt->execute([$nom, $prenom, $email, password_hash($password, PASSWORD_DEFAULT), $telephone, $lieu, $motivation]);
        echo json_encode(['success' => true, 'message' => 'Inscription franchisé réussie']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} elseif ($role === 'client') {
    if (!$role || !$nom || !$prenom || !$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'Champs obligatoires manquants']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();

        // Vérifier si l'email existe déjà dans clients
        $stmt = $conn->prepare("SELECT id FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email déjà utilisé']);
            exit;
        }
        
        // Insérer dans clients
        $stmt = $conn->prepare("INSERT INTO clients (nom, prenom, email, mot_de_passe) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nom, $prenom, $email, password_hash($password, PASSWORD_DEFAULT)]);
        echo json_encode(['success' => true, 'message' => 'Inscription client réussie']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Rôle invalide']);
}