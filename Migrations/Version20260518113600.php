<?php

/*
 * This file is part of the "Invoice-Share plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\InvoiceShareBundle\Migrations;

use App\Doctrine\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

/**
 * @version 1.0.0
 */
final class Version20260518113600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create kimai2_invoice_share table for the Invoice Share plugin';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('kimai2_invoice_share');

        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('invoice_id', Types::INTEGER, ['notnull' => true]);
        $table->addColumn('uuid', Types::STRING, ['length' => 32, 'notnull' => true]);
        $table->addColumn('end_date', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $table->addColumn('is_public', Types::BOOLEAN, ['default' => true, 'notnull' => true]);

        $table->addForeignKeyConstraint('kimai2_invoices', ['invoice_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_INVOICE_SHARE_INVOICE');

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['uuid'], 'UNIQ_INVOICE_SHARE_UUID');
        $table->addIndex(['invoice_id'], 'IDX_INVOICE_SHARE_INVOICE_ID');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('kimai2_invoice_share');
    }
}