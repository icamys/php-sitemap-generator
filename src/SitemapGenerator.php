<?php

namespace Icamys\SitemapGenerator;

/**
 * Sitemap generator class.
 *
 * Generate your sitemaps with ease! Inspired by Icamys\SitemapGenerator.
 * For sitemap limitations see https://www.sitemaps.org/protocol.html
 *
 * @package TheIggs\SitemapGenerator
 * @see https://github.com/icamys/php-sitemap-generator/
 * @see https://www.sitemaps.org/protocol.html
 */
class SitemapGenerator
{
    /**
     * Maximum allowed index file size, bytes.
     */
    const MAX_INDEX_SIZE = 10485760;

    /**
     * Maximum allowed sitemap file size, bytes.
     */
    const MAX_SITEMAP_SIZE = 10485760;

    /**
     * Maximum allowed number of sitemaps per index file.
     */
    const MAX_SITEMAPS_PER_INDEX = 50000;

    /**
     * Maximum allowed number of URLs per sitemap file.
     */
    const MAX_URLS_PER_SITEMAP = 50000;

    /**
     * "Location" parameter index (for use in $urls array).
     */
    const URL_PARAM_LOC = 0;

    /**
     * "Last modified" parameter index (for use in $urls array).
     */
    const URL_PARAM_LASTMOD = 1;

    /**
     * "Change frequency" parameter index (for use in $urls array).
     */
    const URL_PARAM_CHANGEFREQ = 2;

    /**
     * "Priority" parameter index (for use in $urls array).
     */
    const URL_PARAM_PRIORITY = 3;

    /**
     * Generator information.
     *
     * @var string
     */
    protected static $generatorInfo = '';

    /**
     * If true, compressed .xml.gz sitemap files will be created instead of
     * plain .xml files. This setting doesn't apply to sitemap index files
     * which are always plain text.
     *
     * @var bool
     */
    public $createGzipFile = true;

    /**
     * If true, additional generator information will be included into
     * the resulting XML code.
     *
     * @var bool
     */
    public $includeGeneratorInfo = true;

    /**
     * Default name for a sitemap index file.
     *
     * @var string
     */
    public $indexFilename = 'sitemap-index.xml';

    /**
     * Default name for a sitemap file.
     *
     * @var string
     */
    public $sitemapFilename = 'sitemap.xml';

    /**
     * Site URL.
     *
     * @var string
     */
    protected $baseURL;

    /**
     * Base path, relative to script location.
     *
     * Use this if your sitemap files should be stored in other directory
     * than this script.
     *
     * @var string
     */
    protected $basePath = '';

    /**
     * Version of this class.
     *
     * @var string
     */
    protected $classVersion = '1.1';

    /**
     * Array with sitemap indexes.
     *
     * Each array elements contains the XML code of the sitemap index
     *
     * @var \SimpleXMLElement[]
     */
    protected $indexes = [];

    /**
     * Array with sitemaps.
     *
     * Each array element contains the XML code of a sitemap under the 'xml'
     * key (with a maximum of MAX_URLS_PER_SITEMAP URLs and MAX_SITEMAP_SIZE
     * bytes length) and the filename under the 'filename' key.
     *
     * @var array[]
     */
    protected $sitemaps = [];

    /**
     * Array with URLs.
     *
     * @var \SplFixedArray of strings
     */
    protected $urls;

    /**
     * Constructor.
     *
     * @param string $baseURL Site URL, with / at the end if needed.
     * @param string $basePath Relative path where sitemaps should be stored.
     */
    public function __construct($baseURL, $basePath = '')
    {
        $this->urls = new \SplFixedArray();
        $this->baseURL = $baseURL;
        $this->basePath = $basePath;
        $this::$generatorInfo = '<!-- generator="TheIggs/SitemapGenerator" -->'
            . '<!-- generator-version="' . $this->classVersion . '" -->'
            . '<!-- generated-on="' . date('c') . '" -->';
    }

    /**
     * Adds multiple URLs to the sitemap.
     *
     * Each inside array can have 1 to 4 fields.
     *
     * @param array[] $urlsArray
     * @return void
     * @throws \InvalidArgumentException
     */
    public function addUrls($urlsArray)
    {
        if (!is_array($urlsArray)) {
            throw new \InvalidArgumentException(
                'Array as argument should be given.'
            );
        }
        foreach ($urlsArray as $url) {
            $this->addUrl(
                isset($url[0]) ? $url[0] : null,
                isset($url[1]) ? $url[1] : null,
                isset($url[2]) ? $url[2] : null,
                isset($url[3]) ? $url[3] : null
            );
        }
    }

    /**
     * Adds a single URL to the sitemap.
     *
     * @param string $url URL
     * @param string $lastModified When it was last modified, use ISO 8601
     * @param string $changeFrequency How often search engines should revisit this URL
     * @param string $priority Priority of URL on your site
     * @return void
     * @see http://en.wikipedia.org/wiki/ISO_8601
     * @see http://php.net/manual/en/function.date.php
     * @throws \InvalidArgumentException
     * @throws \LengthException
     */
    public function addUrl($url, $lastModified = null, $changeFrequency = null,
        $priority = null
    ) {
        if (is_null($url)) {
            throw new \InvalidArgumentException(
                'URL is mandatory. At least one argument should be given.'
            );
        }
        if (strlen($url) > 2048) {
            throw new \LengthException(
                "URL length must not be bigger than 2048 characters."
            );
        }
        $element = new \SplFixedArray(1);
        $element[static::URL_PARAM_LOC] = $url;
        if (isset($priority)) {
            $element->setSize(4);
            $element[static::URL_PARAM_LASTMOD] = $lastModified;
            $element[static::URL_PARAM_CHANGEFREQ] = $changeFrequency;
            $element[static::URL_PARAM_PRIORITY] = $priority;
        } elseif (isset($changeFrequency)) {
            $element->setSize(3);
            $element[static::URL_PARAM_LASTMOD] = $lastModified;
            $element[static::URL_PARAM_CHANGEFREQ] = $changeFrequency;
        } elseif (isset($lastModified)) {
            $element->setSize(2);
            $element[static::URL_PARAM_LASTMOD] = $lastModified;
        }
        $this->urls->setSize($this->urls->getSize() + 1);
        $this->urls[$this->urls->key()] = $element;
        $this->urls->next();
    }

    /**
     * Creates the sitemap.
     *
     * Creates XML code for all added URLs. XML code is split into several
     * chunks if needed. Sitemap index is created automatically if there are
     * more than two chunks. Filenames are assigned for all sitemap chunks and
     * indexes.
     *
     * @return void
     * @throws \BadMethodCallException
     */
    public function createSitemap()
    {
        if (count($this->urls) < 1) {
            throw new \BadMethodCallException(
                'To create a sitemap, first add URLs with the addUrl()'
                . ' or addUrls() functions.'
            );
        }
        foreach ($this->urls as $url) {
            $this->pushUrl($url);
        }
        $postfix = $this->createGzipFile ? '.gz' : '';
        if (count($this->sitemaps) > 1) {
            $sitemapCounter = 1;
            foreach ($this->sitemaps as &$sitemap) {
                $filename = str_replace(
                    '.xml',
                    $sitemapCounter++ . '.xml',
                    $this->sitemapFilename
                );
                $sitemap['filename'] = $filename . $postfix;
            }
            unset($sitemap);
            $this->createIndex();
        } else {
            $this->sitemaps[0]['filename'] = $this->sitemapFilename . $postfix;
        }
    }

    /**
     * Returns sitemap URLs in a format suitable to put in the robots.txt.
     *
     * If there is only one sitemap file (and hence no index has been created),
     * returns its filename. If there is one or more index files, returns
     * index filenames.
     *
     * @return string[]
     */
    public function getSitemapUrls()
    {
        if (!$this->sitemaps) {
            throw new \BadMethodCallException(
                'To get sitemap URLs, first create a sitemap with'
                . ' the createSitemap() function.'
            );
        }
        if (count($this->sitemaps) < 2) {
            return [$this->baseURL . $this->sitemaps[0]['filename']];
        }
        $urls = array_column($this->indexes, 'filename');
        foreach ($urls as &$url) {
            $url = $this->baseURL . $url;
        }
        unset($url);
        return $urls;
    }

    /**
     * Writes created sitemap to files.
     *
     * @return void
     * @throws \BadMethodCallException
     */
    public function writeSitemap()
    {
        if (!$this->sitemaps) {
            throw new \BadMethodCallException(
                'To write a sitemap, first create it with the createSitemap()'
                . ' function.'
            );
        }
        foreach ($this->sitemaps as $sitemap) {
            if ($this->createGzipFile) {
                $this->writeGzipFile($sitemap['xml'], $sitemap['filename']);
            } else {
                $this->writeXmlFile($sitemap['xml'], $sitemap['filename']);
            }
        }
        foreach ($this->indexes as $index) {
            $this->writeXmlFile($index['xml'], $index['filename']);
        }
    }

    /**
     * Adds an element to the array of sitemap indexes.
     *
     * @return void
     */
    protected function addIndex()
    {
        $sitemapIndexHeader = '<?xml version="1.0" encoding="UTF-8"?>'
            . ($this->includeGeneratorInfo ? $this::$generatorInfo : '')
            . '<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            . ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9'
            . ' http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd"'
            . ' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            . '</sitemapindex>';
        $this->indexes[] = [
            'xml'      => new \SimpleXMLElement($sitemapIndexHeader),
            'filename' => '',
        ];
    }

    /**
     * Adds an element to the array of sitemaps.
     *
     * @return void
     */
    protected function addSitemap()
    {
        $sitemapHeader = '<?xml version="1.0" encoding="UTF-8"?>'
            . ($this->includeGeneratorInfo ? $this::$generatorInfo : '')
            . '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            . ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9'
            . ' http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"'
            . ' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
        $this->sitemaps[] = [
            'xml'      => new \SimpleXMLElement($sitemapHeader),
            'filename' => '',
        ];
    }

    /**
     * Gets XML for the actual chunk of the sitemap index.
     *
     * @return \SimpleXMLElement
     */
    protected function getIndex()
    {
        if (!$this->indexes) {
            $this->addIndex();
        }
        $index = end($this->indexes);
        return $index['xml'];
    }

    /**
     * Gets XML for the actual chunk of the sitemap.
     *
     * @return \SimpleXMLElement
     */
    protected function getSitemap()
    {
        if (!$this->sitemaps) {
            $this->addSitemap();
        }
        $sitemap = end($this->sitemaps);
        return $sitemap['xml'];
    }

    /**
     * Pushes a sitemap to the actual sitemap index chunk.
     *
     * @param array $sitemap Sitemap.
     * @return void
     */
    protected function pushSitemap(array $sitemap)
    {
        $index = $this->getIndex();
        while (!$this->pushSitemapToIndex($index, $sitemap)) {
            $this->addIndex();
            $index = $this->getIndex();
        }
    }

    /**
     * Pushes a sitemap to sitemap index.
     *
     * @param \SimpleXMLElement $index Sitemap index XML.
     * @param array $sitemap Sitemap element.
     * @return bool
     */
    protected function pushSitemapToIndex(\SimpleXMLElement $index, array $sitemap)
    {
        if ($index->count() >= static::MAX_SITEMAPS_PER_INDEX) {
            return false;
        }
        $row = $index->addChild('sitemap');
        $row->addChild('loc', $this->baseURL . htmlentities($sitemap['filename']));
        $row->addChild('lastmod', date('c'));
        if (strlen($index->asXML()) > static::MAX_INDEX_SIZE) {
            unset($index->sitemap[$index->count() - 1]);
            return false;
        }
        return true;
    }

    /**
     * Pushes an URL to the actual sitemap chunk.
     *
     * @param \SplFixedArray $url
     * @return void
     */
    protected function pushUrl(\SplFixedArray $url)
    {
        $sitemap = $this->getSitemap();
        while (!$this->pushUrlToSitemap($sitemap, $url)) {
            $this->addSitemap();
            $sitemap = $this->getSitemap();
        }
    }

    /**
     * Pushes an URL to the sitemap.
     *
     * @param \SimpleXMLElement $sitemap Sitemap XML.
     * @param \SplFixedArray $url URL data.
     * @return bool
     */
    protected function pushUrlToSitemap(\SimpleXMLElement $sitemap,
        \SplFixedArray $url
    ) {
        if ($sitemap->count() >= static::MAX_URLS_PER_SITEMAP) {
            return false;
        }
        $row = $sitemap->addChild('url');
        $row->addChild(
            'loc',
            htmlspecialchars(
                $url[static::URL_PARAM_LOC],
                ENT_QUOTES,
                'UTF-8'
            )
        );
        if (
            isset($url[static::URL_PARAM_LASTMOD])
            && !is_null($url[static::URL_PARAM_LASTMOD])
        ) {
            $row->addChild('lastmod', $url[static::URL_PARAM_LASTMOD]);
        }
        if (
            isset($url[static::URL_PARAM_CHANGEFREQ])
            && !is_null($url[static::URL_PARAM_CHANGEFREQ])
        ) {
            $row->addChild('changefreq', $url[static::URL_PARAM_CHANGEFREQ]);
        }
        if (
            isset($url[static::URL_PARAM_PRIORITY])
            && !is_null($url[static::URL_PARAM_PRIORITY])
        ) {
            $row->addChild('priority', $url[static::URL_PARAM_PRIORITY]);
        }
//        if (strlen($sitemap->asXML()) > static::MAX_SITEMAP_SIZE) {
//            unset($sitemap->url[$sitemap->count() - 1]);
//            return false;
//        }
        return true;
    }

    /**
     * Creates sitemap index.
     *
     * @return bool
     */
    protected function createIndex()
    {
        if (count($this->sitemaps) < 2) {
            return false;
        }
        foreach ($this->sitemaps as $sitemap) {
            $this->pushSitemap($sitemap);
        }
        if (count($this->indexes) > 1) {
            $indexCounter = 1;
            foreach ($this->indexes as &$index) {
                $index['filename'] = str_replace(
                    '.xml',
                    $indexCounter++ . '.xml',
                    $this->indexFilename
                );
            }
            unset($index);
        } else {
            $this->indexes[0]['filename'] = $this->indexFilename;
        }
        return true;
    }

    /**
     * Saves an XML file.
     *
     * @param \SimpleXMLElement $xml The XML to write.
     * @param string $filename The filename.
     * @return bool
     */
    protected function writeXmlFile($xml, $filename)
    {
        return $xml->asXML($this->basePath . $filename);
    }

    /**
     * Saves a gzipped XML file.
     *
     * @param \SimpleXMLElement $xml The XML to write.
     * @param string $filename The filename.
     * @return bool
     */
    protected function writeGzipFile($xml, $filename)
    {
        $file = gzopen($this->basePath . $filename, 'w');
        if (!$file) {
            return false;
        }
        gzwrite($file, $xml->asXML());
        return gzclose($file);
    }
}
