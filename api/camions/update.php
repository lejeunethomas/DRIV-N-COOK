<?php
// filepath: c:\MAMP\htdocs\DRIV-N-COOK\api\camions\update.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID manquant']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("
            UPDATE camions 
            SET nom_camion = ?, etat = ?, emplacement = ?, menu = ?, jours = ?, date_livraison = ?
            WHERE id = ?
        ");
        $stmt->execute([
            isset($data['nom_camion']) ? $data['nom_camion'] : '',
            isset($data['etat']) ? $data['etat'] : 'en_preparation',
            isset($data['emplacement']) ? $data['emplacement'] : '',
            isset($data['menu']) ? $data['menu'] : '',
            isset($data['jours']) ? $data['jours'] : '',
            array_key_exists('date_livraison', $data) ? $data['date_livraison'] : null,
            $data['id']
        ]);
        
        if (!empty($data['emplacement'])) {
            $stmt = $conn->prepare("
                UPDATE users 
                SET lieu_installation = ?
                WHERE id = (SELECT user_id FROM camions WHERE id = ?)
            ");
            $stmt->execute([$data['emplacement'], $data['id']]);
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Camion mis à jour avec succès (emplacement synchronisé)']);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>