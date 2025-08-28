<?php
require_once '../../includes/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();
    
    $whereClause = "WHERE 1";
    $params = [];

    // Filtrer par camion si demandé
    if (isset($_GET['camion']) && $_GET['camion'] !== '') {
        $whereClause .= " AND v.camion_id = ?";
        $params[] = $_GET['camion'];
    }

    // Filtrer par période si demandé
    if (isset($_GET['periode']) && $_GET['periode'] !== '') {
        switch ($_GET['periode']) {
            case 'aujourdhui':
                $whereClause .= " AND DATE(v.date_vente) = CURDATE()";
                break;
            case 'semaine':
                $whereClause .= " AND YEARWEEK(v.date_vente) = YEARWEEK(NOW())";
                break;
            case 'mois':
                $whereClause .= " AND MONTH(v.date_vente) = MONTH(NOW()) AND YEAR(v.date_vente) = YEAR(NOW())";
                break;
        }
    }

    $whereClause .= " AND v.statut = 'valide'";

    if ($_SESSION['role'] === 'admin') {
        // Admin voit toutes les ventes
        $stmt = $conn->query("
            SELECT v.*, u.nom as franchise_nom, u.prenom as franchise_prenom,
                   c.nom_camion, c.immatriculation,
                   COUNT(vd.id) as nb_produits
            FROM ventes v
            LEFT JOIN users u ON v.user_id = u.id
            LEFT JOIN camions c ON v.camion_id = c.id
            LEFT JOIN vente_details vd ON v.id = vd.vente_id
            $whereClause
            GROUP BY v.id
            ORDER BY v.date_vente DESC
        ");
    } else {
        // Franchisé voit ses ventes
        $stmt = $conn->prepare("
            SELECT v.*, c.nom_camion, c.immatriculation,
                   COUNT(vd.id) as nb_produits
            FROM ventes v
            LEFT JOIN camions c ON v.camion_id = c.id
            LEFT JOIN vente_details vd ON v.id = vd.vente_id
            $whereClause
            GROUP BY v.id
            ORDER BY v.date_vente DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
    }
    
    $ventes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($ventes);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>