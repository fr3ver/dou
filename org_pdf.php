<?php

/** @param list<string> $paragraphs */
function org_pdf_write(string $title, array $paragraphs, string $targetPath, ?string $subtitle = null): void
{
    if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {
        define('K_TCPDF_EXTERNAL_CONFIG', true);
    }
    if (!defined('K_PATH_MAIN')) {
        define('K_PATH_MAIN', __DIR__ . '/../lib/tcpdf/');
    }
    if (!defined('K_PATH_FONTS')) {
        define('K_PATH_FONTS', K_PATH_MAIN . 'fonts/');
    }

    require_once K_PATH_MAIN . 'tcpdf.php';

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator(SITE_NAME);
    $pdf->SetAuthor(SITE_NAME);
    $pdf->SetTitle($title);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(20, 22, 20);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', 'B', 14);
    $pdf->MultiCell(0, 9, $title, 0, 'C', false, 1);
    $pdf->Ln(4);

    if ($subtitle !== null && $subtitle !== '') {
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->MultiCell(0, 7, $subtitle, 0, 'C', false, 1);
        $pdf->Ln(6);
    }

    $pdf->SetFont('dejavusans', '', 11);
    foreach ($paragraphs as $paragraph) {
        $pdf->MultiCell(0, 6.5, $paragraph, 0, 'J', false, 1);
        $pdf->Ln(3);
    }

    $dir = dirname($targetPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $pdf->Output($targetPath, 'F');
}
