<?php

namespace Bundle\I18nRoutingBundle\Routing\Loader;

use Symfony\Component\Routing\Loader\YamlFileLoader as BaseYamlFileLoader,
    Symfony\Component\Routing\RouteCollection,
    Symfony\Component\Config\Resource\FileResource,
    Bundle\I18nRoutingBundle\Routing as Routing;

class YamlFileLoader extends BaseYamlFileLoader
{
    /**
     * Loads a Yaml file.
     *
     * @param string $file A Yaml file path
     * @param string $type The resource type
     *
     * @return RouteCollection A RouteCollection instance
     *
     * @throws \InvalidArgumentException When route can't be parsed
     */
    public function load($file, $type = null)
    {
        $path = $this->locator->locate($file);

        $config = $this->loadFile($path);

        $collection = new RouteCollection();
        $collection->addResource(new FileResource($path));

        // empty file
        if (null === $config) {
            $config = array();
        }

        // not an array
        if (!is_array($config)) {
            throw new \InvalidArgumentException(sprintf('The file "%s" must contain a YAML array.', $file));
        }

        foreach ($config as $name => $config) {
            if (isset($config['resource'])) {
                $type = isset($config['type']) ? $config['type'] : null;
                $prefix = isset($config['prefix']) ? $config['prefix'] : null;
                $this->currentDir = dirname($path);
                $file = $this->locator->locate($config['resource'], $this->currentDir);
                $collection->addCollection($this->import($file, $type), $prefix);
            } elseif (isset($config['pattern']) || isset($config['locales'])) {
                $this->parseRoute($collection, $name, $config, $file);
            } else {
                throw new \InvalidArgumentException(sprintf('Unable to parse the "%s" route.', $name));
            }
        }

        return $collection;
    }

    /**
     * @param RouteCollection $collection The collection routes
     * @param string          $name       The route name
     * @param array           $config     The config options
     * @param string          $file       A Yaml file path
     *
     * @throws \InvalidArgumentException When config pattern is not defined for the given route
     */
    protected function parseRoute(RouteCollection $collection, $name, $config, $file)
    {
        $defaults = isset($config['defaults']) ? $config['defaults'] : array();
        $requirements = isset($config['requirements']) ? $config['requirements'] : array();
        $options = isset($config['options']) ? $config['options'] : array();

        if (isset($config['locales'])) {
            $route = new Routing\I18nRoute($name, $config['locales'], $defaults, $requirements, $options);

            $collection->addCollection($route->getCollection());
        } elseif (isset($config['pattern'])) {
            $route = new Routing\Route($config['pattern'], $defaults, $requirements, $options);

            $collection->add($name, $route);
        } else {
            throw new \InvalidArgumentException(sprintf('You must define a "pattern" for the "%s" route.', $name));
        }
    }
}