<?php
// filepath: api/users/update_profile.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('client');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();
    $clientId = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        UPDATE clients
        SET nom = ?, prenom = ?, telephone = ?
        WHERE id = ?
    ");
    $stmt->execute([
        isset($data['nom']) ? $data['nom'] : '',
        isset($data['prenom']) ? $data['prenom'] : '',
        isset($data['telephone']) ? $data['telephone'] : null,
        $clientId
    ]);

    echo json_encode(['success' => true, 'message' => 'Profil client mis à jour avec succès']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>