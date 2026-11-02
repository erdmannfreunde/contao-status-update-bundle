<?php

declare(strict_types=1);

/*
 * This file is part of erdmannfreunde/contao-status-update-bundle.
 *
 * (c) Erdmann & Freunde <https://erdmann-freunde.de>
 *
 * @license LGPL-3.0-or-later
 */

namespace ErdmannFreunde\ContaoStatusUpdateBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('erdmannfreunde_contao_status_update');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('feed_url')
            ->defaultNull()
            ->info('HTTPS-URL eines Status-Update-Feeds (contao-status-update-feed-bundle). Leer = kein Abo.')
            ->validate()
            ->ifTrue(static fn ($url): bool => null !== $url && !str_starts_with((string) $url, 'https://'))
            ->thenInvalid('Die feed_url muss mit https:// beginnen (angegeben: %s).')
            ->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
