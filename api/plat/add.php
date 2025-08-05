<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_franchise_validated();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['nom']) || empty($data['prix'])) {
        echo json_encode(['success' => false, 'message' => 'Nom et prix obligatoires']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        $conn->beginTransaction();
        $userId = $_SESSION['user_id'];
        
        // Créer le plat
        $stmt = $conn->prepare("
            INSERT INTO menus (user_id, nom, description, prix, categorie, ingredients, allergenes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $data['nom'],
            $data['description'] ?? '',
            $data['prix'],
            $data['categorie'] ?? 'plat',
            $data['ingredients'] ?? '',
            $data['allergenes'] ?? ''
        ]);
        
        $menuId = $conn->lastInsertId();
        
        // Ajouter les ingrédients techniques si fournis
        if (!empty($data['ingredients_techniques']) && is_array($data['ingredients_techniques'])) {
            $stmt = $conn->prepare("INSERT INTO menu_produits (menu_id, produit_id, quantite_necessaire, unite) VALUES (?, ?, ?, ?)");
            
            foreach ($data['ingredients_techniques'] as $ingredient) {
                if (!empty($ingredient['produit_id']) && !empty($ingredient['quantite_necessaire'])) {
                    $stmt->execute([
                        $menuId,
                        $ingredient['produit_id'],
                        $ingredient['quantite_necessaire'],
                        $ingredient['unite'] ?? 'unites'
                    ]);
                }
            }
        }
        
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Plat ajouté avec succès',
            'id' => $menuId
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>