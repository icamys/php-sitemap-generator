<?php

namespace Icamys\SitemapGenerator;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

class SitemapGeneratorTest extends TestCase
{
    public function getSizeDiffInPercentsProvider()
    {
        return [
            ['args' => [100, 90], 'expected' => -10],
            ['args' => [100, 110], 'expected' => 10],
            ['args' => [200, 100], 'expected' => -50],
        ];
    }

    /**
     * @dataProvider getSizeDiffInPercentsProvider
     * @throws ReflectionException
     */
    public function testGetSizeDiffInPercents($args, $expected)
    {
        $g = new SitemapGenerator('example.com');
        $actual = $this->invokeMethod($g, 'getDiffInPercents', $args);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Call protected/private method of a class.
     * @param object &$object Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array $parameters Array of parameters to pass into method.
     * @return mixed Method return.
     * @throws ReflectionException
     */
    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
