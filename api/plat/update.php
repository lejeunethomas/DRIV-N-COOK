<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_franchise_validated();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['id']) || empty($data['nom']) || empty($data['prix'])) {
        echo json_encode(array('success' => false, 'message' => 'Données manquantes'));
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        $conn->beginTransaction();
        $userId = $_SESSION['user_id'];
        
        // Vérifier que le plat appartient au franchisé
        $stmt = $conn->prepare("SELECT id FROM menus WHERE id = ? AND user_id = ?");
        $stmt->execute(array($data['id'], $userId));
        
        if (!$stmt->fetch()) {
            echo json_encode(array('success' => false, 'message' => 'Plat non trouvé'));
            exit;
        }
        
        $stmt = $conn->prepare("
            UPDATE menus 
            SET nom = ?, description = ?, prix = ?, categorie = ?, ingredients = ?, allergenes = ?
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->execute(array(
            $data['nom'],
            isset($data['description']) ? $data['description'] : '',
            $data['prix'],
            isset($data['categorie']) ? $data['categorie'] : 'plat',
            isset($data['ingredients']) ? $data['ingredients'] : '',
            isset($data['allergenes']) ? $data['allergenes'] : '',
            $data['id'],
            $userId
        ));
        
        // Mettre à jour les ingrédients techniques si fournis
        if (isset($data['ingredients_techniques']) && is_array($data['ingredients_techniques'])) {
            // Supprimer les anciennes liaisons
            $stmt = $conn->prepare("DELETE FROM menu_produits WHERE menu_id = ?");
            $stmt->execute(array($data['id']));
            
            // Ajouter les nouvelles liaisons
            $stmt = $conn->prepare("INSERT INTO menu_produits (menu_id, produit_id, quantite_necessaire, unite) VALUES (?, ?, ?, ?)");
            
            foreach ($data['ingredients_techniques'] as $ingredient) {
                if (!empty($ingredient['produit_id']) && !empty($ingredient['quantite_necessaire'])) {
                    $stmt->execute(array(
                        $data['id'],
                        $ingredient['produit_id'],
                        $ingredient['quantite_necessaire'],
                        isset($ingredient['unite']) ? $ingredient['unite'] : 'unites'
                    ));
                }
            }
        }
        
        $conn->commit();
        echo json_encode(array('success' => true, 'message' => 'Plat mis à jour avec succès'));
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(array('success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()));
    }
} else {
    echo json_encode(array('success' => false, 'message' => 'Méthode non autorisée'));
}
?>