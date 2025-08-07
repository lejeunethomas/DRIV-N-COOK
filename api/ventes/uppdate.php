<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('client');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['id']) || empty($data['statut'])) {
        echo json_encode(['success' => false, 'message' => 'ID et statut requis']);
        exit;
    }

    $statutsAutorises = ['en_attente', 'valide'];
    if (!in_array($data['statut'], $statutsAutorises)) {
        echo json_encode(['success' => false, 'message' => 'Statut non autorisé']);
        exit;
    }

    try {
        $conn = Database::getInstance()->getConnection();
        $userId = $_SESSION['user_id'];

        $stmt = $conn->prepare("SELECT id, statut FROM ventes WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['id'], $userId]);
        $vente = $stmt->fetch();
        
        if (!$vente) {
            echo json_encode(['success' => false, 'message' => 'Commande non trouvée']);
            exit;
        }
        
        if ($vente['statut'] === 'valide' && $data['statut'] === 'valide') {
            echo json_encode(['success' => true, 'message' => 'Commande déjà validée']);
            exit;
        }
        
        if ($vente['statut'] !== 'en_attente' && $data['statut'] === 'valide') {
            echo json_encode(['success' => false, 'message' => 'Seules les commandes en attente peuvent être validées']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE ventes SET statut = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['statut'], $data['id'], $userId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Statut mis à jour',
                'nouveau_statut' => $data['statut']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Aucune modification effectuée']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>