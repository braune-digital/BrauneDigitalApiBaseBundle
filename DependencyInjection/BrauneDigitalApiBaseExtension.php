<?php

namespace BrauneDigital\ApiBaseBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class BrauneDigitalApiBaseExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('braune_digital_api_base.modules', $config['modules']);
        $container->setParameter('braune_digital_api_base.timeout', $config['timeout']);
        $container->setParameter('braune_digital_api_base.features', $config['features']);
        $container->setParameter('braune_digital_api_base.features.serialization', array_key_exists('serialization', $config['features']) ? $config['features']['serialization'] : []);


        if(!$container->hasParameter('braune_digital_api_base.configuration')) {
            $container->setParameter('braune_digital_api_base.configuration', isset($config['configuration']) ? $config['configuration'] : array());
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}