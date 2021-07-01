<?php

namespace Phpactor\Extension\Navigation;

use Phpactor\ClassFileConverter\PathFinder;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\WorseReflection\WorseReflectionExtension;
use Phpactor\MapResolver\Resolver;
use Phpactor\Container\Container;
use Phpactor\Extension\Navigation\Application\Navigator;
use Phpactor\Extension\Navigation\Navigator\ChainNavigator;
use Phpactor\Extension\Navigation\Handler\NavigateHandler;
use Phpactor\Extension\Navigation\Navigator\PathFinderNavigator;
use Phpactor\Extension\Navigation\Navigator\WorseReflectionNavigator;

class NavigationExtension implements Extension
{
    const PATH_FINDER_DESTINATIONS = 'navigator.destinations';
    const NAVIGATOR_AUTOCREATE = 'navigator.autocreate';
    const SERVICE_PATH_FINDER = 'navigation.path_finder';

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container): void
    {
        $this->registerPathFinder($container);
        $this->registerNavigators($container);
        $this->registerRpc($container);
    }

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema): void
    {
        $schema->setDefaults([
            self::PATH_FINDER_DESTINATIONS => [],
            self::NAVIGATOR_AUTOCREATE => [],
        ]);
    }

    private function registerPathFinder(ContainerBuilder $container): void
    {
        $container->register(self::SERVICE_PATH_FINDER, function (Container $container) {
            return PathFinder::fromAbsoluteDestinations(
                $container->getParameter('file_path_resolver.project_root'),
                $container->getParameter(self::PATH_FINDER_DESTINATIONS
            ));
        });

        $container->register('application.navigator', function (Container $container) {
            return new Navigator(
                $container->get('navigation.navigator.chain'),
                $container->get('application.class_new'),
                $container->getParameter(self::NAVIGATOR_AUTOCREATE)
            );
        });
    }

    private function registerRpc(ContainerBuilder $container): void
    {
        $container->register('rpc.handler.navigate', function (Container $container) {
            return new NavigateHandler(
                $container->get('application.navigator')
            );
        }, [ 'rpc.handler' => ['name' => NavigateHandler::NAME] ]);
    }

    private function registerNavigators(ContainerBuilder $container): void
    {
        $container->register('navigation.navigator.chain', function (Container $container) {
            $navigators = [];
            foreach ($container->getServiceIdsForTag('navigation.navigator') as $serviceId => $attrs) {
                $navigators[] = $container->get($serviceId);
            }
        
            return new ChainNavigator($navigators);
        });
        $container->register('navigation.navigator.path_finder', function (Container $container) {
            return new PathFinderNavigator($container->get(self::SERVICE_PATH_FINDER));
        }, [ 'navigation.navigator' => [] ]);
        
        $container->register('navigation.navigator.worse_reflection', function (Container $container) {
            return new WorseReflectionNavigator($container->get(WorseReflectionExtension::SERVICE_REFLECTOR));
        }, [ 'navigation.navigator' => [] ]);
    }
}
