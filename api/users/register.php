<?php
require_once '../../includes/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$role = isset($data['role']) ? $data['role'] : '';
$nom = isset($data['nom']) ? trim($data['nom']) : '';
$email = isset($data['email']) ? trim($data['email']) : '';
$password = isset($data['password']) ? $data['password'] : '';

if (!$role || !$nom || !$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Champs obligatoires manquants']);
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();

    if ($role === 'franchise') {
        // Vérifier si l'email existe déjà dans users
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email déjà utilisé']);
            exit;
        }
        // Insérer dans users
        $stmt = $conn->prepare("INSERT INTO users (nom, email, mot_de_passe, role) VALUES (?, ?, ?, 'franchise')");
        $stmt->execute([$nom, $email, password_hash($password, PASSWORD_DEFAULT)]);
        echo json_encode(['success' => true, 'message' => 'Inscription franchisé réussie']);
    } elseif ($role === 'client') {
        // Vérifier si l'email existe déjà dans clients
        $stmt = $conn->prepare("SELECT id FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email déjà utilisé']);
            exit;
        }
        // Insérer dans clients
        $stmt = $conn->prepare("INSERT INTO clients (nom, email, mot_de_passe) VALUES (?, ?, ?)");
        $stmt->execute([$nom, $email, password_hash($password, PASSWORD_DEFAULT)]);
        echo json_encode(['success' => true, 'message' => 'Inscription client réussie']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Rôle invalide']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}