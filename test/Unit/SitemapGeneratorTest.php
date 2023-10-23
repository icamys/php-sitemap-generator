<?php

namespace Unit;

use BadMethodCallException;
use DateTime;
use Icamys\SitemapGenerator\Config;
use Icamys\SitemapGenerator\FileSystem;
use Icamys\SitemapGenerator\Runtime;
use Icamys\SitemapGenerator\SitemapGenerator;
use InvalidArgumentException;
use OutOfRangeException;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

class SitemapGeneratorTest extends TestCase
{
    use PHPMock;

    /**
     * @var SitemapGenerator
     */
    private $g;

    /**
     * @var FileSystem
     */
    private $fs;

    /**
     * @var Runtime
     */
    private $runtime;

    /**
     * @var DateTime current datetime
     */
    private $now;

    /**
     * Call protected/private method of a class.
     * @param object &$object Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array $parameters Array of parameters to pass into method.
     * @return mixed Method return.
     * @throws ReflectionException
     */
    public function invokeMethod(object $object, string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function testSetSitemapFilenameExceptionWhenEmptyFilenamePassed()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->g->setSitemapFilename();
    }

    public function testSetSitemapFilenameExceptionWhenInvalidExtensionPassed()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->g->setSitemapFilename('doc.pdf');
    }

    public function testCompressionStates()
    {
        $this->assertFalse($this->g->isCompressionEnabled());
        $this->g->enableCompression();
        $this->assertTrue($this->g->isCompressionEnabled());
        $this->g->disableCompression();
        $this->assertFalse($this->g->isCompressionEnabled());
    }

    public function testSetSitemapIndexFilenameException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->g->setSitemapIndexFilename();
    }

    public function testFinalizeExceptionIfNoUrlsAdded()
    {
        $this->expectException(RuntimeException::class);
        $this->g->finalize();
    }

    public function testSetRobotsFileNameException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->g->setRobotsFileName('');
    }

    public function testSetRobotsExceptionWhenFinalizeWasNotCalled()
    {
        $this->expectException(BadMethodCallException::class);
        $this->g->updateRobots();
    }

    public function testSubmitSitemapExceptionBeforeAddedUrls()
    {
        $this->expectException(BadMethodCallException::class);
        $this->g->submitSitemap();
    }

    public function testSetRobotsFileName()
    {
        $return = $this->g->setRobotsFileName('robots.txt');
        $this->assertEquals($this->g, $return);
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

    public function testAddURLWithInvalidChangeFreq()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->g->addURL('/product/', $this->now, 'INVALID_CHANGEFREQ', 0.8);
    }

    public function testAddURLWithInvalidPriority()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->g->addURL('/product/', $this->now, 'always', 1.11);
    }

    public function testAddTooLargeUrl()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->g->addURL(str_repeat('c', 5000), $this->now, 'always', 0.8);
    }

    public function testUpdateRobotsNoSitemapsException()
    {
        $this->expectException(BadMethodCallException::class);
        $this->g->updateRobots();
    }

    public function testSubmitSitemapExceptionOnEmptySitemaps()
    {
        $this->expectException(BadMethodCallException::class);
        $this->g->submitSitemap();
    }

    public function testSetSitemapStylesheetThrowExceptionWhenEmptyPathPassed()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('sitemap stylesheet path should not be empty');
        $this->g->setSitemapStylesheet("");
    }

    public function testSubmitSitemapExceptionWhenCurlResourceInitFails()
    {
        $this->g->addURL("/path/to/page-1/");
        $this->g->flush();
        $this->g->finalize();

        $this->runtime->expects(self::any())
            ->method('extension_loaded')
            ->with('curl')
            ->willReturn(true);

        $this->runtime->expects(self::any())
            ->method('curl_init')
            ->with(self::anything())
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->g->submitSitemap();
    }

    public function testSubmitSitemapExceptionWhenCurlSetOptFails()
    {
        $this->g->addURL("/path/to/page-1/");
        $this->g->flush();
        $this->g->finalize();

        $curlHandle = curl_init();

        $this->runtime->expects(self::any())
            ->method('extension_loaded')
            ->with('curl')
            ->willReturn(true);

        $this->runtime->expects(self::any())
            ->method('curl_init')
            ->with(self::anything())
            ->willReturn($curlHandle);

        $this->runtime->expects(self::any())
            ->method('curl_setopt')
            ->with(self::anything(), CURLOPT_RETURNTRANSFER, true)
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->g->submitSitemap();
    }

    public function testSubmitSitemapExceptionWhenCurlExecFails()
    {
        $this->g->addURL("/path/to/page-1/");
        $this->g->flush();
        $this->g->finalize();

        $curlHandle = curl_init();

        $this->runtime->expects(self::any())
            ->method('extension_loaded')
            ->with('curl')
            ->willReturn(true);

        $this->runtime->expects(self::any())
            ->method('curl_init')
            ->with(self::anything())
            ->willReturn($curlHandle);

        $this->runtime->expects(self::any())
            ->method('curl_setopt')
            ->with(self::anything(), CURLOPT_RETURNTRANSFER, true)
            ->willReturn(true);

        $this->runtime->expects(self::any())
            ->method('curl_exec')
            ->with(self::anything())
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->g->submitSitemap();
    }

    public function robotsContentProvider()
    {
        return [
            'withExistingContent_NoSitemapURL' => [
                'file_get_contents_return' => 'User-agent: *',
                'file_exists_return' => true,
            ],
            'withExistingContent_WithSitemapURL' => [
                'file_get_contents_return' => "Sitemap: http://example.com/sitemap.xml\nUser-agent: *",
                'file_exists_return' => true,
            ],
            'withoutExistingContent' => [
                'file_get_contents_return' => true,
                'file_exists_return' => true,
            ],
        ];
    }

    /**
     * @dataProvider robotsContentProvider
     */
    public function testCreateNewRobotsContentFromFile($file_get_contents_return, $file_exists_return)
    {
        $this->fs->expects($this->once())
            ->method('file_get_contents')
            ->willReturn($file_get_contents_return);

        $this->fs->expects($this->exactly(3))
            ->method('file_put_contents')
            ->willReturnCallback(function ($filename, $content, $flags) {
                $sitemapFilenameMatcher = $this->stringContains('sm-0-');
                $sitemapContentsHeaderMatcher = $this->stringContains('<?xml version="1.0" encoding="UTF-8"?>');
                $sitemapContentsCloserMatcher = $this->stringContains('</urlset>');
                $robotsFilenameMatcher = $this->stringContains('robots.txt');
                $robotsContentMatcher = $this->stringContains("User-Agent");

                return match ([$filename, $content, $flags]) {
                    [$sitemapFilenameMatcher, $sitemapContentsHeaderMatcher, FILE_APPEND] => true,
                    [$sitemapFilenameMatcher, $sitemapContentsCloserMatcher, FILE_APPEND] => true,
                    [$robotsFilenameMatcher, $robotsContentMatcher, FILE_APPEND] => true,
                    default => false,
                };
            });

        $this->fs->expects($this->once())
            ->method('file_exists')
            ->willReturn($file_exists_return);

        $this->g->addURL('/product/', $this->now, 'always', 0.8);
        $this->g->flush();
        $this->g->finalize();

        $this->g->updateRobots();
    }

    public function testCreateNewRobotsContentFromFile_withoutExistingContent()
    {
        $this->fs->expects($this->once())
            ->method('file_get_contents')
            ->willReturn(true);

        $this->fs->expects($this->exactly(3))
            ->method('file_put_contents')
            ->willReturnCallback(function ($filename, $content, $flags) {
                $sitemapFilenameMatcher = $this->stringContains('sm-0-');
                $sitemapContentsHeaderMatcher = $this->stringContains('<?xml version="1.0" encoding="UTF-8"?>');
                $sitemapContentsCloserMatcher = $this->stringContains('</urlset>');
                $robotsFilenameMatcher = $this->stringContains('robots.txt');
                $robotsContentMatcher = $this->stringContains("User-Agent");

                return match ([$filename, $content, $flags]) {
                    [$sitemapFilenameMatcher, $sitemapContentsHeaderMatcher, FILE_APPEND] => true,
                    [$sitemapFilenameMatcher, $sitemapContentsCloserMatcher, FILE_APPEND] => true,
                    [$robotsFilenameMatcher, $robotsContentMatcher, FILE_APPEND] => true,
                    default => false,
                };
            });

        $this->fs->expects($this->once())
            ->method('file_exists')
            ->willReturn(true);

        $this->g->addURL('/product/', $this->now, 'always', 0.8);
        $this->g->flush();
        $this->g->finalize();

        $this->g->updateRobots();
    }

    public function testCreateNewRobotsContentFromFile_invalidFileGetContentsResponse()
    {
        $this->expectExceptionMessage('Failed to read existing robots.txt file: robots.txt');

        $this->fs->expects($this->once())
            ->method('file_get_contents')
            ->willReturn(false);

        $this->fs->expects($this->exactly(2))
            ->method('file_put_contents')
            ->willReturnCallback(function ($filename, $content, $flags) {
                $sitemapFilenameMatcher = $this->stringContains('sm-0-');
                $sitemapContentsHeaderMatcher = $this->stringContains('<?xml version="1.0" encoding="UTF-8"?>');
                $sitemapContentsCloserMatcher = $this->stringContains('</urlset>');

                return match ([$filename, $content, $flags]) {
                    [$sitemapFilenameMatcher, $sitemapContentsHeaderMatcher, FILE_APPEND] => true,
                    [$sitemapFilenameMatcher, $sitemapContentsCloserMatcher, FILE_APPEND] => true,
                    default => false,
                };
            });

        $this->fs->expects($this->once())
            ->method('file_exists')
            ->willReturn(true);

        $this->g->addURL('/product/', $this->now, 'always', 0.8);
        $this->g->flush();
        $this->g->finalize();

        $this->g->updateRobots();
    }

    protected function setUp(): void
    {
        $this->fs = $this->createMock(FileSystem::class);
        $this->runtime = $this->createMock(Runtime::class);
        $this->runtime
            ->expects($this->once())
            ->method('is_writable')
            ->willReturn(true);

        $config = new Config();
        $config->setBaseURL('http://example.com');
        $config->setFS($this->fs);
        $config->setRuntime($this->runtime);

        $this->g = new SitemapGenerator($config);
        $this->now = new DateTime();
    }

    protected function tearDown(): void
    {
        unset($this->fs);
        unset($this->runtime);
        unset($this->g);
    }
}
