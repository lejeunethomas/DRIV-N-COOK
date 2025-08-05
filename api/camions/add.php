<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['nom_camion']) || empty($data['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes']);
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        
        // Générer une immatriculation unique
        $immatriculation = generateImmatriculation($conn);
        
        $stmt = $conn->prepare("
            INSERT INTO camions (user_id, nom_camion, immatriculation, etat, emplacement, menu, jours, date_livraison) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['user_id'],
            $data['nom_camion'],
            $immatriculation,
            $data['etat'] ?? 'en_preparation',
            $data['emplacement'] ?? '',
            $data['menu'] ?? '',
            $data['jours'] ?? '',
            $data['date_livraison'] ?? date('Y-m-d', strtotime('+14 days'))
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Camion ajouté avec succès',
            'id' => $conn->lastInsertId(),
            'immatriculation' => $immatriculation
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}

function generateImmatriculation($conn) {
    $lettres1 = chr(rand(65, 90)) . chr(rand(65, 90)); 
    $chiffres = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT); 
    $lettres2 = chr(rand(65, 90)) . chr(rand(65, 90));
    $immatriculation = "{$lettres1}-{$chiffres}-{$lettres2}";
    
    // Vérifier l'unicité
    $stmt = $conn->prepare("SELECT id FROM camions WHERE immatriculation = ?");
    $stmt->execute([$immatriculation]);
    
    if ($stmt->fetch()) {
        return generateImmatriculation($conn);
    }
    
    return $immatriculation;
}
?>