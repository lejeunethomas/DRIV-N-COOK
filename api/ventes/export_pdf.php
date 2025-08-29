<?php
// filepath: api/ventes/export_pdf.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_franchise_validated();

require_once '../../vendor/autoload.php'; // FPDF doit être installé via Composer

header('Content-Type: application/pdf');

$userId = $_SESSION['user_id'];

try {
    $conn = Database::getInstance()->getConnection();

    // Récupérer les ventes validées du mois pour ce franchisé
    $stmt = $conn->prepare("
        SELECT v.id, v.date_vente, v.montant, c.nom_camion
        FROM ventes v
        LEFT JOIN camions c ON v.camion_id = c.id
        WHERE v.user_id = ? AND MONTH(v.date_vente) = MONTH(NOW()) AND YEAR(v.date_vente) = YEAR(NOW()) AND v.statut = 'valide'
        ORDER BY v.date_vente ASC
    ");
    $stmt->execute([$userId]);
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

    // Récupérer le mois et l'année actuels
    $mois = date('m');
    $annee = date('Y');
    // Générer le PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10,utf8_decode("Récapitulatif des ventes du mois $mois/$annee"),0,1,'C');
    $pdf->SetFont('Arial','',12);
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
        $pdf->Cell(130,8,utf8_decode('Total commande'),1);
        $pdf->Cell(35,8,number_format($vente['montant'],2).' '.chr(128),1,0,'R');
        $pdf->Ln(12);
    }

    // Total général
    $pdf->SetFont('Arial','B',13);
    $pdf->Cell(130,10,utf8_decode('TOTAL GENERAL'),1);
    $pdf->Cell(35,10,number_format($totalGeneral,2).' '.chr(128),1,0,'R');

    $pdf->Output('D', "ventes_{$mois}/{$annee}.pdf");
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo "Erreur génération PDF: " . $e->getMessage();
}