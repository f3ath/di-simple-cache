<?php
declare(strict_types=1);

namespace Cekta\DISimpleCache\Test\Unit;

use Cekta\DISimpleCache\Autowire;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\CacheInterface;
use stdClass;

class AutowireTest extends TestCase
{

    public function testHas(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        assert($cache instanceof CacheInterface);
        $autowire = new Autowire($cache);
        self::assertTrue($autowire->hasProvide(stdClass::class));
        self::assertFalse($autowire->hasProvide('invalid name'));
    }

    public function testGetWithoutCache(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $cache->expects($this->once())->method('has')
            ->with(stdClass::class)
            ->willReturn(false);
        $cache->expects($this->once())->method('get')
            ->with(stdClass::class)
            ->willReturn([]);
        assert($cache instanceof CacheInterface);
        $autowire = new Autowire($cache);
        assert($container instanceof ContainerInterface);
        self::assertEquals(new stdClass(), $autowire->provide(stdClass::class, $container));
    }

    public function testGetWithDependeciesInCache(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $obj = new class(1)
        {
            public $a;

            public function __construct($a)
            {
                $this->a = $a;
            }

        };
        $name = get_class($obj);
        $cache->expects($this->once())->method('has')
            ->with($name)
            ->willReturn(true);
        $cache->expects($this->once())->method('get')
            ->with($name)
            ->willReturn(['a']);
        $container->expects($this->once())->method('get')
            ->with('a')
            ->willReturn(1234);
        assert($cache instanceof CacheInterface);
        $autowire = new Autowire($cache);
        assert($container instanceof ContainerInterface);
        $result = $autowire->provide($name, $container);
        self::assertTrue($result instanceof $name);
        self::assertSame(1234, $result->a);
    }

    public function testGetInvalidName()
    {
        $cache = $this->createMock(CacheInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Container `invalid name` not found');
        $cache->expects($this->once())->method('has')
            ->with('invalid name')
            ->willReturn(false);
        assert($cache instanceof CacheInterface);
        $autowire = new Autowire($cache);
        assert($container instanceof ContainerInterface);
        $autowire->provide('invalid name', $container);
    }
}
