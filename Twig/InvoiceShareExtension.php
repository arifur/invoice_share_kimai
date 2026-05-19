<?php

/*
 * This file is part of the "Invoice-Share plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\InvoiceShareBundle\Twig;

use App\Entity\Invoice;
use App\Utils\DataTable;
use KimaiPlugin\InvoiceShareBundle\Repository\InvoiceShareRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class InvoiceShareExtension extends AbstractExtension
{
    public function __construct(
        private readonly InvoiceShareRepository $invoiceShareRepository,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('invoice_share_url', [$this, 'getInvoiceShareUrl']),
            new TwigFunction('invoice_share_add_column', [$this, 'addColumnToDataTable']),
            new TwigFunction('invoice_share_is_public', [$this, 'isInvoiceShare']),
        ];
    }

    public function getInvoiceShareUrl(Invoice $invoice): string
    {
        $invoiceShare = $this->invoiceShareRepository->findByInvoice($invoice);

        if ($invoiceShare === null) {
            return 'N/A';
        }

        if (!$invoiceShare->isPublic()) {
            return 'N/A';
        }

        return $this->urlGenerator->generate('invoice_share_public', [
            'uuid' => $invoiceShare->getUuid(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function isInvoiceShare(Invoice $invoice): ?bool
    {
        $invoiceShare = $this->invoiceShareRepository->findByInvoice($invoice);

        if ($invoiceShare === null) {
            return null;
        }

        return $invoiceShare->isPublic();
    }

    public function addColumnToDataTable(DataTable $dataTable): void
    {
        $columns = $dataTable->getColumns();

        // Extract the actions column so we can insert our column before it
        $actionsColumn = $columns['actions'] ?? null;
        unset($columns['actions']);

        // Add our Public column
        $columns['invoice_share'] = [
            'class' => 'd-none d-md-table-cell w-min',
            'title' => 'Public',
            'orderBy' => false,
        ];

        // Re-add actions column at the end
        if ($actionsColumn !== null) {
            $columns['actions'] = $actionsColumn;
        }

        $dataTable->setColumns($columns);
    }
}