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
    $menus = $stmt->fetchAll();

    echo json_encode($menus);

} catch (Exception $e) {
    echo json_encode([]);
}
?>