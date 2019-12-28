<?php

namespace Icamys\SitemapGenerator;

use InvalidArgumentException;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

class SitemapGeneratorTest extends TestCase
{
    private $testDomain = 'example.com';

    /**
     * @var SitemapGenerator
     */
    private $g;

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
        $actual = $this->invokeMethod($this->g, 'getDiffInPercents', $args);
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

    public function testSetSitemapFilenameException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->g->setSitemapFilename('');
    }

    public function testSetSitemapIndexFilenameException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->g->setSitemapIndexFilename('');
    }

    public function testSetRobotsFileNameException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->g->setRobotsFileName('');
    }

    public function testSetMaxURLsPerSitemapLeftOutOfRangeException()
    {
        $this->expectException(OutOfRangeException::class);
        $this->g->setMaxURLsPerSitemap(0);
    }

    public function testSetMaxURLsPerSitemapRightOutOfRangeException()
    {
        $this->expectException(OutOfRangeException::class);
        $this->g->setMaxURLsPerSitemap(50001);
    }

    protected function setUp(): void
    {
        $this->g = new SitemapGenerator($this->testDomain);
    }

    protected function tearDown(): void
    {
        unset($this->g);
    }
}
