<?php


namespace Icamys\SitemapGenerator;


use DateTime;
use DOMDocument;
use DOMElement;
use Exception;

class SitemapTemplateBuilder
{
    const ATTR_NAME_LOC = 'loc';
    const ATTR_NAME_LASTMOD = 'lastmod';
    const ATTR_NAME_CHANGEFREQ = 'changefreq';
    const ATTR_NAME_PRIORITY = 'priority';
    const ATTR_NAME_ALTERNATES = 'alternates';
    private $version;
    private $now;
    private $baseURL;

    public function __construct($baseURL, $version = null, $now = null)
    {
        $this->baseURL = $baseURL;
        $this->version = $version;
        $this->now = $now;
        if (!$this->now) {
            $this->now = new DateTime();
        }
    }

    public function createSitemapDocumentContainer(DOMDocument $sitemapDoc, $containerName): DOMElement
    {
        $container = $sitemapDoc->createElement($containerName);

        if ($container === false) {
            throw new Exception('failed to create sitemap items container');
        }

        $container->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $container->setAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
        $container->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $sitemapDoc->appendChild($container);
        return $container;
    }

    public function createSitemapDocument(DateTime $now = null): DOMDocument
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->appendChild($doc->createComment(sprintf(' generator-class="%s" ', get_class($this))));

        if ($this->version) {
            $doc->appendChild($doc->createComment(sprintf(' generator-version="%s" ', $this->version)));
        }

        $doc->appendChild($doc->createComment(sprintf(' generated-on="%s" ', $now || $this->now->format(DATE_ATOM))));
        return $doc;
    }

    public function createUrlElement(
        DOMDocument $sitemap,
        string $loc, $lastmod = null, $changefreq = null, $priority = null, $alternates = []
    ): DOMElement
    {
        $urlEl = $sitemap->createElement('url');

        $urlEl->appendChild(
            $sitemap->createElement(
                self::ATTR_NAME_LOC,
                htmlspecialchars($this->baseURL . $loc, ENT_QUOTES)
            )
        );

        if ($lastmod !== null) {
            $urlEl->appendChild($sitemap->createElement(self::ATTR_NAME_LASTMOD, $lastmod));
        }

        if ($changefreq !== null) {
            $urlEl->appendChild($sitemap->createElement(self::ATTR_NAME_CHANGEFREQ, $changefreq));
        }
        if ($priority !== null) {
            $urlEl->appendChild($sitemap->createElement(self::ATTR_NAME_PRIORITY, $priority));
        }
        if (count($alternates) > 0) {
            foreach ($alternates as $alternate) {
                if (isset($alternate['hreflang']) && isset($alternate['href'])) {
                    $tag = $sitemap->createElement('link');
                    $tag->setAttribute('rel', 'alternate');
                    $tag->setAttribute('hreflang', $alternate['hreflang']);
                    $tag->setAttribute('href', $alternate['href']);
                    $urlEl->appendChild($tag);
                }
            }
        }
        return $urlEl;
    }
}