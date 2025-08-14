<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

try {
    $conn = Database::getInstance()->getConnection();
    
    $whereClause = "WHERE 1";
    $params = [];

    if (isset($_GET['statut']) && $_GET['statut'] !== '') {
        $whereClause .= " AND c.statut = ?";
        $params[] = $_GET['statut'];
    }
    
    $stmt = $conn->prepare("
        SELECT c.*, 
               u.nom as franchise_nom, u.prenom as franchise_prenom, u.email as franchise_email,
               e.nom as entrepot_nom, e.ville as entrepot_ville, e.adresse as entrepot_adresse,
               e.latitude as entrepot_latitude, e.longitude as entrepot_longitude,
               admin.nom as admin_nom, admin.prenom as admin_prenom,
               COUNT(cd.id) as nb_produits,
               GROUP_CONCAT(CONCAT(p.nom, ' (', cd.quantite, ')') SEPARATOR ', ') as produits_resume,
               -- Essayer de récupérer les coordonnées du franchisé depuis ses demandes de camion
               dc.latitude as franchise_latitude, dc.longitude as franchise_longitude
        FROM commandes c 
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN entrepots e ON c.entrepot_id = e.id
        LEFT JOIN users admin ON c.validee_par = admin.id
        LEFT JOIN commande_details cd ON c.id = cd.commande_id
        LEFT JOIN produits p ON cd.produit_id = p.id
        LEFT JOIN (
            SELECT user_id, latitude, longitude,
                   ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY date_demande DESC) as rn
            FROM demandes_camion 
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL
        ) dc ON u.id = dc.user_id AND dc.rn = 1
        {$whereClause}
        GROUP BY c.id
        ORDER BY 
            CASE c.statut 
                WHEN 'en_attente' THEN 1 
                WHEN 'validee' THEN 2 
                WHEN 'en_preparation' THEN 3 
                WHEN 'livree' THEN 4 
                WHEN 'annulee' THEN 5 
            END,
            c.date_commande ASC
    ");
    $stmt->execute($params);
    $commandes = $stmt->fetchAll();
    
    // Calculer les distances et temps de livraison
    foreach ($commandes as $key => $commande) {
        if ($commande['entrepot_latitude'] && $commande['entrepot_longitude'] && 
            $commande['franchise_latitude'] && $commande['franchise_longitude']) {
            
            $distance = calculateDistance(
                $commande['entrepot_latitude'], $commande['entrepot_longitude'],
                $commande['franchise_latitude'], $commande['franchise_longitude']
            );
            
            $commandes[$key]['distance_km'] = round($distance, 2);
            $commandes[$key]['temps_livraison_estime'] = calculateDeliveryTime($distance);
            $commandes[$key]['urgence_livraison'] = calculateUrgency($distance, $commande['date_commande']);
        } else {
            $commandes[$key]['distance_km'] = null;
            $commandes[$key]['temps_livraison_estime'] = 'Non calculable';
            $commandes[$key]['urgence_livraison'] = 'normale';
        }
    }
    
    echo json_encode($commandes);
} catch (Exception $e) {
    echo json_encode(array('error' => $e->getMessage()));
}

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + 
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    return $dist * 111.13384; // Conversion en km
}

function calculateDeliveryTime($distanceKm) {
    if ($distanceKm <= 10) return "Même jour";
    if ($distanceKm <= 50) return "24h";
    if ($distanceKm <= 150) return "48h";
    if ($distanceKm <= 300) return "3 jours";
    return "4-5 jours";
}

function calculateUrgency($distanceKm, $dateCommande) {
    $joursDiff = (time() - strtotime($dateCommande)) / (60 * 60 * 24);
    
    if ($distanceKm <= 10 && $joursDiff > 1) return 'urgente';
    if ($distanceKm <= 50 && $joursDiff > 2) return 'urgente';
    if ($distanceKm <= 150 && $joursDiff > 3) return 'urgente';
    if ($joursDiff > 5) return 'urgente';
    
    return $joursDiff > 2 ? 'attention' : 'normale';
}
?>