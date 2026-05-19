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
        private readonly InvoiceService $invoiceService
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

        // Try to get the invoice file (PDF)
        $invoiceFile = $this->invoiceService->getInvoiceFile($invoice);

        if ($invoiceFile !== null) {
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