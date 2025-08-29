<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();

    $venteId = intval($_GET['id']);
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

    if ($role === 'client') {
        $stmt = $conn->prepare("SELECT * FROM ventes WHERE id = ?");
        $stmt->execute([$venteId]);
    } else {
        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT * FROM ventes WHERE id = ? AND user_id = ?");
        $stmt->execute([$venteId, $userId]);
    }
    $vente = $stmt->fetch();

    if (!$vente) {
        echo json_encode(['success' => false, 'message' => 'Vente non trouvée']);
        exit;
    }

    // Récupérer les détails
    $stmt = $conn->prepare("
        SELECT 
            vd.*,
            COALESCE(m.nom, p.nom) AS nom
        FROM vente_details vd
        LEFT JOIN menus m ON vd.menu_id = m.id
        LEFT JOIN produits p ON vd.produit_id = p.id
        WHERE vd.vente_id = ?
    ");
    $stmt->execute([$venteId]);
    $details = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'details' => $details,
        'montant' => $vente['montant'],
        'date_vente' => $vente['date_vente'],
        'statut' => $vente['statut']
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>