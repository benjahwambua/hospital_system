require('../vendor/fpdf/fpdf.php');
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Hospital Invoice',0,1);
$pdf->Output();
