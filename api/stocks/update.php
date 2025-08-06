<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['entrepot_id']) || empty($data['produit_id'])) {
        echo json_encode(array('success' => false, 'message' => 'Données manquantes'));
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        
        // Vérifier si le stock existe
        $stmt = $conn->prepare("SELECT id FROM stocks WHERE entrepot_id = ? AND produit_id = ?");
        $stmt->execute(array($data['entrepot_id'], $data['produit_id']));
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Mettre à jour
            $stmt = $conn->prepare("UPDATE stocks SET quantite = ?, unite = ?, seuil_alerte = ? WHERE entrepot_id = ? AND produit_id = ?");
            $stmt->execute(array(
                $data['quantite'],
                isset($data['unite']) ? $data['unite'] : 'unites',
                isset($data['seuil_alerte']) ? $data['seuil_alerte'] : 0,
                $data['entrepot_id'],
                $data['produit_id']
            ));
            $message = 'Stock mis à jour avec succès';
        } else {
            // Créer
            $stmt = $conn->prepare("INSERT INTO stocks (entrepot_id, produit_id, quantite, unite, seuil_alerte) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(array(
                $data['entrepot_id'],
                $data['produit_id'],
                $data['quantite'],
                isset($data['unite']) ? $data['unite'] : 'unites',
                isset($data['seuil_alerte']) ? $data['seuil_alerte'] : 0
            ));
            $message = 'Stock créé avec succès';
        }
        
        echo json_encode(array('success' => true, 'message' => $message));
        
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()));
    }
} else {
    echo json_encode(array('success' => false, 'message' => 'Méthode non autorisée'));
}
?>