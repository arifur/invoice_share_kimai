<?php

/*
 * This file is part of the "Invoice-Share plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\InvoiceShareBundle\Repository;

use App\Entity\Invoice;
use Doctrine\ORM\EntityRepository;
use KimaiPlugin\InvoiceShareBundle\Entity\InvoiceShare;

/**
 * @extends EntityRepository<InvoiceShare>
 */
class InvoiceShareRepository extends EntityRepository
{
    public function save(InvoiceShare $invoiceShare): void
    {
        $em = $this->getEntityManager();
        $em->persist($invoiceShare);
        $em->flush();
    }

    public function remove(InvoiceShare $invoiceShare): void
    {
        $em = $this->getEntityManager();
        $em->remove($invoiceShare);
        $em->flush();
    }

    public function findByUuid(string $uuid): ?InvoiceShare
    {
        return $this->createQueryBuilder('il')
            ->where('il.uuid = :uuid')
            ->setMaxResults(1)
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByInvoice(Invoice $invoice): ?InvoiceShare
    {
        return $this->createQueryBuilder('il')
            ->where('il.invoice = :invoice')
            ->setMaxResults(1)
            ->setParameter('invoice', $invoice)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Toggle is_public field directly in the database using native SQL.
     * Creates a new record if one does not exist (defaults to public).
     */
    public function toggleIsPublicByInvoiceId(int $invoiceId): bool
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        // First, get the current value
        $row = $conn->fetchAssociative(
            'SELECT id, is_public FROM kimai2_invoice_share WHERE invoice_id = :id LIMIT 1',
            ['id' => $invoiceId]
        );

        if ($row === false) {
            // No record exists - insert a new one with is_public = true
            $conn->executeStatement(
                'INSERT INTO kimai2_invoice_share (invoice_id, uuid, is_public) VALUES (:invoiceId, :uuid, 1)',
                [
                    'invoiceId' => $invoiceId,
                    'uuid' => md5(uniqid('invoice_share_', true)),
                ]
            );

            return true;
        }

        $newStatus = $row['is_public'] ? 0 : 1;

        $conn->executeStatement(
            'UPDATE kimai2_invoice_share SET is_public = :status WHERE id = :id',
            ['status' => $newStatus, 'id' => $row['id']]
        );

        return (bool) $newStatus;
    }
}