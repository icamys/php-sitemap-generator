<?php

namespace Icamys\SitemapGenerator;

use BadMethodCallException;
use DateTime;
use InvalidArgumentException;
use OutOfRangeException;
use phpmock\phpunit\PHPMock;
use phpmock\spy\Spy;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

class SitemapGeneratorTest extends TestCase
{
    use PHPMock;

    private $testDomain = 'example.com';

    /**
     * @var SitemapGenerator
     */
    private $g;

    /**
     * @var Spy for file_put_contents function
     */
    private $filePutContentsSpy;

    /**
     * @var Spy for gzopen function
     */
    private $gzopenSpy;

    /**
     * @var Spy for gzwrite function
     */
    private $gzwriteSpy;

    /**
     * @var Spy for gzclose function
     */
    private $gzcloseSpy;

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

    public function testAddURL()
    {
        $now = new \DateTime();
        $nowStr = $now->format('Y-m-d\TH:i:sP');
        for ($i = 0; $i < 2; $i++) {
            $this->g->addURL('/product-'.$i . '/', $now, 'always', '0.8' );
        }
        $urlArray = $this->g->getURLsArray();

        $this->assertCount(2, $urlArray);
        $this->assertEquals('/product-0/', $urlArray[0][$this->g::ATTR_NAME_LOC]);
        $this->assertEquals($nowStr, $urlArray[0][$this->g::ATTR_NAME_LASTMOD]);
        $this->assertEquals('always', $urlArray[0][$this->g::ATTR_NAME_CHANGEFREQ]);
        $this->assertEquals('0.8', $urlArray[0][$this->g::ATTR_NAME_PRIORITY]);
        $this->assertEquals('/product-1/', $urlArray[1][$this->g::ATTR_NAME_LOC]);
    }

    public function testAddURLWithAlternates()
    {
        $alternates = [
            ['hreflang' => 'de', 'href' => "http://www.example.com/de"],
            ['hreflang' => 'fr', 'href' => "http://www.example.com/fr"],
        ];
        $now = new \DateTime();
        $nowStr = $now->format('Y-m-d\TH:i:sP');
        $this->g->addURL('/product-0/', $now, 'always', '0.8' , $alternates);
        $urlArray = $this->g->getURLsArray();
        $this->assertCount(1, $urlArray);
        $this->assertEquals('/product-0/', $urlArray[0][$this->g::ATTR_NAME_LOC]);
        $this->assertEquals($nowStr, $urlArray[0][$this->g::ATTR_NAME_LASTMOD]);
        $this->assertEquals('always', $urlArray[0][$this->g::ATTR_NAME_CHANGEFREQ]);
        $this->assertEquals('0.8', $urlArray[0][$this->g::ATTR_NAME_PRIORITY]);
        $this->assertCount(2, $urlArray[0][$this->g::ATTR_NAME_ALTERNATES]);
        $this->assertCount(2, $urlArray[0][$this->g::ATTR_NAME_ALTERNATES][0]);
        $this->assertEquals('de', $urlArray[0][$this->g::ATTR_NAME_ALTERNATES][0]['hreflang']);
        $this->assertEquals('http://www.example.com/de', $urlArray[0][$this->g::ATTR_NAME_ALTERNATES][0]['href']);
        $this->assertEquals('fr', $urlArray[0][$this->g::ATTR_NAME_ALTERNATES][1]['hreflang']);
        $this->assertEquals('http://www.example.com/fr', $urlArray[0][$this->g::ATTR_NAME_ALTERNATES][1]['href']);
    }

    public function testAddURLInvalidLocException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->g->addURL('', new \DateTime(), 'always', '0.8' );
    }

    public function testAddURLTooLongException()
    {
        $this->expectException(InvalidArgumentException::class);
        $url = str_repeat("s", 5000);
        $this->g->addURL($url, new \DateTime(), 'always', '0.8' );
    }

    public function testWriteSitemapBadMethodCallException()
    {
        $this->expectException(BadMethodCallException::class);
        $this->g->writeSitemap();
    }

    public function testWriteSitemapWithManySitemaps()
    {
        $this->g->setMaxURLsPerSitemap(1);
        $this->g->setSitemapFilename("sitemap.xml");
        $this->g->setSitemapIndexFilename("sitemap-index.xml");
        $this->g->addURL('/product-1/', new DateTime(), 'always', '0.8' );
        $this->g->addURL('/product-2/', new DateTime(), 'always', '0.8' );
        $this->g->createSitemap();
        $this->g->writeSitemap();

        $this->assertCount(3, $this->filePutContentsSpy->getInvocations());
        $this->assertEquals('sitemap-index.xml', $this->filePutContentsSpy->getInvocations()[0]->getArguments()[0]);
        $this->assertStringStartsWith('<?xml ', $this->filePutContentsSpy->getInvocations()[0]->getArguments()[1]);
        $this->assertEquals('sitemap1.xml', $this->filePutContentsSpy->getInvocations()[1]->getArguments()[0]);
        $this->assertStringStartsWith('<?xml ', $this->filePutContentsSpy->getInvocations()[1]->getArguments()[1]);
        $this->assertEquals('sitemap2.xml', $this->filePutContentsSpy->getInvocations()[2]->getArguments()[0]);
        $this->assertStringStartsWith('<?xml ', $this->filePutContentsSpy->getInvocations()[2]->getArguments()[1]);
        $this->assertCount(1, $this->gzopenSpy->getInvocations());
        $this->assertCount(1, $this->gzwriteSpy->getInvocations());
        $this->assertCount(1, $this->gzcloseSpy->getInvocations());
    }

    public function testWriteSitemapWithSingleSitemap()
    {
        $this->g->setMaxURLsPerSitemap(1);
        $this->g->setSitemapFilename("sitemap.xml");
        $this->g->setSitemapIndexFilename("sitemap-index.xml");
        $this->g->addURL('/product-1/', new DateTime(), 'always', '0.8' );
        $this->g->createSitemap();
        $this->g->writeSitemap();

        $this->assertCount(1, $this->filePutContentsSpy->getInvocations());
        $this->assertEquals('sitemap.xml', $this->filePutContentsSpy->getInvocations()[0]->getArguments()[0]);
        $this->assertStringStartsWith('<?xml ', $this->filePutContentsSpy->getInvocations()[0]->getArguments()[1]);
        $this->assertCount(1, $this->gzopenSpy->getInvocations());
        $this->assertCount(1, $this->gzwriteSpy->getInvocations());
        $this->assertCount(1, $this->gzcloseSpy->getInvocations());
    }

    protected function setUp(): void
    {
        $this->g = new SitemapGenerator($this->testDomain);
        $this->filePutContentsSpy = new Spy(__NAMESPACE__, "file_put_contents", function (){});
        $this->filePutContentsSpy->enable();
        $this->gzopenSpy = new Spy(__NAMESPACE__, "gzopen", function (){});
        $this->gzopenSpy->enable();
        $this->gzwriteSpy = new Spy(__NAMESPACE__, "gzwrite", function (){});
        $this->gzwriteSpy->enable();
        $this->gzcloseSpy = new Spy(__NAMESPACE__, "gzclose", function (){});
        $this->gzcloseSpy->enable();
    }

    protected function tearDown(): void
    {
        unset($this->g);
        $this->filePutContentsSpy->disable();
        $this->gzopenSpy->disable();
        $this->gzwriteSpy->disable();
        $this->gzcloseSpy->disable();
    }
}
