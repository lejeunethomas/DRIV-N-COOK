<?php
require_once '../../includes/db.php';
header('Content-Type: application/json');

try {
    if (isset($_GET['admin'])) {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->query("SELECT COUNT(*) as count FROM commandes");
        $result = $stmt->fetch();
        echo json_encode(['length' => $result['count']]);
    } else {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([]);
            exit;
        }
        
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare("
            SELECT c.*, e.nom as entrepot_nom,
                   COUNT(cd.id) as nb_produits,
                   GROUP_CONCAT(CONCAT(p.nom, ' (', cd.quantite, ')') SEPARATOR ', ') as produits_resume,
                   admin.nom as admin_nom, admin.prenom as admin_prenom
            FROM commandes c 
            LEFT JOIN entrepots e ON c.entrepot_id = e.id
            LEFT JOIN commande_details cd ON c.id = cd.commande_id
            LEFT JOIN produits p ON cd.produit_id = p.id
            LEFT JOIN users admin ON c.validee_par = admin.id
            WHERE c.user_id = ? 
            GROUP BY c.id
            ORDER BY c.date_commande DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $commandes = $stmt->fetchAll();
        
        echo json_encode($commandes);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>