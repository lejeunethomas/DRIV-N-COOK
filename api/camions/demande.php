<?php
// filepath: /workspaces/DRIV-N-COOK/api/camions/demande.php
require_once '../../includes/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['emplacement']) || empty($data['menu']) || empty($data['jours'])) {
    echo json_encode(['success' => false, 'message' => 'Champs manquants']);
    exit;
}

$conn = Database::getInstance()->getConnection();
$stmt = $conn->prepare("INSERT INTO demandes_camion (user_id, emplacement, menu, jours, etat) VALUES (?, ?, ?, ?, 'en attente')");
$stmt->execute([
    $_SESSION['user_id'],
    $data['emplacement'],
    $data['menu'],
    implode(',', $data['jours'])
]);
echo json_encode(['success' => true, 'message' => 'Votre demande a bien été enregistrée.']);