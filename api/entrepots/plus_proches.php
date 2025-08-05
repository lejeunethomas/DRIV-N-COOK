<?php
require_once '../../includes/db.php';
header('Content-Type: application/json');

$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;

if ($lat === null || $lng === null) {
    // Si pas de coordonnées, retourner tous les entrepôts
    try {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->query("
            SELECT e.*, COUNT(s.id) as nb_produits_dispo 
            FROM entrepôts e 
            LEFT JOIN stocks s ON e.id = s.entrepot_id AND s.quantite > 0
            WHERE e.actif = 1 
            GROUP BY e.id 
            ORDER BY e.nom
        ");
        $entrepots = $stmt->fetchAll();
        echo json_encode($entrepots);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();
    
    // Récupérer les entrepôts avec coordonnées
    $stmt = $conn->query("
        SELECT e.*, COUNT(s.id) as nb_produits_dispo,
               AVG(CASE WHEN s.quantite <= s.seuil_alerte THEN 1 ELSE 0 END) as taux_alerte
        FROM entrepots e 
        LEFT JOIN stocks s ON e.id = s.entrepot_id 
        WHERE e.latitude IS NOT NULL AND e.longitude IS NOT NULL AND e.actif = 1
        GROUP BY e.id
    ");
    $entrepots = $stmt->fetchAll();
    
    // Calculer les distances
    foreach ($entrepots as &$entrepot) {
        $theta = $lng - $entrepot['longitude'];
        $dist = sin(deg2rad($lat)) * sin(deg2rad($entrepot['latitude'])) + 
                cos(deg2rad($lat)) * cos(deg2rad($entrepot['latitude'])) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $km = $dist * 111.13384;
        
        $entrepot['distance_km'] = round($km, 2);
        $entrepot['temps_livraison_estime'] = calculateDeliveryTime($km);
        $entrepot['score_recommandation'] = calculateRecommendationScore($km, $entrepot['nb_produits_dispo'], $entrepot['taux_alerte']);
    }
    
    // Trier par score de recommandation (distance + disponibilité)
    usort($entrepots, function($a, $b) {
        return $b['score_recommandation'] <=> $a['score_recommandation'];
    });
    
    echo json_encode($entrepots);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

function calculateDeliveryTime($distanceKm) {
    if ($distanceKm <= 10) return "Même jour";
    if ($distanceKm <= 50) return "24h";
    if ($distanceKm <= 150) return "48h";
    return "3-5 jours";
}

function calculateRecommendationScore($distance, $nbProduits, $tauxAlerte) {
    $scoreDistance = max(0, 100 - ($distance * 2));
    $scoreProduits = min(50, $nbProduits * 2);
    $scoreStock = max(0, 30 - ($tauxAlerte * 30));
    
    return $scoreDistance + $scoreProduits + $scoreStock;
}
?>