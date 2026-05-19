<?php

/*
 * This file is part of the "Invoice-Share plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\InvoiceShareBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class InvoiceShareCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $pluginDir = $container->getParameter('kernel.project_dir') . '/var/plugins/InvoiceShareBundle/Resources/views';

        $twigLoaderDefinition = $container->getDefinition('twig.loader.native_filesystem');

        // Prepend the plugin views directory with the highest priority
        // Using the "!" prefix means it's a path that should be checked BEFORE the normal paths
        $twigLoaderDefinition->addMethodCall('prependPath', [$pluginDir]);
    }
}