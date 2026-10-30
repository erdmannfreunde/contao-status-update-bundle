<?php

declare(strict_types=1);

namespace ErdmannFreunde\ContaoStatusUpdateBundle\DependencyInjection;

use ErdmannFreunde\ContaoStatusUpdateBundle\Feed\FeedSynchronizer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ErdmannFreundeContaoStatusUpdateExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');

        $container->getDefinition(FeedSynchronizer::class)->setArgument('$feedUrl', $config['feed_url']);
    }

    public function getAlias(): string
    {
        return 'erdmannfreunde_contao_status_update';
    }
}
