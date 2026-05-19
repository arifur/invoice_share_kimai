<?php

/*
 * This file is part of the "Invoice-Share plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\InvoiceShareBundle\EventSubscriber;

use App\Event\InvoiceCreatedEvent;
use KimaiPlugin\InvoiceShareBundle\Entity\InvoiceShare;
use KimaiPlugin\InvoiceShareBundle\Repository\InvoiceShareRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InvoiceShareSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly InvoiceShareRepository $invoiceShareRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InvoiceCreatedEvent::class => 'onInvoiceCreated',
        ];
    }

    public function onInvoiceCreated(InvoiceCreatedEvent $event): void
    {
        $invoice = $event->getInvoice();

        $invoiceShare = new InvoiceShare();
        $invoiceShare->setInvoice($invoice);
        $invoiceShare->setUuid($this->generateUuid());
        $invoiceShare->setIsPublic(true);

        $endDate = new \DateTime();
        $endDate->modify('+6 months');
        $invoiceShare->setEndDate($endDate);

        $this->invoiceShareRepository->save($invoiceShare);
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);

        // Set version to 4 (random UUID)
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set variant to RFC 4122
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return bin2hex($data);
    }
}