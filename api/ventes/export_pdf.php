<?php
// filepath: api/ventes/export_pdf.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_franchise_validated();

require_once '../../vendor/autoload.php'; // FPDF ou TCPDF doit être installé via Composer

header('Content-Type: application/pdf');

$userId = $_SESSION['user_id'];

try {
    $conn = Database::getInstance()->getConnection();

    // Récupérer les ventes du mois pour ce franchisé
    $stmt = $conn->prepare("
        SELECT v.id, v.date_vente, v.montant, c.nom_camion,
            GROUP_CONCAT(CONCAT(m.nom, ' (x', vd.quantite, ')') SEPARATOR ', ') as produits
        FROM ventes v
        LEFT JOIN camions c ON v.camion_id = c.id
        LEFT JOIN vente_details vd ON v.id = vd.vente_id
        LEFT JOIN menus m ON vd.menu_id = m.id
        WHERE v.user_id = ? AND MONTH(v.date_vente) = MONTH(NOW()) AND YEAR(v.date_vente) = YEAR(NOW()) AND v.statut = 'valide'
        GROUP BY v.id
        ORDER BY v.date_vente ASC
    ");
    $stmt->execute([$userId]);
    $ventes = $stmt->fetchAll();

    // Générer le PDF (exemple avec FPDF)
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10,utf8_decode('Récapitulatif des ventes du mois'),0,1,'C');
    $pdf->SetFont('Arial','',12);
    $pdf->Ln(5);

    foreach ($ventes as $vente) {
        $pdf->Cell(0,8,utf8_decode("Vente #{$vente['id']} - {$vente['date_vente']} - Camion: {$vente['nom_camion']}"),0,1);
        $pdf->MultiCell(0,8,utf8_decode("Produits: {$vente['produits']}"));
        $pdf->Cell(0,8,utf8_decode("Montant: ".number_format($vente['montant'],2).' €'),0,1);
        $pdf->Ln(2);
    }

    $pdf->Output('D', 'ventes_mois.pdf');
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo "Erreur génération PDF: " . $e->getMessage();
}