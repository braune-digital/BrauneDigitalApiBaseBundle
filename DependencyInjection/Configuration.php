<?php

namespace BrauneDigital\ApiBaseBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('braune_digital_api_base');

        $rootNode->children()
			->arrayNode('features')
				->children()
					->booleanNode('use_token_relation')
						->defaultValue(false)
					->end()
                    ->arrayNode('serialization')
                        ->children()
                            ->booleanNode('route_as_default')
                                ->defaultValue(false)
                            ->end()
                            ->scalarNode('custom_groups_key')
                                ->defaultValue('serializationGroups')
                            ->end()
                            ->booleanNode('allow_custom_groups')
                                ->defaultValue(true)
                            ->end()
                            ->arrayNode('default_groups')
                                ->prototype('scalar')->end()
                                ->defaultValue(array('Default')) //default JMS Serialization Group => include properties which got no groups defined
                        ->end()
                        ->end()
                    ->end()

				->end()
			->end()
			->integerNode('timeout')
				->defaultValue(0)
			->end()
		->end();


        $rootNode->children()->variableNode('configuration');

        $this->addModulesSection($rootNode);

        return $treeBuilder;
    }

    /**
     * add modules configuration
     * @param ArrayNodeDefinition $rootNode
     */
    protected function addModulesSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('modules')
                    ->useAttributeAsKey('module')
                    ->normalizeKeys(false)
                    ->prototype('array')
                        ->children()
                            ->arrayNode('roles')
                                ->prototype('scalar')->end()
                                ->defaultValue(array())
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
