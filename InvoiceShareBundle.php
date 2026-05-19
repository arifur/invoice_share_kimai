<?php

/*
 * This file is part of the "Invoice-Share plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\InvoiceShareBundle;

use App\Plugin\PluginInterface;
use KimaiPlugin\InvoiceShareBundle\DependencyInjection\InvoiceShareCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class InvoiceShareBundle extends Bundle implements PluginInterface
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new InvoiceShareCompilerPass());
    }
}