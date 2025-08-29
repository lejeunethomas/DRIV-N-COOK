<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['demande_id']) || empty($data['action'])) {
        echo json_encode(array('success' => false, 'message' => 'Données manquantes'));
        exit;
    }
    
    try {
        $conn = Database::getInstance()->getConnection();
        $conn->beginTransaction();
        
        session_start();
        $adminId = $_SESSION['user_id'];
        
        // Récupérer les détails de la demande
        $stmt = $conn->prepare("
            SELECT d.*, u.nom as franchise_nom, u.prenom as franchise_prenom 
            FROM demandes_camion d 
            JOIN users u ON d.user_id = u.id 
            WHERE d.id = ? AND d.etat = 'en attente'
        ");
        $stmt->execute(array($data['demande_id']));
        $demande = $stmt->fetch();
        
        if (!$demande) {
            echo json_encode(array('success' => false, 'message' => 'Demande non trouvée ou déjà traitée'));
            exit;
        }
        
        if ($data['action'] === 'valider') {
            // Générer une immatriculation unique
            $immatriculation = generateImmatriculation($conn);
            
            // Créer le camion
            $stmt = $conn->prepare("
                INSERT INTO camions (
                    user_id, nom_camion, immatriculation, etat, 
                    emplacement, menu, jours, date_livraison
                ) VALUES (?, ?, ?, 'en préparation', ?, ?, ?, ?)
            ");
            $dateLivraison = isset($data['date_livraison']) ? $data['date_livraison'] : 
                date('Y-m-d', strtotime('+14 days')); // Par défaut : livraison dans 2 semaines
                
            $stmt->execute(array(
                $demande['user_id'],
                $demande['nom_camion'],
                $immatriculation,
                $demande['emplacement'],
                $demande['menu'],
                $demande['jours'],
                $dateLivraison
            ));
            
            $camionId = $conn->lastInsertId();
            
            // Mettre à jour la demande
            $stmt = $conn->prepare("
                UPDATE demandes_camion 
                SET etat = 'validee', 
                    date_traitement = NOW(),
                    commentaire_admin = ?
                WHERE id = ?
            ");
            $commentaireAdmin = isset($data['commentaire']) ? $data['commentaire'] : 
                "Demande validée. Camion #{$camionId} créé avec immatriculation {$immatriculation}";
            $stmt->execute(array($commentaireAdmin, $data['demande_id']));
            
            $message = "Demande validée ! Camion créé avec l'immatriculation {$immatriculation}. Livraison prévue le " . date('d/m/Y', strtotime($dateLivraison));
            
        } elseif ($data['action'] === 'refuser') {
            // Refuser la demande
            $stmt = $conn->prepare("
                UPDATE demandes_camion 
                SET etat = 'refusee', 
                    date_traitement = NOW(),
                    commentaire_admin = ?
                WHERE id = ?
            ");
            $commentaireRefus = isset($data['commentaire']) ? $data['commentaire'] : 'Demande refusée par l\'administrateur';
            $stmt->execute(array($commentaireRefus, $data['demande_id']));
            
            $message = 'Demande refusée';
        }
        
        $conn->commit();
        echo json_encode(array(
            'success' => true, 
            'message' => $message,
            'camion_id' => isset($camionId) ? $camionId : null
        ));
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(array('success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()));
    }
} else {
    echo json_encode(array('success' => false, 'message' => 'Méthode non autorisée'));
}

function generateImmatriculation($conn) {
    // Générer une immatriculation au format français
    $lettres1 = chr(rand(65, 90)) . chr(rand(65, 90)); 
    $chiffres = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT); 
    $lettres2 = chr(rand(65, 90)) . chr(rand(65, 90));
    
    $immatriculation = "{$lettres1}-{$chiffres}-{$lettres2}";
    
    // Vérifier l'unicité
    $stmt = $conn->prepare("SELECT id FROM camions WHERE immatriculation = ?");
    $stmt->execute(array($immatriculation));
    
    if ($stmt->fetch()) {
        // Si déjà utilisée, recommencer
        return generateImmatriculation($conn);
    }
    
    return $immatriculation;
}
?>
