<?php

/*
 * This file is part of the "Invoice-Share plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\InvoiceShareBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class InvoiceShareExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        // Add PUBLIC_ACCESS for the public invoice link route.
        // prependExtensionConfig adds this before the main security.yaml,
        // and access_control rules are evaluated in order (first match wins).
        $container->prependExtensionConfig('security', [
            'access_control' => [
                ['path' => '^/invoice/[a-f0-9]{32}$', 'roles' => 'PUBLIC_ACCESS'],
            ],
        ]);
    }
}