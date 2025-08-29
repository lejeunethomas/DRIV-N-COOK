<?php
// filepath: api/ventes/export_pdf.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

// Pour l'admin, on accepte un paramètre franchise_id
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
if ($isAdmin && isset($_GET['franchise_id'])) {
    $userId = intval($_GET['franchise_id']);
} else {
    require_franchise_validated();
    $userId = $_SESSION['user_id'];
}

require_once '../../vendor/setasign/fpdf/fpdf.php';

header('Content-Type: application/pdf');

try {
    $conn = Database::getInstance()->getConnection();

    // Historique complet ou mois courant ?
    $all = isset($_GET['all']) && $_GET['all'] == 1;
    $where = "v.user_id = ? AND v.statut = 'valide'";
    $params = [$userId];
    if (!$all) {
        $where .= " AND MONTH(v.date_vente) = MONTH(NOW()) AND YEAR(v.date_vente) = YEAR(NOW())";
    }

    $stmt = $conn->prepare("
        SELECT v.id, v.date_vente, v.montant, c.nom_camion
        FROM ventes v
        LEFT JOIN camions c ON v.camion_id = c.id
        WHERE $where
        ORDER BY v.date_vente ASC
    ");
    $stmt->execute($params);
    $ventes = $stmt->fetchAll();

    // Détails pour chaque vente
    $ventesDetails = [];
    $totalGeneral = 0;
    foreach ($ventes as $vente) {
        $stmt2 = $conn->prepare("
            SELECT COALESCE(m.nom, p.nom) as nom, vd.quantite, vd.prix_unitaire, vd.prix_total
            FROM vente_details vd
            LEFT JOIN menus m ON vd.menu_id = m.id
            LEFT JOIN produits p ON vd.produit_id = p.id
            WHERE vd.vente_id = ?
        ");
        $stmt2->execute([$vente['id']]);
        $details = $stmt2->fetchAll();
        $ventesDetails[] = [
            'vente' => $vente,
            'details' => $details
        ];
        $totalGeneral += $vente['montant'];
    }

    // Récupérer les infos franchisé et food truck
    $stmtInfo = $conn->prepare("
        SELECT u.nom AS franchise_nom, u.prenom AS franchise_prenom, c.nom_camion
        FROM users u
        LEFT JOIN camions c ON c.user_id = u.id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmtInfo->execute([$userId]);
    $info = $stmtInfo->fetch();

    $franchiseNom = $info ? $info['franchise_nom'] : '';
    $franchisePrenom = $info ? $info['franchise_prenom'] : '';
    $foodTruckNom = $info ? $info['nom_camion'] : '';

    // Génération du PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $titre = $all ? "Historique des ventes" : "Récapitulatif des ventes du mois";
    $pdf->Cell(0,10,utf8_decode($titre),0,1,'C');
    $pdf->SetFont('Arial','',12);
    $pdf->Ln(2);

    // Affichage franchisé et food truck
    $pdf->Cell(0,8,utf8_decode("Franchisé : $franchisePrenom $franchiseNom"),0,1);
    $pdf->Cell(0,8,utf8_decode("Food truck : $foodTruckNom"),0,1);
    $pdf->Ln(5);

    foreach ($ventesDetails as $venteData) {
        $vente = $venteData['vente'];
        $details = $venteData['details'];
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,8,utf8_decode("Commande #{$vente['id']} - {$vente['date_vente']} - Camion: {$vente['nom_camion']}"),0,1);
        $pdf->SetFont('Arial','',11);

        // Tableau des produits
        $pdf->Cell(70,8,'Produit',1);
        $pdf->Cell(25,8,'Quantite',1);
        $pdf->Cell(35,8,'Prix unitaire',1);
        $pdf->Cell(35,8,'Total',1);
        $pdf->Ln();

        foreach ($details as $d) {
            $pdf->Cell(70,8,utf8_decode($d['nom']),1);
            $pdf->Cell(25,8,$d['quantite'],1,0,'C');
            $pdf->Cell(35,8,number_format($d['prix_unitaire'],2).' '.chr(128),1,0,'R');
            $pdf->Cell(35,8,number_format($d['prix_total'],2).' '.chr(128),1,0,'R');
            $pdf->Ln();
        }
        // Total commande
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(130,8,'Total commande',1);
        $pdf->Cell(35,8,number_format($vente['montant'],2).' '.chr(128),1,0,'R');
        $pdf->Ln(12);
    }

    // Total général
    $pdf->SetFont('Arial','B',13);
    $pdf->Cell(130,10,'TOTAL GENERAL',1);
    $pdf->Cell(35,10,number_format($totalGeneral,2).' '.chr(128),1,0,'R');

    $filename = $all ? "ventes_historique.pdf" : "ventes_mois.pdf";
    $pdf->Output('D', $filename);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo "Erreur génération PDF: " . $e->getMessage();
}