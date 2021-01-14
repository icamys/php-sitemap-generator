<?php

use Icamys\SitemapGenerator\Runtime;
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

        $sitemapXHTML = new SimpleXMLElement(file_get_contents($sitemapFilepath), 0, false, 'xhtml', true);
        foreach ($sitemapXHTML->children() as $url) {
            $links = $url->children('xhtml', true)->link;
            $this->assertEquals('alternate', $links[0]->attributes()['rel']);
            $this->assertEquals('de', $links[0]->attributes()['hreflang']);
            $this->assertEquals('http://www.example.com/de', $links[0]->attributes()['href']);
            $this->assertEquals('alternate', $links[1]->attributes()['rel']);
            $this->assertEquals('fr', $links[1]->attributes()['hreflang']);
            $this->assertEquals('http://www.example.com/fr', $links[1]->attributes()['href']);
        }

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

        $this->assertEquals('https://example.com/path/to/page-1/', $sitemap->url[1]->loc);
        $this->assertEquals($datetimeStr, $sitemap->url[1]->lastmod);
        $this->assertEquals('always', $sitemap->url[1]->changefreq);
        $this->assertEquals('0.5', $sitemap->url[1]->priority);
        unlink($sitemapFilepath);

        $generatedFiles = $generator->getGeneratedFiles();
        $this->assertCount(2, $generatedFiles);
        $this->assertNotEmpty($generatedFiles['sitemaps_location']);
        $this->assertCount(1, $generatedFiles['sitemaps_location']);
        $this->assertEquals('./test/Feature/sitemap.xml', $generatedFiles['sitemaps_location'][0]);
        $this->assertEquals('https://example.com/sitemap.xml', $generatedFiles['sitemaps_index_url']);
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

        $generatedFiles = $generator->getGeneratedFiles();
        $this->assertCount(2, $generatedFiles);
        $this->assertNotEmpty($generatedFiles['sitemaps_location']);
        $this->assertCount(1, $generatedFiles['sitemaps_location']);
        $this->assertEquals('./test/Feature/custom.xml', $generatedFiles['sitemaps_location'][0]);
        $this->assertEquals('https://example.com/custom.xml', $generatedFiles['sitemaps_index_url']);
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

        $sitemapXHTML = new SimpleXMLElement(file_get_contents($sitemapFilepath), 0, false, 'xhtml', true);
        foreach ($sitemapXHTML->children() as $url) {
            $links = $url->children('xhtml', true)->link;
            $this->assertEquals('alternate', $links[0]->attributes()['rel']);
            $this->assertEquals('de', $links[0]->attributes()['hreflang']);
            $this->assertEquals('http://www.example.com/submodule/de', $links[0]->attributes()['href']);
            $this->assertEquals('alternate', $links[1]->attributes()['rel']);
            $this->assertEquals('fr', $links[1]->attributes()['hreflang']);
            $this->assertEquals('http://www.example.com/submodule/fr', $links[1]->attributes()['href']);
        }

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

        $this->assertEquals('https://example.com/submodule/path/to/page-1/', $sitemap->url[1]->loc);
        $this->assertEquals($datetimeStr, $sitemap->url[1]->lastmod);
        $this->assertEquals('always', $sitemap->url[1]->changefreq);
        $this->assertEquals('0.5', $sitemap->url[1]->priority);
        unlink($sitemapFilepath);

        $generatedFiles = $generator->getGeneratedFiles();
        $this->assertCount(2, $generatedFiles);
        $this->assertNotEmpty($generatedFiles['sitemaps_location']);
        $this->assertCount(1, $generatedFiles['sitemaps_location']);
        $this->assertEquals('./test/Feature/sitemap.xml', $generatedFiles['sitemaps_location'][0]);
        $this->assertEquals('https://example.com/submodule/sitemap.xml', $generatedFiles['sitemaps_index_url']);
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

        $sitemapXHTML = new SimpleXMLElement(file_get_contents($sitemapFilepathUncompressed), 0, false, 'xhtml', true);
        foreach ($sitemapXHTML->children() as $url) {
            $links = $url->children('xhtml', true)->link;
            $this->assertEquals('alternate', $links[0]->attributes()['rel']);
            $this->assertEquals('de', $links[0]->attributes()['hreflang']);
            $this->assertEquals('http://www.example.com/de', $links[0]->attributes()['href']);
            $this->assertEquals('alternate', $links[1]->attributes()['rel']);
            $this->assertEquals('fr', $links[1]->attributes()['hreflang']);
            $this->assertEquals('http://www.example.com/fr', $links[1]->attributes()['href']);
        }

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

        $this->assertEquals('https://example.com/path/to/page-1/', $sitemap->url[1]->loc);
        $this->assertEquals($datetimeStr, $sitemap->url[1]->lastmod);
        $this->assertEquals('always', $sitemap->url[1]->changefreq);
        $this->assertEquals('0.5', $sitemap->url[1]->priority);
        unlink($sitemapFilepath);
        unlink($sitemapFilepathUncompressed);

        $generatedFiles = $generator->getGeneratedFiles();
        $this->assertCount(2, $generatedFiles);
        $this->assertNotEmpty($generatedFiles['sitemaps_location']);
        $this->assertCount(1, $generatedFiles['sitemaps_location']);
        $this->assertEquals('./test/Feature/sitemap.xml.gz', $generatedFiles['sitemaps_location'][0]);
        $this->assertEquals('https://example.com/sitemap.xml.gz', $generatedFiles['sitemaps_index_url']);
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

        $sitemapXHTML = new SimpleXMLElement(file_get_contents($sitemapFilepathUncompressed), 0, false, 'xhtml', true);
        foreach ($sitemapXHTML->children() as $url) {
            $links = $url->children('xhtml', true)->link;
            $this->assertEquals('alternate', $links[0]->attributes()['rel']);
            $this->assertEquals('de', $links[0]->attributes()['hreflang']);
            $this->assertEquals('http://www.example.com/de', $links[0]->attributes()['href']);
            $this->assertEquals('alternate', $links[1]->attributes()['rel']);
            $this->assertEquals('fr', $links[1]->attributes()['hreflang']);
            $this->assertEquals('http://www.example.com/fr', $links[1]->attributes()['href']);
        }

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

        $this->assertEquals('https://example.com/path/to/page-1/', $sitemap->url[1]->loc);
        $this->assertEquals($datetimeStr, $sitemap->url[1]->lastmod);
        $this->assertEquals('always', $sitemap->url[1]->changefreq);
        $this->assertEquals('0.5', $sitemap->url[1]->priority);
        unlink($sitemapFilepath);
        unlink($sitemapFilepathUncompressed);

        $generator->updateRobots();
        $robotsPath = $this->saveDir . '/robots.txt';
        $this->assertFileExists($robotsPath);
        $robotsContent = file_get_contents($robotsPath);
        $this->assertStringContainsString('Sitemap: https://example.com/sitemap.xml.gz', $robotsContent);
        unlink($robotsPath);

        $generatedFiles = $generator->getGeneratedFiles();
        $this->assertCount(2, $generatedFiles);
        $this->assertNotEmpty($generatedFiles['sitemaps_location']);
        $this->assertCount(1, $generatedFiles['sitemaps_location']);
        $this->assertEquals('./test/Feature/sitemap.xml.gz', $generatedFiles['sitemaps_location'][0]);
        $this->assertEquals('https://example.com/sitemap.xml.gz', $generatedFiles['sitemaps_index_url']);
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

        $sitemapXHTML = new SimpleXMLElement(file_get_contents($sitemapFilepath1), 0, false, 'xhtml', true);
        foreach ($sitemapXHTML->children() as $url) {
            $links = $url->children('xhtml', true)->link;
            $this->assertEquals('alternate', $links[0]->attributes()['rel']);
            $this->assertEquals('de', $links[0]->attributes()['hreflang']);
            $this->assertEquals('http://www.example.com/de', $links[0]->attributes()['href']);
            $this->assertEquals('alternate', $links[1]->attributes()['rel']);
            $this->assertEquals('fr', $links[1]->attributes()['hreflang']);
            $this->assertEquals('http://www.example.com/fr', $links[1]->attributes()['href']);
        }

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
        unlink($sitemapFilepath1);

        $sitemapFilepath2 = $this->saveDir . '/sitemap2.xml';
        $this->assertFileExists($sitemapFilepath2);

        $sitemapXHTML = new SimpleXMLElement(file_get_contents($sitemapFilepath2), 0, false, 'xhtml', true);
        foreach ($sitemapXHTML->children() as $url) {
            $links = $url->children('xhtml', true)->link;
            $this->assertEquals('alternate', $links[0]->attributes()['rel']);
            $this->assertEquals('de', $links[0]->attributes()['hreflang']);
            $this->assertEquals('http://www.example.com/de', $links[0]->attributes()['href']);
            $this->assertEquals('alternate', $links[1]->attributes()['rel']);
            $this->assertEquals('fr', $links[1]->attributes()['hreflang']);
            $this->assertEquals('http://www.example.com/fr', $links[1]->attributes()['href']);
        }

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
        unlink($sitemapFilepath2);

        $generatedFiles = $generator->getGeneratedFiles();
        $this->assertCount(3, $generatedFiles);
        $this->assertNotEmpty($generatedFiles['sitemaps_location']);
        $this->assertCount(2, $generatedFiles['sitemaps_location']);
        $this->assertEquals('./test/Feature/sitemap1.xml', $generatedFiles['sitemaps_location'][0]);
        $this->assertEquals('./test/Feature/sitemap2.xml', $generatedFiles['sitemaps_location'][1]);
        $this->assertEquals('./test/Feature/sitemap-index.xml', $generatedFiles['sitemaps_index_location']);
        $this->assertEquals('https://example.com/sitemap-index.xml', $generatedFiles['sitemaps_index_url']);
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

        $generatedFiles = $generator->getGeneratedFiles();
        $this->assertCount(3, $generatedFiles);
        $this->assertNotEmpty($generatedFiles['sitemaps_location']);
        $this->assertCount(2, $generatedFiles['sitemaps_location']);
        $this->assertEquals('./test/Feature/sitemap1.xml', $generatedFiles['sitemaps_location'][0]);
        $this->assertEquals('./test/Feature/sitemap2.xml', $generatedFiles['sitemaps_location'][1]);
        $this->assertEquals('./test/Feature/custom-index.xml', $generatedFiles['sitemaps_index_location']);
        $this->assertEquals('https://example.com/custom-index.xml', $generatedFiles['sitemaps_index_url']);
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

        $sitemapXHTML = new SimpleXMLElement(file_get_contents($sitemapFilepath1), 0, false, 'xhtml', true);
        foreach ($sitemapXHTML->children() as $url) {
            $links = $url->children('xhtml', true)->link;
            $this->assertEquals('alternate', $links[0]->attributes()['rel']);
            $this->assertEquals('de', $links[0]->attributes()['hreflang']);
            $this->assertEquals('http://www.example.com/de', $links[0]->attributes()['href']);
            $this->assertEquals('alternate', $links[1]->attributes()['rel']);
            $this->assertEquals('fr', $links[1]->attributes()['hreflang']);
            $this->assertEquals('http://www.example.com/fr', $links[1]->attributes()['href']);
        }

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
        unlink($sitemapFilepath1);
        unlink($sitemapFilepath1Compressed);

        $sitemapFilepath2 = $this->saveDir . '/sitemap2.xml';
        $sitemapFilepath2Compressed = $sitemapFilepath2 . '.gz';
        $this->assertFileExists($sitemapFilepath2Compressed);
        copy('compress.zlib://' . $sitemapFilepath2Compressed, $sitemapFilepath2);
        $this->assertFileExists($sitemapFilepath2);

        $sitemapXHTML = new SimpleXMLElement(file_get_contents($sitemapFilepath2), 0, false, 'xhtml', true);
        foreach ($sitemapXHTML->children() as $url) {
            $links = $url->children('xhtml', true)->link;
            $this->assertEquals('alternate', $links[0]->attributes()['rel']);
            $this->assertEquals('de', $links[0]->attributes()['hreflang']);
            $this->assertEquals('http://www.example.com/de', $links[0]->attributes()['href']);
            $this->assertEquals('alternate', $links[1]->attributes()['rel']);
            $this->assertEquals('fr', $links[1]->attributes()['hreflang']);
            $this->assertEquals('http://www.example.com/fr', $links[1]->attributes()['href']);
        }

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
        unlink($sitemapFilepath2);
        unlink($sitemapFilepath2Compressed);

        $generator->updateRobots();
        $robotsPath = $this->saveDir . '/robots.txt';
        $this->assertFileExists($robotsPath);
        $robotsContent = file_get_contents($robotsPath);
        $this->assertStringContainsString('Sitemap: https://example.com/sitemap-index.xml', $robotsContent);
        unlink($robotsPath);

        $generatedFiles = $generator->getGeneratedFiles();
        $this->assertCount(3, $generatedFiles);
        $this->assertNotEmpty($generatedFiles['sitemaps_location']);
        $this->assertCount(2, $generatedFiles['sitemaps_location']);
        $this->assertEquals('./test/Feature/sitemap1.xml.gz', $generatedFiles['sitemaps_location'][0]);
        $this->assertEquals('./test/Feature/sitemap2.xml.gz', $generatedFiles['sitemaps_location'][1]);
        $this->assertEquals('./test/Feature/sitemap-index.xml', $generatedFiles['sitemaps_index_location']);
        $this->assertEquals('https://example.com/sitemap-index.xml', $generatedFiles['sitemaps_index_url']);
    }

    public function testSubmitValuesWithYahooIdentifier()
    {
        $siteUrl = 'https://example.com';
        $outputDir = $this->saveDir;

        $runtimeMock = $this->createMock(Runtime::class);
        $runtimeMock
            ->expects($this->exactly(1))
            ->method('extension_loaded')
            ->with('curl')
            ->willReturn(true);
        $runtimeMock
            ->expects($this->exactly(5))
            ->method('curl_init')
            ->withConsecutive(
                [$this->equalTo('http://search.yahooapis.com/SiteExplorerService/V1/updateNotification?appid=YAHOO_APP_ID_TEST&url=https://example.com/sitemap.xml')],
                [$this->equalTo('http://www.google.com/ping?sitemap=https://example.com/sitemap.xml')],
                [$this->equalTo('http://submissions.ask.com/ping?sitemap=https://example.com/sitemap.xml')],
                [$this->equalTo('http://www.bing.com/ping?sitemap=https://example.com/sitemap.xml')],
                [$this->equalTo('http://www.webmaster.yandex.ru/ping?sitemap=https://example.com/sitemap.xml')],
            )
            ->willReturn(true);
        $runtimeMock
            ->expects($this->exactly(5))
            ->method('curl_getinfo')
            ->willReturn(['http_code' => 200]);
        $runtimeMock
            ->expects($this->exactly(5))
            ->method('curl_setopt');
        $runtimeMock
            ->expects($this->exactly(5))
            ->method('curl_exec');

        $generator = new SitemapGenerator($siteUrl, $outputDir, null, $runtimeMock);
        $alternates = [
            ['hreflang' => 'de', 'href' => "http://www.example.com/de"],
            ['hreflang' => 'fr', 'href' => "http://www.example.com/fr"],
        ];

        $lastmod = new DateTime('2020-12-29T08:46:55+00:00');

        for ($i = 0; $i < 2; $i++) {
            $generator->addURL("/path/to/page-$i/", $lastmod, 'always', 0.5, $alternates);
        }

        $generator->flush();
        $generator->finalize();

        $sitemapFilepath = $this->saveDir . '/sitemap.xml';
        $this->assertFileExists($sitemapFilepath);
        unlink($sitemapFilepath);

        $generatedFiles = $generator->getGeneratedFiles();
        $this->assertCount(2, $generatedFiles);
        $this->assertNotEmpty($generatedFiles['sitemaps_location']);
        $this->assertCount(1, $generatedFiles['sitemaps_location']);
        $this->assertEquals('./test/Feature/sitemap.xml', $generatedFiles['sitemaps_location'][0]);
        $this->assertEquals('https://example.com/sitemap.xml', $generatedFiles['sitemaps_index_url']);

        $generator->submitSitemap('YAHOO_APP_ID_TEST');
    }

    public function testSubmitValuesWithoutYahooIdentifier()
    {
        $siteUrl = 'https://example.com';
        $outputDir = $this->saveDir;

        $runtimeMock = $this->createMock(Runtime::class);
        $runtimeMock
            ->expects($this->exactly(1))
            ->method('extension_loaded')
            ->with('curl')
            ->willReturn(true);
        $runtimeMock
            ->expects($this->exactly(5))
            ->method('curl_init')
            ->withConsecutive(
                [$this->equalTo('http://search.yahooapis.com/SiteExplorerService/V1/ping?sitemap=https://example.com/sitemap.xml')],
                [$this->equalTo('http://www.google.com/ping?sitemap=https://example.com/sitemap.xml')],
                [$this->equalTo('http://submissions.ask.com/ping?sitemap=https://example.com/sitemap.xml')],
                [$this->equalTo('http://www.bing.com/ping?sitemap=https://example.com/sitemap.xml')],
                [$this->equalTo('http://www.webmaster.yandex.ru/ping?sitemap=https://example.com/sitemap.xml')],
            )
            ->willReturn(true);
        $runtimeMock
            ->expects($this->exactly(5))
            ->method('curl_getinfo')
            ->willReturn(['http_code' => 200]);
        $runtimeMock
            ->expects($this->exactly(5))
            ->method('curl_setopt');
        $runtimeMock
            ->expects($this->exactly(5))
            ->method('curl_exec');

        $generator = new SitemapGenerator($siteUrl, $outputDir, null, $runtimeMock);
        $alternates = [
            ['hreflang' => 'de', 'href' => "http://www.example.com/de"],
            ['hreflang' => 'fr', 'href' => "http://www.example.com/fr"],
        ];

        $lastmod = new DateTime('2020-12-29T08:46:55+00:00');

        for ($i = 0; $i < 2; $i++) {
            $generator->addURL("/path/to/page-$i/", $lastmod, 'always', 0.5, $alternates);
        }

        $generator->flush();
        $generator->finalize();

        $sitemapFilepath = $this->saveDir . '/sitemap.xml';
        $this->assertFileExists($sitemapFilepath);
        unlink($sitemapFilepath);

        $generatedFiles = $generator->getGeneratedFiles();
        $this->assertCount(2, $generatedFiles);
        $this->assertNotEmpty($generatedFiles['sitemaps_location']);
        $this->assertCount(1, $generatedFiles['sitemaps_location']);
        $this->assertEquals('./test/Feature/sitemap.xml', $generatedFiles['sitemaps_location'][0]);
        $this->assertEquals('https://example.com/sitemap.xml', $generatedFiles['sitemaps_index_url']);

        $generator->submitSitemap();
    }

    public function testExceptionWhenCurlIsNotPresent()
    {
        $this->expectException(BadMethodCallException::class);
        $siteUrl = 'https://example.com';
        $outputDir = $this->saveDir;

        $runtimeMock = $this->createMock(Runtime::class);
        $runtimeMock
            ->expects($this->exactly(1))
            ->method('extension_loaded')
            ->with('curl')
            ->willReturn(false);

        $generator = new SitemapGenerator($siteUrl, $outputDir, null, $runtimeMock);
        $alternates = [
            ['hreflang' => 'de', 'href' => "http://www.example.com/de"],
            ['hreflang' => 'fr', 'href' => "http://www.example.com/fr"],
        ];

        $lastmod = new DateTime('2020-12-29T08:46:55+00:00');

        for ($i = 0; $i < 2; $i++) {
            $generator->addURL("/path/to/page-$i/", $lastmod, 'always', 0.5, $alternates);
        }

        $generator->flush();
        $generator->finalize();

        $sitemapFilepath = $this->saveDir . '/sitemap.xml';
        $this->assertFileExists($sitemapFilepath);
        unlink($sitemapFilepath);

        $generatedFiles = $generator->getGeneratedFiles();
        $this->assertCount(2, $generatedFiles);
        $this->assertNotEmpty($generatedFiles['sitemaps_location']);
        $this->assertCount(1, $generatedFiles['sitemaps_location']);
        $this->assertEquals('./test/Feature/sitemap.xml', $generatedFiles['sitemaps_location'][0]);
        $this->assertEquals('https://example.com/sitemap.xml', $generatedFiles['sitemaps_index_url']);

        $generator->submitSitemap('YAHOO_APP_ID_TEST');
    }

    public function testGoogleVideoExtension()
    {
        $siteUrl = 'https://example.com';
        $outputDir = '/tmp';

        $generator = new SitemapGenerator($siteUrl, $outputDir);

        $extensions = [
            'google_video' => [
                'thumbnail_loc' => 'http://www.example.com/thumbs/123.jpg',
                'title' => 'Grilling steaks for summer',
                'description' => 'Alkis shows you how to get perfectly done steaks every time',
                'content_loc' => 'http://streamserver.example.com/video123.mp4',
                'player_loc' => 'http://www.example.com/videoplayer.php?video=123',
                'duration' => 600,
                'expiration_date' => '2021-11-05T19:20:30+08:00',
                'rating' => 4.2,
                'view_count' => 12345,
                'publication_date' => '2007-11-05T19:20:30+08:00',
                'family_friendly' => 'yes',
                'restriction' => [
                    'relationship' => 'allow',
                    'value' => 'IE GB US CA',
                ],
                'platform' => [
                    'relationship' => 'allow',
                    'value' => 'web mobile',
                ],
                'price' => [
                    [
                        'currency' => 'EUR',
                        'value' => 1.99,
                        'type' => 'rent',
                        'resolution' => 'hd',
                    ]
                ],
                'requires_subscription' => 'yes',
                'uploader' => [
                    'info' => 'https://example.com/users/grillymcgrillerson',
                    'value' => 'GrillyMcGrillerson',
                ],
                'live' => 'no',
                'tag' => [
                    "steak", "meat", "summer", "outdoor"
                ],
                'category' => 'baking',
            ]
        ];

        $generator->addURL("/path/to/page/", null, null, null, null, $extensions);

        $generator->flush();
        $generator->finalize();

        $sitemapFilepath = $outputDir . '/sitemap.xml';
        $this->assertFileExists($sitemapFilepath);

        $sitemap = new SimpleXMLElement(file_get_contents($sitemapFilepath), 0, false, 'video', true);
        $video = $sitemap->children()[0]->children('video', true)->video;
        $this->assertEquals('http://www.example.com/thumbs/123.jpg', $video->thumbnail_loc);
        $this->assertEquals('Grilling steaks for summer', $video->title);
        $this->assertEquals('Alkis shows you how to get perfectly done steaks every time', $video->description);
        $this->assertCount(2, $video->content_loc);
        $this->assertEquals('http://streamserver.example.com/video123.mp4', $video->content_loc[0]);
        $this->assertEquals('http://www.example.com/videoplayer.php?video=123', $video->content_loc[1]);
        $this->assertEquals('600', $video->duration);
        $this->assertEquals('2021-11-05T19:20:30+08:00', $video->expiration_date);
        $this->assertEquals('4.2', $video->rating);
        $this->assertEquals('12345', $video->view_count);
        $this->assertEquals('2007-11-05T19:20:30+08:00', $video->publication_date);
        $this->assertEquals('yes', $video->family_friendly);
        $this->assertEquals('IE GB US CA', $video->restriction);
        $this->assertEquals('web mobile', $video->platform);
        $this->assertEquals('1.99', $video->price);
        $this->assertEquals('yes', $video->requires_subscription);
        $this->assertEquals('GrillyMcGrillerson', $video->uploader);
        $this->assertEquals('no', $video->live);
        $this->assertCount(4, $video->tag);
        $this->assertEquals('steak', $video->tag[0]);
        $this->assertEquals('meat', $video->tag[1]);
        $this->assertEquals('summer', $video->tag[2]);
        $this->assertEquals('outdoor', $video->tag[3]);
        $this->assertEquals('baking', $video->category);
    }

    public function testGoogleVideoExtensionValidationErrorOnUrlAdd()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields: thumbnail_loc, title, description');
        $siteUrl = 'https://example.com';
        $outputDir = '/tmp';
        $generator = new SitemapGenerator($siteUrl, $outputDir);
        $extensions = ['google_video' => []];
        $generator->addURL("/path/to/page/", null, null, null, null, $extensions);
        $generator->flush();
        $generator->finalize();
    }
}