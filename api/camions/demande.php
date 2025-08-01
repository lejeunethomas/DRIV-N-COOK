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
if (!$data || empty($data['nom_camion']) || empty($data['numero_permis']) || empty($data['emplacement']) || empty($data['menu']) || empty($data['jours'])) {
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont obligatoires']);
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();
    
    // Vérifier si l'utilisateur n'a pas déjà une demande en attente
    $stmt = $conn->prepare("SELECT id FROM demandes_camion WHERE user_id = ? AND etat = 'en attente'");
    $stmt->execute([$_SESSION['user_id']]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Vous avez déjà une demande en attente']);
        exit;
    }
    
    // Vérifier si le numéro de permis est déjà utilisé par un autre utilisateur
    $stmt = $conn->prepare("SELECT id FROM users WHERE numero_permis = ? AND id != ?");
    $stmt->execute([$data['numero_permis'], $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ce numéro de permis est déjà utilisé par un autre utilisateur']);
        exit;
    }
    
    // Mettre à jour le numéro de permis dans le profil utilisateur
    $stmt = $conn->prepare("UPDATE users SET numero_permis = ? WHERE id = ?");
    $stmt->execute([$data['numero_permis'], $_SESSION['user_id']]);
    
    // Insérer la nouvelle demande avec latitude/longitude
    $stmt = $conn->prepare("INSERT INTO demandes_camion (user_id, nom_camion, numero_permis, emplacement, latitude, longitude, menu, jours, etat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'en attente')");
    $stmt->execute([
        $_SESSION['user_id'],
        $data['nom_camion'],
        $data['numero_permis'],
        $data['emplacement'],
        isset($data['latitude']) ? $data['latitude'] : null,
        isset($data['longitude']) ? $data['longitude'] : null,
        $data['menu'],
        implode(',', $data['jours'])
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Votre demande de camion a bien été enregistrée.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>