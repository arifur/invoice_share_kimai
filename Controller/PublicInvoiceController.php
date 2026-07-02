<?php

/*
 * This file is part of the "Invoice-Share plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\InvoiceShareBundle\Controller;

use App\Invoice\InvoiceService;
use KimaiPlugin\InvoiceShareBundle\Entity\InvoiceShare;
use KimaiPlugin\InvoiceShareBundle\Repository\InvoiceShareRepository;
use KimaiPlugin\InvoiceShareBundle\Service\PaidStampService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class PublicInvoiceController extends AbstractController
{
    public function __construct(
        private readonly InvoiceShareRepository $invoiceShareRepository,
        private readonly InvoiceService $invoiceService,
        private readonly PaidStampService $paidStampService,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route(path: '/invoice/{id}/toggle', name: 'invoice_share_toggle', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function togglePublic(int $id): JsonResponse
    {
        $isPublic = $this->invoiceShareRepository->toggleIsPublicByInvoiceId($id);

        return new JsonResponse([
            'isPublic' => $isPublic,
            'success' => true,
        ]);
    }

    #[Route(path: '/invoice/{uuid}', name: 'invoice_share_public', methods: ['GET'], requirements: ['uuid' => '[a-f0-9]{32}'])]
    public function showInvoice(string $uuid): Response
    {
        $invoiceShare = $this->invoiceShareRepository->findByUuid($uuid);

        if ($invoiceShare === null) {
            throw $this->createNotFoundException('Invoice link not found.');
        }

        if (!$invoiceShare->isPublic()) {
            throw $this->createNotFoundException('Invoice link not found.');
        }

        if ($invoiceShare->getEndDate() !== null && $invoiceShare->getEndDate() < new \DateTime()) {
            throw $this->createNotFoundException('Invoice link has expired.');
        }

        $invoice = $invoiceShare->getInvoice();

        if ($invoice === null) {
            throw $this->createNotFoundException('Invoice link not found.');
        }

        // Try to get the invoice file (PDF)
        $invoiceFile = $this->invoiceService->getInvoiceFile($invoice);

        if ($invoiceFile !== null) {
            // When the invoice has been paid, stamp the PDF on the fly with a
            // "PAID" seal before serving it. The original file on disk is never
            // modified; we stamp a copy in memory and stream that back.
            if ($invoice->isPaid()) {
                try {
                    $stampedPdf = $this->paidStampService->stampPaid($invoiceFile->getRealPath());

                    $response = new Response($stampedPdf);
                    $response->headers->set('Content-Type', 'application/pdf');
                    $response->headers->set(
                        'Content-Disposition',
                        ResponseHeaderBag::DISPOSITION_INLINE . '; filename="' . $invoiceFile->getBasename() . '"'
                    );

                    return $response;
                } catch (\Throwable $e) {
                    // Stamp failed (e.g. encrypted/protected source PDF, PDF/A that
                    // disallows transparency, or missing mbstring PHP extension that
                    // mPDF requires). Log the cause so it is diagnosable, then fall
                    // back to the HTML view which clearly shows the PAID status so
                    // the client is never left looking at an unpaid-looking invoice.
                    $this->logger->warning(
                        'Failed to stamp invoice {invoice} PDF as paid; showing the HTML PAID view instead: {error}',
                        ['invoice' => $invoice->getId(), 'error' => $e->getMessage(), 'exception' => $e]
                    );

                    return $this->render('@InvoiceShare/public/invoice.html.twig', [
                        'invoice' => $invoice,
                        'invoiceShare' => $invoiceShare,
                    ]);
                }
            }

            $response = new BinaryFileResponse($invoiceFile->getRealPath());
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_INLINE,
                $invoiceFile->getBasename()
            );

            return $response;
        }

        // If no file, render HTML view with invoice details
        return $this->render('@InvoiceShare/public/invoice.html.twig', [
            'invoice' => $invoice,
            'invoiceShare' => $invoiceShare,
        ]);
    }
}