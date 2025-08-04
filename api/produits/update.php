<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['id']) || empty($data['nom']) || empty($data['prix_unitaire'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        
        $stmt = $conn->prepare("UPDATE produits SET nom = ?, type = ?, prix_unitaire = ?, obligatoire = ?, entrepot_id = ? WHERE id = ?");
        $stmt->execute([
            $data['nom'],
            $data['type'] ?? 'aliment',
            $data['prix_unitaire'],
            isset($data['obligatoire']) ? ($data['obligatoire'] ? 1 : 0) : 1,
            $data['entrepot_id'] ?? null,
            $data['id']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Produit mis à jour avec succès']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>