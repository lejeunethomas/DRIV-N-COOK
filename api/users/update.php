<?php
// filepath: c:\MAMP\htdocs\DRIV-N-COOK\api\users\update.php
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
            UPDATE users 
            SET nom = ?, prenom = ?, email = ?, telephone = ?, 
                numero_permis = ?, lieu_installation = ?, statut = ?
            WHERE id = ?
        ");
        $stmt->execute([
            isset($data['nom']) ? $data['nom'] : '',
            isset($data['prenom']) ? $data['prenom'] : '',
            isset($data['email']) ? $data['email'] : '',
            isset($data['telephone']) ? $data['telephone'] : null,
            isset($data['numero_permis']) ? $data['numero_permis'] : null,
            isset($data['lieu_installation']) ? $data['lieu_installation'] : '',
            isset($data['statut']) ? $data['statut'] : 'valide',
            $data['id']
        ]);
        
        if (!empty($data['lieu_installation'])) {
            $stmt = $conn->prepare("
                UPDATE camions 
                SET emplacement = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$data['lieu_installation'], $data['id']]);
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Franchisé mis à jour avec succès (emplacement synchronisé)']);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>