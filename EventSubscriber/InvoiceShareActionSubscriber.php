<?php

/*
 * This file is part of the "Invoice-Share plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\InvoiceShareBundle\EventSubscriber;

use App\Event\PageActionsEvent;
use KimaiPlugin\InvoiceShareBundle\Repository\InvoiceShareRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InvoiceShareActionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly InvoiceShareRepository $invoiceShareRepository,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'actions.invoice' => ['onInvoiceActions', 100],
        ];
    }

    public function onInvoiceActions(PageActionsEvent $event): void
    {
        if (!$event->isIndexView()) {
            return;
        }

        $payload = $event->getPayload();
        $invoice = $payload['invoice'] ?? null;

        if ($invoice === null) {
            return;
        }

        $invoiceShare = $this->invoiceShareRepository->findByInvoice($invoice);
        $isPublic = $invoiceShare !== null && $invoiceShare->isPublic();

        // Add toggle public/private action
        $event->addDivider();

        $toggleUrl = $this->urlGenerator->generate('invoice_share_toggle', [
            'id' => $invoice->getId(),
        ]);

        $event->addAction('toggle_public', [
            'url' => $toggleUrl,
            'class' => 'api-link',
            'title' => $isPublic ? 'Set to private' : 'Set to public',
            'icon' => $isPublic ? 'lock' : 'globe',
            'attr' => [
                'data-event' => 'kimai.invoiceUpdate',
                'data-method' => 'GET',
                'data-msg-success' => 'Invoice visibility updated!',
                'data-msg-error' => 'Failed to update invoice visibility.',
            ],
        ]);

        // Only show view/copy actions if currently public
        if (!$isPublic) {
            return;
        }

        $publicUrl = $this->urlGenerator->generate('invoice_share_public', [
            'uuid' => $invoiceShare->getUuid(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $event->addAction('view_public_invoice', [
            'url' => $publicUrl,
            'target' => '_blank',
            'title' => 'View public invoice',
            'icon' => 'eye',
        ]);

        $event->addAction('copy_invoice_url', [
            'url' => $publicUrl,
            'onclick' => "event.preventDefault();event.stopPropagation();var t=document.createElement('textarea');t.value=this.href;document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);var c=document.getElementById('toast-container');if(c){var n=document.createElement('div');n.className='toast align-items-center text-bg-success border-0';n.setAttribute('role','alert');n.setAttribute('aria-live','assertive');n.setAttribute('aria-atomic','true');n.innerHTML='<div class=\"d-flex\"><div class=\"toast-body\">Invoice URL copied!</div><button type=\"button\" class=\"btn-close btn-close-white me-2 m-auto\" data-bs-dismiss=\"toast\" aria-label=\"Close\"></button></div>';c.appendChild(n);bootstrap.Toast.getOrCreateInstance(n).show()}return false;",
            'title' => 'Copy invoice URL',
        ]);
    }
}