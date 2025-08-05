<?php
require_once '../../includes/db.php';
session_start();
header('Content-Type: application/json');

if (isset($_GET['stats'])) {
    try {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->query("SELECT COALESCE(SUM(montant), 0) as total FROM ventes");
        $result = $stmt->fetch();
        echo json_encode(['total' => number_format($result['total'], 2) . '€']);
    } catch (Exception $e) {
        echo json_encode(['total' => '0€']);
    }
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['montant']) || empty($data['produits'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("
            INSERT INTO ventes (user_id, camion_id, montant, type_paiement, date_vente) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $data['camion_id'] ?? null,
            $data['montant'],
            $data['type_paiement'] ?? 'especes'
        ]);
        
        $venteId = $conn->lastInsertId();
        
        // Ajouter les détails de vente
        foreach ($data['produits'] as $produit) {
            $stmt = $conn->prepare("
                INSERT INTO vente_details (vente_id, produit_id, quantite, prix_unitaire, prix_total) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $venteId,
                $produit['produit_id'],
                $produit['quantite'],
                $produit['prix_unitaire'],
                $produit['prix_total']
            ]);
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Vente enregistrée avec succès', 'vente_id' => $venteId]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>