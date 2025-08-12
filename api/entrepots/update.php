<?php
// filepath: c:\MAMP\htdocs\DRIV-N-COOK\api\entrepots\update.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['id']) || empty($data['nom'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        
        if (isset($data['latitude']) && isset($data['longitude'])) {
            $stmt = $conn->prepare("UPDATE entrepots SET nom = ?, adresse = ?, ville = ?, code_postal = ?, telephone = ?, email = ?, responsable = ?, latitude = ?, longitude = ? WHERE id = ?");
            $stmt->execute([
                $data['nom'],
                $data['adresse'],
                $data['ville'],
                $data['code_postal'],
                isset($data['telephone']) ? $data['telephone'] : null,
                isset($data['email']) ? $data['email'] : null,
                isset($data['responsable']) ? $data['responsable'] : null,
                floatval($data['latitude']),
                floatval($data['longitude']),
                $data['id']
            ]);
        } else {
            $stmt = $conn->prepare("UPDATE entrepots SET nom = ?, adresse = ?, ville = ?, code_postal = ?, telephone = ?, email = ?, responsable = ? WHERE id = ?");
            $stmt->execute([
                $data['nom'],
                $data['adresse'],
                $data['ville'],
                $data['code_postal'],
                isset($data['telephone']) ? $data['telephone'] : null,
                isset($data['email']) ? $data['email'] : null,
                isset($data['responsable']) ? $data['responsable'] : null,
                $data['id']
            ]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Entrepôt mis à jour avec succès']);
        
    } catch (Exception $e) {
        error_log('Erreur update entrepot: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>