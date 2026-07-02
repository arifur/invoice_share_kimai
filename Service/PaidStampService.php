<?php

/*
 * This file is part of the "Invoice-Share plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\InvoiceShareBundle\Service;

use setasign\Fpdi\Fpdi;

/**
 * Stamps an existing invoice PDF with a "PAID" seal.
 *
 * Uses FPDI (bundled with Kimai) to import each page of the source PDF as a form
 * XObject and overlay a stamp on top. The original page content streams are kept
 * verbatim, so the invoice template, fonts and layout are preserved exactly - we
 * only add the stamp objects, never re-render or replace the document.
 *
 * The result is returned as a binary string and the source file on disk is never
 * modified.
 */
final class PaidStampService
{
    /**
     * Stamp the given PDF file with a "PAID" seal and return the new PDF bytes.
     *
     * @param string $sourceFile Absolute path to the original invoice PDF
     * @return string The stamped PDF content (binary)
     */
    public function stampPaid(string $sourceFile): string
    {
        $pdf = new StampedPdf();

        $pageCount = $pdf->setSourceFile($sourceFile);
        if ($pageCount < 1) {
            throw new \RuntimeException('The invoice PDF contains no pages.');
        }

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $templateId = $pdf->importPage($pageNumber);
            $size = $pdf->getTemplateSize($templateId);

            // importPage() throws if the page cannot be read, so getTemplateSize()
            // returns a valid size array here. Guard defensively nonetheless.
            if (!is_array($size)) {
                throw new \RuntimeException(sprintf('Could not read page %d of the invoice PDF.', $pageNumber));
            }

            $width = (float) $size['width'];
            $height = (float) $size['height'];
            $orientation = $size['orientation'];

            // Add a page with the same dimensions/orientation as the imported one
            // and place the original page content onto it (preserved verbatim via the
            // form XObject - no re-rendering of the invoice template).
            $pdf->AddPage($orientation, [$width, $height]);
            $pdf->useTemplate($templateId, 0, 0, $width, $height, false);

            $this->drawPaidSeal($pdf, $width, $height);
        }

        return $pdf->Output('S');
    }

    /**
     * Draw a faint diagonal "PAID" seal centered on the page using a PDF rotation
     * transform. The seal is intentionally barely visible (thin, light-red outline
     * and text) and ~30% smaller than the original stamp so it does not obscure
     * the invoice content.
     */
    private function drawPaidSeal(StampedPdf $pdf, float $width, float $height): void
    {
        $cx = $width / 2;
        $cy = $height / 2;

        // Light, low-contrast red so the seal is barely visible.
        $pdf->SetDrawColor(220, 160, 160);
        $pdf->SetTextColor(220, 160, 160);
        $pdf->SetLineWidth(0.4);

        // 30% smaller than the previous 180x50 box.
        $boxWidth = 126;
        $boxHeight = 35;

        $pdf->startTransform();
        $pdf->rotate(35, $cx, $cy);

        // Outer rectangle
        $pdf->Rect($cx - $boxWidth / 2, $cy - $boxHeight / 2, $boxWidth, $boxHeight, 'D');
        // Inner rectangle
        $pdf->SetLineWidth(0.2);
        $pdf->Rect($cx - $boxWidth / 2 + 3, $cy - $boxHeight / 2 + 3, $boxWidth - 6, $boxHeight - 6, 'D');

        // "PAID" text, centered in the box (sized down ~30%: 40 -> 28).
        $pdf->SetFont('Helvetica', 'B', 28);
        $pdf->SetXY($cx - $boxWidth / 2, $cy - 7);
        $pdf->Cell($boxWidth, 14, 'PAID', 0, 0, 'C');

        $pdf->stopTransform();
    }
}

/**
 * Minimal FPDI subclass that adds FPDF rotation/transform primitives (which the
 * core FPDF lacks) via raw PDF content-stream operators. Subclassing gives access
 * to the protected _out/properties needed to emit the rotation matrix.
 */
final class StampedPdf extends Fpdi
{
    /**
     * Begin a save/restore graphics state block (PDF 'q' operator).
     */
    public function startTransform(): void
    {
        $this->_out('q');
    }

    /**
     * End a save/restore graphics state block (PDF 'Q' operator).
     */
    public function stopTransform(): void
    {
        $this->_out('Q');
    }

    /**
     * Rotate the coordinate system by $angle degrees around the point ($x, $y).
     * Coordinates are in the current FPDF unit (millimetres by default).
     */
    public function rotate(float $angle, float $x, float $y): void
    {
        $k = (float) $this->k;
        $x = $x * $k;
        $y = ($this->h - $y) * $k;

        $angleRad = deg2rad($angle);
        $cos = cos($angleRad);
        $sin = sin($angleRad);

        $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.3F %.3F cm 1 0 0 1 %.3F %.3F cm', $cos, $sin, -$sin, $cos, $x, $y, -$x, -$y));
    }
}