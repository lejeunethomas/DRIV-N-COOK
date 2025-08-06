<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    $conn = Database::getInstance()->getConnection();
    
    // Si admin, voir tous les camions (statistique)
    if (isset($_GET['admin']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $stmt = $conn->query("SELECT * FROM camions");
        $camions = $stmt->fetchAll();
        echo json_encode($camions);
    } else {
        // Si franchisé, voir seulement ses camions
        require_franchise_validated();
        $userId = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("
            SELECT c.*, u.nom as franchise_nom, u.prenom as franchise_prenom 
            FROM camions c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.user_id = ?
        ");
        $stmt->execute(array($userId));
        $camions = $stmt->fetchAll();
        
        echo json_encode($camions);
    }
} catch (Exception $e) {
    echo json_encode(array('error' => $e->getMessage()));
}
?>