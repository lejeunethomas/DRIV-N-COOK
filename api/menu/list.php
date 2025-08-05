<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_franchise_validated();

header('Content-Type: application/json');

try {
    $conn = Database::getInstance()->getConnection();
    $userId = $_SESSION['user_id'];
    
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