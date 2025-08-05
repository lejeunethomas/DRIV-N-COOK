<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['produits']) || empty($data['entrepot_id'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        $conn->beginTransaction();
        
        $totalQuantite = 0;
        $quantiteObligatoire = 0;
        
        foreach ($data['produits'] as $item) {
            $stmt = $conn->prepare("SELECT obligatoire, quantite_minimale FROM produits WHERE id = ?");
            $stmt->execute([$item['produit_id']]);
            $produit = $stmt->fetch();
            
            if ($produit) {
                $totalQuantite += $item['quantite'];
                if ($produit['obligatoire']) {
                    $quantiteObligatoire += $item['quantite'];
                }
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO commandes (user_id, entrepot_id, statut) VALUES (?, ?, 'en_attente')");
        $stmt->execute([$_SESSION['user_id'], $data['entrepot_id']]);
        $commandeId = $conn->lastInsertId();
        
        $total = 0;
        
        foreach ($data['produits'] as $item) {
            $stmt = $conn->prepare("SELECT prix_unitaire, obligatoire, quantite_minimale FROM produits WHERE id = ?");
            $stmt->execute([$item['produit_id']]);
            $produit = $stmt->fetch();
            
            if ($produit) {
                // Vérifier la quantité minimale pour les produits obligatoires
                if ($produit['obligatoire'] && $produit['quantite_minimale'] > 0 && $item['quantite'] < $produit['quantite_minimale']) {
                    echo json_encode(['success' => false, 'message' => "Le produit '{$produit['nom']}' nécessite une quantité minimale de {$produit['quantite_minimale']}"]);
                    exit;
                }
                
                $prixTotal = $produit['prix_unitaire'] * $item['quantite'];
                $total += $prixTotal;
                
                $stmt = $conn->prepare("INSERT INTO commande_details (commande_id, produit_id, quantite, prix_unitaire, prix_total) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$commandeId, $item['produit_id'], $item['quantite'], $produit['prix_unitaire'], $prixTotal]);
            }
        }
        
        $stmt = $conn->prepare("UPDATE commandes SET total = ? WHERE id = ?");
        $stmt->execute([$total, $commandeId]);
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Commande créée avec succès', 'commande_id' => $commandeId, 'total' => $total]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>