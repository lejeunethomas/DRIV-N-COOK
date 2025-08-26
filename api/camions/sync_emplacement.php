<?php
// filepath: c:\MAMP\htdocs\DRIV-N-COOK\api\camions\sync_emplacement.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['camion_id']) || empty($data['emplacement'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }

    try {
        $conn = Database::getInstance()->getConnection();
        $conn->beginTransaction();

        // Mettre à jour l'emplacement ET les coordonnées GPS si fournies
        $stmt = $conn->prepare("
            UPDATE camions SET emplacement = ?, latitude = ?, longitude = ? WHERE id = ?
        ");
        $stmt->execute([
            $data['emplacement'],
            isset($data['latitude']) ? $data['latitude'] : null,
            isset($data['longitude']) ? $data['longitude'] : null,
            $data['camion_id']
        ]);

        // Synchroniser l'emplacement du franchisé
        $stmt = $conn->prepare("SELECT user_id FROM camions WHERE id = ?");
        $stmt->execute([$data['camion_id']]);
        $camion = $stmt->fetch();

        if ($camion) {
            $stmt = $conn->prepare("UPDATE users SET lieu_installation = ? WHERE id = ?");
            $stmt->execute([$data['emplacement'], $camion['user_id']]);
        }

        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Emplacement et coordonnées synchronisés'
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>