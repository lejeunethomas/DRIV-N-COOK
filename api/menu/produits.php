<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_franchise_validated();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Récupérer les produits liés à un plat
    if (!isset($_GET['menu_id'])) {
        echo json_encode(array('error' => 'menu_id manquant'));
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        
        $stmt = $conn->prepare("
            SELECT mp.*, p.nom as produit_nom, p.type as produit_type, p.obligatoire
            FROM menu_produits mp
            LEFT JOIN produits p ON mp.produit_id = p.id
            WHERE mp.menu_id = ?
            ORDER BY p.obligatoire DESC, p.nom
        ");
        $stmt->execute(array($_GET['menu_id']));
        $produits = $stmt->fetchAll();
        
        echo json_encode($produits);
        
    } catch (Exception $e) {
        echo json_encode(array('error' => $e->getMessage()));
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajouter/Modifier les produits d'un plat
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['menu_id']) || !isset($data['produits'])) {
        echo json_encode(array('success' => false, 'message' => 'Données manquantes'));
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        $conn->beginTransaction();
        $userId = $_SESSION['user_id'];
        
        // Vérifier que le menu appartient au franchisé
        $stmt = $conn->prepare("SELECT id FROM menus WHERE id = ? AND user_id = ?");
        $stmt->execute(array($data['menu_id'], $userId));
        if (!$stmt->fetch()) {
            echo json_encode(array('success' => false, 'message' => 'Menu non trouvé'));
            exit;
        }
        
        // Supprimer les anciennes liaisons
        $stmt = $conn->prepare("DELETE FROM menu_produits WHERE menu_id = ?");
        $stmt->execute(array($data['menu_id']));
        
        // Ajouter les nouvelles liaisons
        $stmt = $conn->prepare("INSERT INTO menu_produits (menu_id, produit_id, quantite_necessaire, unite) VALUES (?, ?, ?, ?)");
        
        foreach ($data['produits'] as $produit) {
            if (!empty($produit['produit_id']) && !empty($produit['quantite_necessaire'])) {
                $stmt->execute(array(
                    $data['menu_id'],
                    $produit['produit_id'],
                    $produit['quantite_necessaire'],
                    isset($produit['unite']) ? $produit['unite'] : 'unites'
                ));
            }
        }
        
        $conn->commit();
        echo json_encode(array('success' => true, 'message' => 'Ingrédients mis à jour avec succès'));
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(array('success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()));
    }
}
?>