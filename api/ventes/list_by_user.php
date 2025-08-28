<?php
// filepath: api/ventes/list_by_user.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    $conn = Database::getInstance()->getConnection();
    session_start();
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
    $userId = $_SESSION['user_id'];

    if ($role === 'franchise') {
        $whereClause = "WHERE v.user_id = ?";
        $params = [$userId];
    } elseif ($role === 'client') {
        $whereClause = "WHERE v.client_id = ?";
        $params = [$userId];
    } else {
        echo json_encode([]);
        exit;
    }

    $whereClause .= " AND v.statut = 'valide'";

    $stmt = $conn->prepare("
        SELECT 
            v.id,
            v.montant,
            v.date_vente,
            v.type_paiement,
            v.statut,
            c.nom_camion,
            GROUP_CONCAT(CONCAT(m.nom, ' (x', vd.quantite, ')') SEPARATOR ', ') as produits_resume
        FROM ventes v
        LEFT JOIN camions c ON v.camion_id = c.id
        LEFT JOIN vente_details vd ON v.id = vd.vente_id
        LEFT JOIN menus m ON vd.menu_id = m.id
        $whereClause
        GROUP BY v.id
        ORDER BY v.date_vente DESC
    ");

    $stmt->execute($params);
    $ventes = $stmt->fetchAll();

    echo json_encode($ventes);

} catch (Exception $e) {
    echo json_encode([]);
}
?>