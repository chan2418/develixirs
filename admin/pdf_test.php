<?php
// TEST FILE - NO PROJECT DEPENDENCIES
require __DIR__ . '/../vendor/autoload.php';

$html = '<h1>TEST PDF</h1><p>If you see this as PDF, Dompdf works!</p>';

$dompdf = new \Dompdf\Dompdf();
$dompdf->loadHtml($html);
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="test.pdf"');
echo $dompdf->output();
