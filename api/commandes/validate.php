<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_admin(); // Au lieu de require_role('admin')

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['commande_id']) || empty($data['action'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        $conn->beginTransaction();
        
        session_start();
        $adminId = $_SESSION['user_id'];
        
        if ($data['action'] === 'valider') {
            // Vérifier la disponibilité des stocks
            $stmt = $conn->prepare("
                SELECT cd.produit_id, cd.quantite, s.quantite as stock_disponible, p.nom as produit_nom
                FROM commande_details cd
                LEFT JOIN commandes c ON cd.commande_id = c.id
                LEFT JOIN produits p ON cd.produit_id = p.id
                LEFT JOIN stocks s ON p.id = s.produit_id AND s.entrepot_id = c.entrepot_id
                WHERE cd.commande_id = ?
            ");
            $stmt->execute([$data['commande_id']]);
            $details = $stmt->fetchAll();
            
            $stocksInsuffisants = [];
            foreach ($details as $detail) {
                if ($detail['stock_disponible'] < $detail['quantite']) {
                    $stocksInsuffisants[] = $detail['produit_nom'] . 
                        " (demandé: {$detail['quantite']}, disponible: {$detail['stock_disponible']})";
                }
            }
            
            if (!empty($stocksInsuffisants)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Stocks insuffisants pour: ' . implode(', ', $stocksInsuffisants)
                ]);
                exit;
            }
            
            // Mettre à jour la commande
            $stmt = $conn->prepare("
                UPDATE commandes 
                SET statut = 'validee', 
                    date_livraison_prevue = ?, 
                    commentaire_admin = ?, 
                    validee_par = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $data['date_livraison'],
                $data['commentaire'],
                $_SESSION['user_id'],
                $data['commande_id']
            ]);
            
            // Réserver les stocks (optionnel - on peut aussi attendre la livraison)
            if (isset($data['reserver_stocks']) && $data['reserver_stocks']) {
                foreach ($details as $detail) {
                    $stmt = $conn->prepare("
                        UPDATE stocks 
                        SET quantite = quantite - ? 
                        WHERE produit_id = ? AND entrepot_id = (
                            SELECT entrepot_id FROM commandes WHERE id = ?
                        )
                    ");
                    $stmt->execute([$detail['quantite'], $detail['produit_id'], $data['commande_id']]);
                }
            }
            
            $message = 'Commande validée avec succès';
            
        } elseif ($data['action'] === 'annuler') {
            $stmt = $conn->prepare("
                UPDATE commandes 
                SET statut = 'annulee', 
                    commentaire_admin = ?, 
                    validee_par = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['commentaire'] ?? 'Commande annulée par l\'administrateur',
                $adminId,
                $data['commande_id']
            ]);
            
            $message = 'Commande annulée';
            
        } elseif ($data['action'] === 'livrer') {
            // Déduire les stocks lors de la livraison
            $stmt = $conn->prepare("
                SELECT cd.produit_id, cd.quantite, c.entrepot_id
                FROM commande_details cd
                LEFT JOIN commandes c ON cd.commande_id = c.id
                WHERE cd.commande_id = ?
            ");
            $stmt->execute([$data['commande_id']]);
            $details = $stmt->fetchAll();
            
            foreach ($details as $detail) {
                $stmt = $conn->prepare("
                    UPDATE stocks 
                    SET quantite = GREATEST(0, quantite - ?) 
                    WHERE produit_id = ? AND entrepot_id = ?
                ");
                $stmt->execute([$detail['quantite'], $detail['produit_id'], $detail['entrepot_id']]);
            }
            
            $stmt = $conn->prepare("
                UPDATE commandes 
                SET statut = 'livree', 
                    commentaire_admin = CONCAT(COALESCE(commentaire_admin, ''), '\n', 'Livré le ', NOW())
                WHERE id = ?
            ");
            $stmt->execute([$data['commande_id']]);
            
            $message = 'Commande marquée comme livrée et stocks mis à jour';
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => $message]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>