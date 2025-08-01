<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Récupérer les comptes en attente
    $conn = Database::getInstance()->getConnection();
    $stmt = $conn->query("SELECT id, nom, prenom, email, telephone, lieu_installation, motivation, date_inscription FROM users WHERE role='franchise' AND statut='en_attente' ORDER BY date_inscription DESC");
    echo json_encode($stmt->fetchAll());
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Valider ou refuser un compte
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['user_id'];
    $action = $data['action']; // 'valider' ou 'refuser'
    
    $statut = ($action === 'valider') ? 'valide' : 'refuse';
    
    $conn = Database::getInstance()->getConnection();
    $stmt = $conn->prepare("UPDATE users SET statut = ? WHERE id = ?");
    $stmt->execute([$statut, $userId]);
    
    echo json_encode(['success' => true, 'message' => 'Statut mis à jour']);
}
?>