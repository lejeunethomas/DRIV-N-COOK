<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/db.php';
session_start();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Champs obligatoires manquants']);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

$conn = Database::getInstance()->getConnection();

// Vérifier franchisé (users)
$stmt = $conn->prepare("SELECT id, nom, mot_de_passe, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['mot_de_passe'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['nom'] = $user['nom'];
    echo json_encode(['success' => true, 'role' => $user['role'], 'message' => 'Connexion réussie']);
    exit;
}

// Vérifier client
$stmt = $conn->prepare("SELECT id, nom, mot_de_passe FROM clients WHERE email = ?");
$stmt->execute([$email]);
$client = $stmt->fetch();

if ($client && password_verify($password, $client['mot_de_passe'])) {
    $_SESSION['user_id'] = $client['id'];
    $_SESSION['role'] = 'client';
    $_SESSION['nom'] = $client['nom'];
    echo json_encode(['success' => true, 'role' => 'client', 'message' => 'Connexion réussie']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect']);