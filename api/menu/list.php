<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    $conn = Database::getInstance()->getConnection();

    if (isset($_GET['camion_id'])) {
        // Récupérer le franchisé propriétaire du camion
        $stmt = $conn->prepare("SELECT user_id FROM camions WHERE id = ?");
        $stmt->execute([$_GET['camion_id']]);
        $camion = $stmt->fetch();
        if (!$camion) {
            echo json_encode([]);
            exit;
        }
        $userId = $camion['user_id'];
    } else {
        require_franchise_validated();
        $userId = $_SESSION['user_id'];
    }

    // Récupérer tous les plats du menu
    $stmt = $conn->prepare("
        SELECT 
            id,
            nom,
            description,
            prix,
            categorie,
            ingredients,
            allergenes,
            date_creation,
            date_modification
        FROM menus 
        WHERE user_id = ? 
        ORDER BY categorie, nom
    ");
    $stmt->execute([$userId]);
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pour chaque plat, récupérer la réduction fidélité max de ses produits partenaires
    foreach ($menus as &$plat) {
        $stmt2 = $conn->prepare("
            SELECT MAX(p.reduction_fidelite) as reduction_fidelite
            FROM menu_produits mp
            JOIN produits p ON mp.produit_id = p.id
            WHERE mp.menu_id = ? AND p.obligatoire = 1
        ");
        $stmt2->execute([$plat['id']]);
        $reduction = $stmt2->fetchColumn();
        $plat['reduction_fidelite'] = $reduction ? floatval($reduction) : 0;
    }

    echo json_encode($menus);

} catch (Exception $e) {
    echo json_encode([]);
}
?>