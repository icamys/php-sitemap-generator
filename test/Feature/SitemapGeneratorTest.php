<?php

use Icamys\SitemapGenerator\SitemapGenerator;
use PHPUnit\Framework\TestCase;

class SitemapGeneratorTest extends TestCase
{
    private $saveDir = './test/Feature';

    public function testSingleSitemapWithDefaultValues()
    {
        $siteUrl = 'https://example.com';
        $outputDir = $this->saveDir;

        $generator = new SitemapGenerator($siteUrl, $outputDir);
        $alternates = [
            ['hreflang' => 'de', 'href' => "http://www.example.com/de"],
            ['hreflang' => 'fr', 'href' => "http://www.example.com/fr"],
        ];

        $datetimeStr = '2020-12-29T08:46:55+00:00';
        $lastmod = new DateTime('2020-12-29T08:46:55+00:00');

        for ($i = 0; $i < 2; $i++) {
            $generator->addURL("/path/to/page-$i/", $lastmod, 'always', 0.5, $alternates);
        }

        $generator->flush();
        $generator->finalize();

        $sitemapFilepath = $this->saveDir . '/sitemap.xml';
        $this->assertFileExists($sitemapFilepath);

        $sitemap = new SimpleXMLElement(file_get_contents($sitemapFilepath));
        $this->assertEquals('urlset', $sitemap->getName());
        $this->assertEquals(2, $sitemap->count());

        $ns = $sitemap->getNamespaces();
        $this->assertEquals('http://www.w3.org/2001/XMLSchema-instance', $ns['xsi']);
        $this->assertEquals('http://www.sitemaps.org/schemas/sitemap/0.9', array_shift($ns));

        $this->assertEquals('https://example.com/path/to/page-0/', $sitemap->url[0]->loc);
        $this->assertEquals($datetimeStr, $sitemap->url[0]->lastmod);
        $this->assertEquals('always', $sitemap->url[0]->changefreq);
        $this->assertEquals('0.5', $sitemap->url[0]->priority);
        $this->assertEquals('alternate', $sitemap->url[0]->link[0]->attributes()['rel']);
        $this->assertEquals('de', $sitemap->url[0]->link[0]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/de', $sitemap->url[0]->link[0]->attributes()['href']);
        $this->assertEquals('alternate', $sitemap->url[0]->link[1]->attributes()['rel']);
        $this->assertEquals('fr', $sitemap->url[0]->link[1]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/fr', $sitemap->url[0]->link[1]->attributes()['href']);

        $this->assertEquals('https://example.com/path/to/page-1/', $sitemap->url[1]->loc);
        $this->assertEquals($datetimeStr, $sitemap->url[1]->lastmod);
        $this->assertEquals('always', $sitemap->url[1]->changefreq);
        $this->assertEquals('0.5', $sitemap->url[1]->priority);
        $this->assertEquals('alternate', $sitemap->url[1]->link[0]->attributes()['rel']);
        $this->assertEquals('de', $sitemap->url[1]->link[0]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/de', $sitemap->url[1]->link[0]->attributes()['href']);
        $this->assertEquals('alternate', $sitemap->url[1]->link[1]->attributes()['rel']);
        $this->assertEquals('fr', $sitemap->url[1]->link[1]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/fr', $sitemap->url[1]->link[1]->attributes()['href']);
        unlink($sitemapFilepath);
    }

    public function testSingleSitemapWithCustomSitemapName()
    {
        $siteUrl = 'https://example.com';
        $outputDir = $this->saveDir;

        $generator = new SitemapGenerator($siteUrl, $outputDir);
        $generator->setSitemapFilename('custom.xml');

        $alternates = [
            ['hreflang' => 'de', 'href' => "http://www.example.com/de"],
            ['hreflang' => 'fr', 'href' => "http://www.example.com/fr"],
        ];
        for ($i = 0; $i < 2; $i++) {
            $generator->addURL("/path/to/page-$i/", new DateTime(), 'always', 0.5, $alternates);
        }

        $generator->flush();
        $generator->finalize();

        $sitemapFilepath = $this->saveDir . '/custom.xml';
        $this->assertFileExists($sitemapFilepath);
        unlink($sitemapFilepath);
    }

    public function testSingleSitemapWithExtendedSiteUrl()
    {
        $siteUrl = 'https://example.com/submodule/';
        $outputDir = $this->saveDir;

        $generator = new SitemapGenerator($siteUrl, $outputDir);
        $alternates = [
            ['hreflang' => 'de', 'href' => "http://www.example.com/submodule/de"],
            ['hreflang' => 'fr', 'href' => "http://www.example.com/submodule/fr"],
        ];

        $datetimeStr = '2020-12-29T08:46:55+00:00';
        $lastmod = new DateTime('2020-12-29T08:46:55+00:00');

        for ($i = 0; $i < 2; $i++) {
            $generator->addURL("/path/to/page-$i/", $lastmod, 'always', 0.5, $alternates);
        }

        $generator->flush();
        $generator->finalize();
        $sitemapFilepath = $this->saveDir . '/sitemap.xml';

        $this->assertFileExists($sitemapFilepath);

        $sitemap = new SimpleXMLElement(file_get_contents($sitemapFilepath));
        $this->assertEquals('urlset', $sitemap->getName());
        $this->assertEquals(2, $sitemap->count());

        $ns = $sitemap->getNamespaces();
        $this->assertEquals('http://www.w3.org/2001/XMLSchema-instance', $ns['xsi']);
        $this->assertEquals('http://www.sitemaps.org/schemas/sitemap/0.9', array_shift($ns));

        $this->assertEquals('https://example.com/submodule/path/to/page-0/', $sitemap->url[0]->loc);
        $this->assertEquals($datetimeStr, $sitemap->url[0]->lastmod);
        $this->assertEquals('always', $sitemap->url[0]->changefreq);
        $this->assertEquals('0.5', $sitemap->url[0]->priority);
        $this->assertEquals('alternate', $sitemap->url[0]->link[0]->attributes()['rel']);
        $this->assertEquals('de', $sitemap->url[0]->link[0]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/submodule/de', $sitemap->url[0]->link[0]->attributes()['href']);
        $this->assertEquals('alternate', $sitemap->url[0]->link[1]->attributes()['rel']);
        $this->assertEquals('fr', $sitemap->url[0]->link[1]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/submodule/fr', $sitemap->url[0]->link[1]->attributes()['href']);

        $this->assertEquals('https://example.com/submodule/path/to/page-1/', $sitemap->url[1]->loc);
        $this->assertEquals($datetimeStr, $sitemap->url[1]->lastmod);
        $this->assertEquals('always', $sitemap->url[1]->changefreq);
        $this->assertEquals('0.5', $sitemap->url[1]->priority);
        $this->assertEquals('alternate', $sitemap->url[1]->link[0]->attributes()['rel']);
        $this->assertEquals('de', $sitemap->url[1]->link[0]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/submodule/de', $sitemap->url[1]->link[0]->attributes()['href']);
        $this->assertEquals('alternate', $sitemap->url[1]->link[1]->attributes()['rel']);
        $this->assertEquals('fr', $sitemap->url[1]->link[1]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/submodule/fr', $sitemap->url[1]->link[1]->attributes()['href']);
        unlink($sitemapFilepath);
    }

    public function testSingleSitemapWithEnabledCompression()
    {
        $siteUrl = 'https://example.com';
        $outputDir = $this->saveDir;

        $generator = new SitemapGenerator($siteUrl, $outputDir);
        $generator->enableCompression();
        $alternates = [
            ['hreflang' => 'de', 'href' => "http://www.example.com/de"],
            ['hreflang' => 'fr', 'href' => "http://www.example.com/fr"],
        ];

        $datetimeStr = '2020-12-29T08:46:55+00:00';
        $lastmod = new DateTime('2020-12-29T08:46:55+00:00');

        for ($i = 0; $i < 2; $i++) {
            $generator->addURL("/path/to/page-$i/", $lastmod, 'always', 0.5, $alternates);
        }

        $generator->flush();
        $generator->finalize();

        $sitemapFilepath = $this->saveDir . '/sitemap.xml.gz';
        $sitemapFilepathUncompressed = $this->saveDir . '/sitemap.xml';
        $this->assertFileExists($sitemapFilepath);
        copy('compress.zlib://' . $sitemapFilepath, $sitemapFilepathUncompressed);

        $sitemap = new SimpleXMLElement(file_get_contents($sitemapFilepathUncompressed));
        $this->assertEquals('urlset', $sitemap->getName());
        $this->assertEquals(2, $sitemap->count());

        $ns = $sitemap->getNamespaces();
        $this->assertEquals('http://www.w3.org/2001/XMLSchema-instance', $ns['xsi']);
        $this->assertEquals('http://www.sitemaps.org/schemas/sitemap/0.9', array_shift($ns));

        $this->assertEquals('https://example.com/path/to/page-0/', $sitemap->url[0]->loc);
        $this->assertEquals($datetimeStr, $sitemap->url[0]->lastmod);
        $this->assertEquals('always', $sitemap->url[0]->changefreq);
        $this->assertEquals('0.5', $sitemap->url[0]->priority);
        $this->assertEquals('alternate', $sitemap->url[0]->link[0]->attributes()['rel']);
        $this->assertEquals('de', $sitemap->url[0]->link[0]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/de', $sitemap->url[0]->link[0]->attributes()['href']);
        $this->assertEquals('alternate', $sitemap->url[0]->link[1]->attributes()['rel']);
        $this->assertEquals('fr', $sitemap->url[0]->link[1]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/fr', $sitemap->url[0]->link[1]->attributes()['href']);

        $this->assertEquals('https://example.com/path/to/page-1/', $sitemap->url[1]->loc);
        $this->assertEquals($datetimeStr, $sitemap->url[1]->lastmod);
        $this->assertEquals('always', $sitemap->url[1]->changefreq);
        $this->assertEquals('0.5', $sitemap->url[1]->priority);
        $this->assertEquals('alternate', $sitemap->url[1]->link[0]->attributes()['rel']);
        $this->assertEquals('de', $sitemap->url[1]->link[0]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/de', $sitemap->url[1]->link[0]->attributes()['href']);
        $this->assertEquals('alternate', $sitemap->url[1]->link[1]->attributes()['rel']);
        $this->assertEquals('fr', $sitemap->url[1]->link[1]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/fr', $sitemap->url[1]->link[1]->attributes()['href']);
        unlink($sitemapFilepath);
        unlink($sitemapFilepathUncompressed);
    }

    public function testSingleSitemapWithEnabledCompressionAndCreatedRobots()
    {
        $siteUrl = 'https://example.com';
        $outputDir = $this->saveDir;

        $generator = new SitemapGenerator($siteUrl, $outputDir);
        $generator->enableCompression();
        $alternates = [
            ['hreflang' => 'de', 'href' => "http://www.example.com/de"],
            ['hreflang' => 'fr', 'href' => "http://www.example.com/fr"],
        ];

        $datetimeStr = '2020-12-29T08:46:55+00:00';
        $lastmod = new DateTime('2020-12-29T08:46:55+00:00');

        for ($i = 0; $i < 2; $i++) {
            $generator->addURL("/path/to/page-$i/", $lastmod, 'always', 0.5, $alternates);
        }

        $generator->flush();
        $generator->finalize();

        $sitemapFilepath = $this->saveDir . '/sitemap.xml.gz';
        $sitemapFilepathUncompressed = $this->saveDir . '/sitemap.xml';
        $this->assertFileExists($sitemapFilepath);
        copy('compress.zlib://' . $sitemapFilepath, $sitemapFilepathUncompressed);

        $sitemap = new SimpleXMLElement(file_get_contents($sitemapFilepathUncompressed));
        $this->assertEquals('urlset', $sitemap->getName());
        $this->assertEquals(2, $sitemap->count());

        $ns = $sitemap->getNamespaces();
        $this->assertEquals('http://www.w3.org/2001/XMLSchema-instance', $ns['xsi']);
        $this->assertEquals('http://www.sitemaps.org/schemas/sitemap/0.9', array_shift($ns));

        $this->assertEquals('https://example.com/path/to/page-0/', $sitemap->url[0]->loc);
        $this->assertEquals($datetimeStr, $sitemap->url[0]->lastmod);
        $this->assertEquals('always', $sitemap->url[0]->changefreq);
        $this->assertEquals('0.5', $sitemap->url[0]->priority);
        $this->assertEquals('alternate', $sitemap->url[0]->link[0]->attributes()['rel']);
        $this->assertEquals('de', $sitemap->url[0]->link[0]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/de', $sitemap->url[0]->link[0]->attributes()['href']);
        $this->assertEquals('alternate', $sitemap->url[0]->link[1]->attributes()['rel']);
        $this->assertEquals('fr', $sitemap->url[0]->link[1]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/fr', $sitemap->url[0]->link[1]->attributes()['href']);

        $this->assertEquals('https://example.com/path/to/page-1/', $sitemap->url[1]->loc);
        $this->assertEquals($datetimeStr, $sitemap->url[1]->lastmod);
        $this->assertEquals('always', $sitemap->url[1]->changefreq);
        $this->assertEquals('0.5', $sitemap->url[1]->priority);
        $this->assertEquals('alternate', $sitemap->url[1]->link[0]->attributes()['rel']);
        $this->assertEquals('de', $sitemap->url[1]->link[0]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/de', $sitemap->url[1]->link[0]->attributes()['href']);
        $this->assertEquals('alternate', $sitemap->url[1]->link[1]->attributes()['rel']);
        $this->assertEquals('fr', $sitemap->url[1]->link[1]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/fr', $sitemap->url[1]->link[1]->attributes()['href']);
        unlink($sitemapFilepath);
        unlink($sitemapFilepathUncompressed);

        $generator->updateRobots();
        $robotsPath = $this->saveDir . '/robots.txt';
        $this->assertFileExists($robotsPath);
        $robotsContent = file_get_contents($robotsPath);
        $this->assertStringContainsString('Sitemap: https://example.com/sitemap.xml.gz', $robotsContent);
        unlink($robotsPath);
    }

    public function testMultipleSitemapsWithDefaultValues()
    {
        $siteUrl = 'https://example.com';
        $outputDir = $this->saveDir;

        $generator = new SitemapGenerator($siteUrl, $outputDir);
        $generator->setMaxUrlsPerSitemap(1);
        $alternates = [
            ['hreflang' => 'de', 'href' => "http://www.example.com/de"],
            ['hreflang' => 'fr', 'href' => "http://www.example.com/fr"],
        ];

        $datetimeStr = '2020-12-29T08:46:55+00:00';
        $lastmod = new DateTime('2020-12-29T08:46:55+00:00');

        for ($i = 0; $i < 2; $i++) {
            $generator->addURL("/path/to/page-$i/", $lastmod, 'always', 0.5, $alternates);
        }

        $generator->flush();
        $generator->finalize();

        $sitemapIndexFilepath = $this->saveDir . '/sitemap-index.xml';
        $this->assertFileExists($sitemapIndexFilepath);
        $sitemapIndex = new SimpleXMLElement(file_get_contents($sitemapIndexFilepath));
        $this->assertEquals('sitemapindex', $sitemapIndex->getName());
        $this->assertEquals(2, $sitemapIndex->count());
        $ns = $sitemapIndex->getNamespaces();
        $this->assertEquals('http://www.w3.org/2001/XMLSchema-instance', $ns['xsi']);
        $this->assertEquals('http://www.sitemaps.org/schemas/sitemap/0.9', array_shift($ns));
        $this->assertEquals('https://example.com/sitemap1.xml', $sitemapIndex->sitemap[0]->loc);
        $this->assertNotNull($sitemapIndex->sitemap[0]->lastmod);
        $this->assertEquals('https://example.com/sitemap2.xml', $sitemapIndex->sitemap[1]->loc);
        $this->assertNotNull($sitemapIndex->sitemap[1]->lastmod);
        unlink($sitemapIndexFilepath);

        $sitemapFilepath1 = $this->saveDir . '/sitemap1.xml';
        $this->assertFileExists($sitemapFilepath1);
        $sitemap1 = new SimpleXMLElement(file_get_contents($sitemapFilepath1));
        $this->assertEquals('urlset', $sitemap1->getName());
        $this->assertEquals(1, $sitemap1->count());
        $ns = $sitemap1->getNamespaces();
        $this->assertEquals('http://www.w3.org/2001/XMLSchema-instance', $ns['xsi']);
        $this->assertEquals('http://www.sitemaps.org/schemas/sitemap/0.9', array_shift($ns));
        $this->assertEquals('https://example.com/path/to/page-0/', $sitemap1->url[0]->loc);
        $this->assertEquals($datetimeStr, $sitemap1->url[0]->lastmod);
        $this->assertEquals('always', $sitemap1->url[0]->changefreq);
        $this->assertEquals('0.5', $sitemap1->url[0]->priority);
        $this->assertEquals('alternate', $sitemap1->url[0]->link[0]->attributes()['rel']);
        $this->assertEquals('de', $sitemap1->url[0]->link[0]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/de', $sitemap1->url[0]->link[0]->attributes()['href']);
        $this->assertEquals('alternate', $sitemap1->url[0]->link[1]->attributes()['rel']);
        $this->assertEquals('fr', $sitemap1->url[0]->link[1]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/fr', $sitemap1->url[0]->link[1]->attributes()['href']);
        unlink($sitemapFilepath1);

        $sitemapFilepath2 = $this->saveDir . '/sitemap2.xml';
        $this->assertFileExists($sitemapFilepath2);
        $sitemap2 = new SimpleXMLElement(file_get_contents($sitemapFilepath2));
        $this->assertEquals('urlset', $sitemap2->getName());
        $this->assertEquals(1, $sitemap2->count());
        $ns = $sitemap2->getNamespaces();
        $this->assertEquals('http://www.w3.org/2001/XMLSchema-instance', $ns['xsi']);
        $this->assertEquals('http://www.sitemaps.org/schemas/sitemap/0.9', array_shift($ns));
        $this->assertEquals('https://example.com/path/to/page-1/', $sitemap2->url[0]->loc);
        $this->assertEquals($datetimeStr, $sitemap2->url[0]->lastmod);
        $this->assertEquals('always', $sitemap2->url[0]->changefreq);
        $this->assertEquals('0.5', $sitemap2->url[0]->priority);
        $this->assertEquals('alternate', $sitemap2->url[0]->link[0]->attributes()['rel']);
        $this->assertEquals('de', $sitemap2->url[0]->link[0]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/de', $sitemap2->url[0]->link[0]->attributes()['href']);
        $this->assertEquals('alternate', $sitemap2->url[0]->link[1]->attributes()['rel']);
        $this->assertEquals('fr', $sitemap2->url[0]->link[1]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/fr', $sitemap2->url[0]->link[1]->attributes()['href']);
        unlink($sitemapFilepath2);
    }

    public function testMultipleSitemapsWithCustomSitemapIndexName()
    {
        $siteUrl = 'https://example.com';
        $outputDir = $this->saveDir;

        $generator = new SitemapGenerator($siteUrl, $outputDir);
        $generator->setSitemapIndexFilename('custom-index.xml');
        $generator->setMaxUrlsPerSitemap(1);
        $alternates = [
            ['hreflang' => 'de', 'href' => "http://www.example.com/de"],
            ['hreflang' => 'fr', 'href' => "http://www.example.com/fr"],
        ];

        $datetimeStr = '2020-12-29T08:46:55+00:00';
        $lastmod = new DateTime('2020-12-29T08:46:55+00:00');

        for ($i = 0; $i < 2; $i++) {
            $generator->addURL("/path/to/page-$i/", $lastmod, 'always', 0.5, $alternates);
        }

        $generator->flush();
        $generator->finalize();

        $sitemapIndexFilepath = $this->saveDir . '/custom-index.xml';
        $this->assertFileExists($sitemapIndexFilepath);
        unlink($sitemapIndexFilepath);

        $sitemapFilepath1 = $this->saveDir . '/sitemap1.xml';
        $this->assertFileExists($sitemapFilepath1);
        unlink($sitemapFilepath1);

        $sitemapFilepath2 = $this->saveDir . '/sitemap2.xml';
        $this->assertFileExists($sitemapFilepath2);
        unlink($sitemapFilepath2);
    }

    public function testMultipleSitemapsCompressionAndCreatedRobots()
    {
        $siteUrl = 'https://example.com';
        $outputDir = $this->saveDir;

        $generator = new SitemapGenerator($siteUrl, $outputDir);
        $generator->setMaxUrlsPerSitemap(1);
        $generator->enableCompression();
        $alternates = [
            ['hreflang' => 'de', 'href' => "http://www.example.com/de"],
            ['hreflang' => 'fr', 'href' => "http://www.example.com/fr"],
        ];

        $datetimeStr = '2020-12-29T08:46:55+00:00';
        $lastmod = new DateTime('2020-12-29T08:46:55+00:00');

        for ($i = 0; $i < 2; $i++) {
            $generator->addURL("/path/to/page-$i/", $lastmod, 'always', 0.5, $alternates);
        }

        $generator->flush();
        $generator->finalize();

        $sitemapIndexFilepath = $this->saveDir . '/sitemap-index.xml';
        $this->assertFileExists($sitemapIndexFilepath);
        $sitemapIndex = new SimpleXMLElement(file_get_contents($sitemapIndexFilepath));
        $this->assertEquals('sitemapindex', $sitemapIndex->getName());
        $this->assertEquals(2, $sitemapIndex->count());
        $ns = $sitemapIndex->getNamespaces();
        $this->assertEquals('http://www.w3.org/2001/XMLSchema-instance', $ns['xsi']);
        $this->assertEquals('http://www.sitemaps.org/schemas/sitemap/0.9', array_shift($ns));
        $this->assertEquals('https://example.com/sitemap1.xml.gz', $sitemapIndex->sitemap[0]->loc);
        $this->assertNotNull($sitemapIndex->sitemap[0]->lastmod);
        $this->assertEquals('https://example.com/sitemap2.xml.gz', $sitemapIndex->sitemap[1]->loc);
        $this->assertNotNull($sitemapIndex->sitemap[1]->lastmod);
        unlink($sitemapIndexFilepath);

        $sitemapFilepath1 = $this->saveDir . '/sitemap1.xml';
        $sitemapFilepath1Compressed = $sitemapFilepath1 . '.gz';
        $this->assertFileExists($sitemapFilepath1Compressed);
        copy('compress.zlib://' . $sitemapFilepath1Compressed, $sitemapFilepath1);
        $sitemap1 = new SimpleXMLElement(file_get_contents($sitemapFilepath1));
        $this->assertEquals('urlset', $sitemap1->getName());
        $this->assertEquals(1, $sitemap1->count());
        $ns = $sitemap1->getNamespaces();
        $this->assertEquals('http://www.w3.org/2001/XMLSchema-instance', $ns['xsi']);
        $this->assertEquals('http://www.sitemaps.org/schemas/sitemap/0.9', array_shift($ns));
        $this->assertEquals('https://example.com/path/to/page-0/', $sitemap1->url[0]->loc);
        $this->assertEquals($datetimeStr, $sitemap1->url[0]->lastmod);
        $this->assertEquals('always', $sitemap1->url[0]->changefreq);
        $this->assertEquals('0.5', $sitemap1->url[0]->priority);
        $this->assertEquals('alternate', $sitemap1->url[0]->link[0]->attributes()['rel']);
        $this->assertEquals('de', $sitemap1->url[0]->link[0]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/de', $sitemap1->url[0]->link[0]->attributes()['href']);
        $this->assertEquals('alternate', $sitemap1->url[0]->link[1]->attributes()['rel']);
        $this->assertEquals('fr', $sitemap1->url[0]->link[1]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/fr', $sitemap1->url[0]->link[1]->attributes()['href']);
        unlink($sitemapFilepath1);
        unlink($sitemapFilepath1Compressed);

        $sitemapFilepath2 = $this->saveDir . '/sitemap2.xml';
        $sitemapFilepath2Compressed = $sitemapFilepath2 . '.gz';
        $this->assertFileExists($sitemapFilepath2Compressed);
        copy('compress.zlib://' . $sitemapFilepath2Compressed, $sitemapFilepath2);
        $this->assertFileExists($sitemapFilepath2);
        $sitemap2 = new SimpleXMLElement(file_get_contents($sitemapFilepath2));
        $this->assertEquals('urlset', $sitemap2->getName());
        $this->assertEquals(1, $sitemap2->count());
        $ns = $sitemap2->getNamespaces();
        $this->assertEquals('http://www.w3.org/2001/XMLSchema-instance', $ns['xsi']);
        $this->assertEquals('http://www.sitemaps.org/schemas/sitemap/0.9', array_shift($ns));
        $this->assertEquals('https://example.com/path/to/page-1/', $sitemap2->url[0]->loc);
        $this->assertEquals($datetimeStr, $sitemap2->url[0]->lastmod);
        $this->assertEquals('always', $sitemap2->url[0]->changefreq);
        $this->assertEquals('0.5', $sitemap2->url[0]->priority);
        $this->assertEquals('alternate', $sitemap2->url[0]->link[0]->attributes()['rel']);
        $this->assertEquals('de', $sitemap2->url[0]->link[0]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/de', $sitemap2->url[0]->link[0]->attributes()['href']);
        $this->assertEquals('alternate', $sitemap2->url[0]->link[1]->attributes()['rel']);
        $this->assertEquals('fr', $sitemap2->url[0]->link[1]->attributes()['hreflang']);
        $this->assertEquals('http://www.example.com/fr', $sitemap2->url[0]->link[1]->attributes()['href']);
        unlink($sitemapFilepath2);
        unlink($sitemapFilepath2Compressed);

        $generator->updateRobots();
        $robotsPath = $this->saveDir . '/robots.txt';
        $this->assertFileExists($robotsPath);
        $robotsContent = file_get_contents($robotsPath);
        $this->assertStringContainsString('Sitemap: https://example.com/sitemap-index.xml', $robotsContent);
        unlink($robotsPath);
    }
}