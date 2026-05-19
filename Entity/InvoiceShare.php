<?php

/*
 * This file is part of the "Invoice-Share plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\InvoiceShareBundle\Entity;

use App\Entity\Invoice;
use Doctrine\ORM\Mapping as ORM;
use KimaiPlugin\InvoiceShareBundle\Repository\InvoiceShareRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Table(name: 'kimai2_invoice_share')]
#[ORM\UniqueConstraint(columns: ['uuid'])]
#[ORM\Index(columns: ['invoice_id'])]
#[ORM\Entity(repositoryClass: InvoiceShareRepository::class)]
#[UniqueEntity(fields: ['uuid'])]
class InvoiceShare
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Invoice::class)]
    #[ORM\JoinColumn(name: 'invoice_id', nullable: false, onDelete: 'CASCADE')]
    private ?Invoice $invoice = null;

    #[ORM\Column(name: 'uuid', type: 'string', length: 32, nullable: false)]
    private ?string $uuid = null;

    #[ORM\Column(name: 'end_date', type: 'datetime', nullable: true)]
    private ?\DateTime $endDate = null;

    #[ORM\Column(name: 'is_public', type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $isPublic = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(Invoice $invoice): void
    {
        $this->invoice = $invoice;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTime $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): void
    {
        $this->isPublic = $isPublic;
    }
}