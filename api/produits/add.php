<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['nom']) || empty($data['prix_unitaire'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        
        $stmt = $conn->prepare("INSERT INTO produits (nom, type, prix_unitaire, obligatoire, quantite_minimale) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['nom'],
            isset($data['type']) ? $data['type'] : 'aliment',
            $data['prix_unitaire'],
            isset($data['obligatoire']) ? ($data['obligatoire'] ? 1 : 0) : 1,
            isset($data['quantite_minimale']) ? intval($data['quantite_minimale']) : 0
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Produit ajouté avec succès', 'id' => $conn->lastInsertId()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>