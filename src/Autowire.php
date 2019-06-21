<?php
declare(strict_types=1);

namespace Cekta\DISimpleCache;

use Cekta\DI\Exception\NotFound;
use Cekta\DI\Provider\Autowire\ReflectionClass;
use Cekta\DI\ProviderInterface;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class Autowire implements ProviderInterface
{
    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function provide(string $name, ContainerInterface $container)
    {
        try {
            if (!$this->cache->has($name)) {
                $class = new ReflectionClass($name);
                $this->cache->set($name, $class->readDependecies());
            }
            $args = [];
            foreach ($this->cache->get($name) as $dependency) {
                $args[] = $container->get($dependency);
            }
            return new $name(...$args);
        } catch (ReflectionException | InvalidArgumentException $e) {
            throw new NotFound($name);
        }

    }

    public function hasProvide(string $name): bool
    {
        return class_exists($name);
    }
}
