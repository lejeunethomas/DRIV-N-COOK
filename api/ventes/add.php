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
        
        // Récupérer le franchisé propriétaire du camion
        $camionId = isset($data['camion_id']) ? $data['camion_id'] : null;
        $stmt = $conn->prepare("SELECT user_id FROM camions WHERE id = ?");
        $stmt->execute([$camionId]);
        $camion = $stmt->fetch();
        if (!$camion) {
            echo json_encode(['success' => false, 'message' => 'Camion introuvable']);
            $conn->rollBack();
            exit;
        }
        $userId = $camion['user_id'];

        $clientId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

        $stmt = $conn->prepare("
            INSERT INTO ventes (user_id, camion_id, montant, type_paiement, date_vente, statut, client_id) 
            VALUES (?, ?, ?, ?, NOW(), ?, ?)
        ");
        $stmt->execute([
            $userId,
            $camionId,
            $data['montant'],
            isset($data['type_paiement']) ? $data['type_paiement'] : 'especes',
            isset($data['statut']) ? $data['statut'] : 'en_attente',
            $clientId
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