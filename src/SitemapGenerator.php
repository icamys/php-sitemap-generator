<?php

namespace Icamys\SitemapGenerator;

use BadMethodCallException;
use DateTime;
use DOMDocument;
use InvalidArgumentException;
use OutOfRangeException;
use RuntimeException;
use XMLWriter;

/**
 * Class SitemapGenerator
 * @package Icamys\SitemapGenerator
 */
class SitemapGenerator
{
    /**
     * Max size of a sitemap according to spec.
     * @see https://www.sitemaps.org/protocol.html
     */
    private const MAX_FILE_SIZE = 52428800;

    /**
     * Max number of urls per sitemap according to spec.
     * @see https://www.sitemaps.org/protocol.html
     */
    private const MAX_URLS_PER_SITEMAP = 50000;

    /**
     * Max number of sitemaps per index file according to spec.
     * @see http://www.sitemaps.org/protocol.html
     */
    private const MAX_SITEMAPS_PER_INDEX = 50000;

    /**
     * Total max number of URLs.
     */
    private const TOTAL_MAX_URLS = self::MAX_URLS_PER_SITEMAP * self::MAX_SITEMAPS_PER_INDEX;

    /**
     * Max url length according to spec.
     * @see https://www.sitemaps.org/protocol.html#xmlTagDefinitions
     */
    private const MAX_URL_LEN = 2048;

    /**
     * Robots file name
     * @var string
     * @access public
     */
    private $robotsFileName = "robots.txt";
    /**
     * Name of sitemap file
     * @var string
     * @access public
     */
    private $sitemapFileName = "sitemap.xml";
    /**
     * Name of sitemap index file
     * @var string
     * @access public
     */
    private $sitemapIndexFileName = "sitemap-index.xml";
    /**
     * Quantity of URLs per single sitemap file.
     * If Your links are very long, sitemap file can be bigger than 10MB,
     * in this case use smaller value.
     * @var int
     * @access public
     */
    private $maxURLsPerSitemap = self::MAX_URLS_PER_SITEMAP;
    /**
     * If true, two sitemap files (.xml and .xml.gz) will be created and added to robots.txt.
     * If true, .gz file will be submitted to search engines.
     * If quantity of URLs will be bigger than 50.000, option will be ignored,
     * all sitemap files except sitemap index will be compressed.
     * @var bool
     * @access public
     */
    private $isCompressionEnabled = false;
    /**
     * URL to Your site.
     * Script will use it to send sitemaps to search engines.
     * @var string
     * @access private
     */
    private $baseURL;
    /**
     * Base path. Relative to script location.
     * Use this if Your sitemap and robots files should be stored in other
     * directory then script.
     * @var string
     * @access private
     */
    private $basePath;
    /**
     * Version of this class
     * @var string
     * @access private
     */
    private $classVersion = "3.0.4";
    /**
     * Search engines URLs
     * @var array of strings
     * @access private
     */
    private $searchEngines = [
        [
            "http://search.yahooapis.com/SiteExplorerService/V1/updateNotification?appid=USERID&url=",
            "http://search.yahooapis.com/SiteExplorerService/V1/ping?sitemap=",
        ],
        "http://www.google.com/ping?sitemap=",
        "http://submissions.ask.com/ping?sitemap=",
        "http://www.bing.com/ping?sitemap=",
        "http://www.webmaster.yandex.ru/ping?sitemap=",
    ];
    /**
     * Array with urls
     * @var array
     * @access private
     */
    private $urls;
    /**
     * Array with sitemap
     * @var array of strings
     * @access private
     */
    private $sitemaps = [];
    /**
     * Current sitemap full URL
     * @var string
     * @access private
     */
    private $sitemapFullURL;
    /**
     * @var DOMDocument
     */
    private $document;
    /**
     * Lines for robots.txt file that are written if file does not exist
     * @var array
     */
    private $sampleRobotsLines = [
        "User-agent: *",
        "Allow: /",
    ];
    /**
     * @var array list of valid changefreq values according to the spec
     */
    private $validChangefreqValues = [
        'always',
        'hourly',
        'daily',
        'weekly',
        'monthly',
        'yearly',
        'never',
    ];
    /**
     * @var float[] list of valid priority values according to the spec
     */
    private $validPriorities = [
        0.0,
        0.1,
        0.2,
        0.3,
        0.4,
        0.5,
        0.6,
        0.7,
        0.8,
        0.9,
        1.0,
    ];
    /**
     * @var FileSystemInterface object used to communicate with file system
     */
    private $fs;
    /**
     * @var RuntimeInterface object used to communicate with runtime
     */
    private $runtime;

    /**
     * @var XMLWriter Used for writing xml to files
     */
    private $xmlWriter;

    /**
     * @var string
     */
    private $flushedSitemapFilenameFormat;

    /**
     * @var int
     */
    private $flushedSitemapSize = 0;

    /**
     * @var int
     */
    private $flushedSitemapCounter = 0;

    /**
     * @var array
     */
    private $flushedSitemaps = [];

    /**
     * @var bool
     */
    private $isSitemapStarted = false;

    /**
     * @var int
     */
    private $urlCount = 0;

    /**
     * @var int
     */
    private $urlsetClosingTagLen = 10; // strlen("</urlset>\n")

    /**
     * @param string $baseURL You site URL
     * @param string $basePath Relative path where sitemap and robots should be stored.
     * @param FileSystemInterface|null $fs
     * @param RuntimeInterface|null $runtime
     */
    public function __construct(string $baseURL, string $basePath = "", FileSystemInterface $fs = null, RuntimeInterface $runtime = null)
    {
        $this->urls = [];
        $this->baseURL = rtrim($baseURL, '/');
        $this->document = new DOMDocument("1.0");
        $this->document->preserveWhiteSpace = false;
        $this->document->formatOutput = true;

        if ($fs === null) {
            $this->fs = new FileSystem();
        } else {
            $this->fs = $fs;
        }

        if ($runtime === null) {
            $this->runtime = new Runtime();
        } else {
            $this->runtime = $runtime;
        }

        if (strlen($basePath) > 0 && substr($basePath, -1) != DIRECTORY_SEPARATOR) {
            $basePath = $basePath . DIRECTORY_SEPARATOR;
        }
        $this->basePath = $basePath;

        $this->xmlWriter = $this->createXmlWriter();
        $this->flushedSitemapFilenameFormat = sprintf("sm-%%d-%d.xml", time());
    }

    private function createXmlWriter(): XMLWriter
    {
        $w = new XMLWriter();
        $w->openMemory();
        $w->setIndent(true);
        return $w;
    }

    /**
     * @param string $filename
     * @return SitemapGenerator
     */
    public function setSitemapFilename(string $filename = ''): SitemapGenerator
    {
        if (strlen($filename) === 0) {
            throw new InvalidArgumentException('sitemap filename should not be empty');
        }
        if (pathinfo($filename, PATHINFO_EXTENSION) !== 'xml') {
            throw new InvalidArgumentException('sitemap filename should have *.xml extension');
        }
        $this->sitemapFileName = $filename;
        return $this;
    }

    /**
     * @param string $filename
     * @return $this
     */
    public function setSitemapIndexFilename(string $filename = ''): SitemapGenerator
    {
        if (strlen($filename) === 0) {
            throw new InvalidArgumentException('filename should not be empty');
        }
        $this->sitemapIndexFileName = $filename;
        return $this;
    }

    /**
     * @param string $filename
     * @return $this
     */
    public function setRobotsFileName(string $filename): SitemapGenerator
    {
        if (strlen($filename) === 0) {
            throw new InvalidArgumentException('filename should not be empty');
        }
        $this->robotsFileName = $filename;
        return $this;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setMaxURLsPerSitemap(int $value): SitemapGenerator
    {
        if ($value < 1 || self::MAX_URLS_PER_SITEMAP < $value) {
            throw new OutOfRangeException(
                sprintf('value %d is out of range 1-%d', $value, self::MAX_URLS_PER_SITEMAP)
            );
        }
        $this->maxURLsPerSitemap = $value;
        return $this;
    }

    public function enableCompression(): SitemapGenerator
    {
        $this->isCompressionEnabled = true;
        return $this;
    }

    public function disableCompression(): SitemapGenerator
    {
        $this->isCompressionEnabled = false;
        return $this;
    }

    /**
     * Add url components.
     * Instead of storing all urls in the memory, the generator will flush sets of added urls
     * to the temporary files created on your disk.
     * The file format is 'sm-{index}-{timestamp}.xml'
     * @param string $loc
     * @param DateTime|null $lastModified
     * @param string|null $changeFrequency
     * @param float|null $priority
     * @param array|null $alternates
     * @return $this
     */
    public function addURL(
        string $loc,
        DateTime $lastModified = null,
        string $changeFrequency = null,
        float $priority = null,
        array $alternates = null
    ): SitemapGenerator
    {
        if ($this->urlCount >= self::TOTAL_MAX_URLS) {
            throw new OutOfRangeException(
                sprintf("Max url limit reached (%d)", self::TOTAL_MAX_URLS)
            );
        }
        if ($this->isValidLocValue($loc) === false) {
            throw new InvalidArgumentException(
                sprintf("loc parameter length should be between 1 and %d", self::MAX_URL_LEN)
            );
        }
        if ($changeFrequency !== null && $this->isValidChangefreqValue($changeFrequency) === false) {
            throw new InvalidArgumentException(
                'invalid change frequency passed, valid values are: %s' . implode(',', $this->validChangefreqValues)
            );
        }
        if ($priority !== null && $this->isValidPriorityValue($priority) === false) {
            throw new InvalidArgumentException("priority should be a float number in the range [0.0..1.0]");
        }

        if ($this->isSitemapStarted === false) {
            $this->writeSitemapStart();
        }

        $this->writeSitemapUrl($loc, $lastModified, $changeFrequency, $priority, $alternates);
        $this->urlCount++;

        if ($this->urlCount % 1000 === 0 || $this->urlCount >= $this->maxURLsPerSitemap) {
            $this->flushSitemap();
        }

        if ($this->urlCount === self::MAX_URLS_PER_SITEMAP) {
            $this->writeSitemapEnd();
        }

        return $this;
    }

    public function isValidLocValue($value): bool
    {
        return 1 <= mb_strlen($value) && mb_strlen($value) <= self::MAX_URL_LEN;
    }

    public function isValidChangefreqValue($value): bool
    {
        return in_array($value, $this->validChangefreqValues);
    }

    public function isValidPriorityValue(float $value): bool
    {
        return in_array($value, $this->validPriorities);
    }

    private function writeSitemapStart()
    {
        $this->xmlWriter->startDocument("1.0", "UTF-8");
        $this->xmlWriter->writeComment(sprintf('generator-class="%s"', get_class($this)));
        $this->xmlWriter->writeComment(sprintf('generator-version="%s"', $this->classVersion));
        $this->xmlWriter->writeComment(sprintf('generated-on="%s"', date('c')));
        $this->xmlWriter->startElement('urlset');
        $this->xmlWriter->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->xmlWriter->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $this->xmlWriter->writeAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
        $this->isSitemapStarted = true;
    }

    private function writeSitemapUrl($loc, $lastModified, $changeFrequency, $priority, $alternates)
    {
        $this->xmlWriter->startElement('url');
        $this->xmlWriter->writeElement('loc', htmlspecialchars($this->baseURL . $loc, ENT_QUOTES));

        if ($lastModified !== null) {
            $this->xmlWriter->writeElement('lastmod', $lastModified->format(DateTime::ATOM));
        }

        if ($changeFrequency !== null) {
            $this->xmlWriter->writeElement('changefreq', $changeFrequency);
        }

        if ($priority !== null) {
            $this->xmlWriter->writeElement('priority', number_format($priority, 1, ".", ""));
        }

        if (is_array($alternates) && count($alternates) > 0) {
            foreach ($alternates as $alternate) {
                if (is_array($alternate) && isset($alternate['hreflang']) && isset($alternate['href'])) {
                    $this->xmlWriter->startElement('link');
                    $this->xmlWriter->writeAttribute('rel', 'alternate');
                    $this->xmlWriter->writeAttribute('hreflang', $alternate['hreflang']);
                    $this->xmlWriter->writeAttribute('href', $alternate['href']);
                    $this->xmlWriter->endElement();
                }
            }
        }

        $this->xmlWriter->endElement(); // url
    }

    private function flushSitemap()
    {
        $flushedXmlString = $this->xmlWriter->outputMemory(true);
        $this->flushedSitemapSize += mb_strlen($flushedXmlString);

        if ($this->flushedSitemapSize > self::MAX_FILE_SIZE - $this->urlsetClosingTagLen) {
            $this->writeSitemapEnd();
            $this->writeSitemapStart();
        }
        $this->fs->file_put_contents(
            sprintf($this->flushedSitemapFilenameFormat, $this->flushedSitemapCounter),
            $flushedXmlString,
            FILE_APPEND
        );
    }

    private function writeSitemapEnd()
    {
        $this->xmlWriter->endElement(); // urlset
        $this->xmlWriter->endDocument();
        $this->fs->file_put_contents(
            sprintf($this->flushedSitemapFilenameFormat, $this->flushedSitemapCounter),
            $this->xmlWriter->flush(true),
            FILE_APPEND
        );
        $this->isSitemapStarted = false;
        $this->flushedSitemaps[] = sprintf($this->flushedSitemapFilenameFormat, $this->flushedSitemapCounter);
        $this->flushedSitemapCounter++;
        $this->flushedSitemapSize = 0;
    }

    /**
     * Flush all stored urls from memory to the disk and close all necessary tags.
     */
    public function flush()
    {
        $this->flushSitemap();
        $this->writeSitemapEnd();
    }

    /**
     * Move flushed files to their final location. Compress if necessary.
     * @return array The generated files array
     */
    public function finalize(): array
    {
        $generatedFiles = [];

        if (count($this->flushedSitemaps) === 1) {
            $targetSitemapLocation = $this->basePath . $this->sitemapFileName;
            if ($this->isCompressionEnabled) {
                $this->fs->copy($this->flushedSitemaps[0], 'compress.zlib://' . $targetSitemapLocation . '.gz');
                $this->fs->unlink($this->flushedSitemaps[0]);
            } else {
                $this->fs->rename($this->flushedSitemaps[0], $targetSitemapLocation);
            }
            $generatedFiles['sitemaps'] = [$targetSitemapLocation];
        } else if (count($this->flushedSitemaps) > 1) {
            $ext = '.' . pathinfo($this->sitemapFileName, PATHINFO_EXTENSION);
            $targetExt = $ext;
            if ($this->isCompressionEnabled) {
                $targetExt .= '.gz';
            }

            $sitemapsUrls = [];
            $targetSitemapsLocations = [];
            foreach ($this->flushedSitemaps as $i => $flushedSitemap) {
                $targetSitemapFilename = str_replace($ext, ($i + 1) . $targetExt, $this->sitemapFileName);
                $targetSitemapLocation = $this->basePath . $targetSitemapFilename;

                if ($this->isCompressionEnabled) {
                    $this->fs->copy($flushedSitemap, 'compress.zlib://' . $targetSitemapLocation);
                    $this->fs->unlink($flushedSitemap);
                } else {
                    $this->fs->rename($flushedSitemap, $targetSitemapLocation);
                }
                $sitemapsUrls[] = htmlspecialchars($this->baseURL . '/' . $targetSitemapFilename, ENT_QUOTES);
                $targetSitemapsLocations[] = $targetSitemapLocation;
            }
            $targetSitemapIndexLocation = $this->basePath . $this->sitemapIndexFileName;

            $this->createSitemapIndex($sitemapsUrls, $targetSitemapIndexLocation);

            if ($this->isCompressionEnabled) {
                $this->fs->copy($targetSitemapIndexLocation, 'compress.zlib://' . $targetSitemapIndexLocation . '.gz');
            }

            $generatedFiles['sitemaps'] = $targetSitemapsLocations;
            $generatedFiles['sitemaps_index'] = $targetSitemapIndexLocation;
        } else {
            throw new RuntimeException('failed to finalize, please add urls and flush first');
        }

        return $generatedFiles;
    }

    private function createSitemapIndex($sitemapsUrls, $sitemapIndexFileName)
    {
        $this->xmlWriter->flush(true);
        $this->writeSitemapIndexStart();
        foreach ($sitemapsUrls as $sitemapsUrl) {
            $this->writeSitemapIndexUrl($sitemapsUrl);
        }
        $this->writeSitemapIndexEnd();
        $this->fs->file_put_contents(
            $sitemapIndexFileName,
            $this->xmlWriter->flush(true),
            FILE_APPEND
        );
    }

    private function writeSitemapIndexStart()
    {
        $this->xmlWriter->startDocument("1.0", "UTF-8");
        $this->xmlWriter->writeComment(sprintf('generator-class="%s"', get_class($this)));
        $this->xmlWriter->writeComment(sprintf('generator-version="%s"', $this->classVersion));
        $this->xmlWriter->writeComment(sprintf('generated-on="%s"', date('c')));
        $this->xmlWriter->startElement('sitemapindex');
        $this->xmlWriter->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->xmlWriter->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $this->xmlWriter->writeAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
    }

    private function writeSitemapIndexUrl($url)
    {
        $this->xmlWriter->startElement('sitemap');
        $this->xmlWriter->writeElement('loc', htmlspecialchars($url, ENT_QUOTES));
        $this->xmlWriter->writeElement('lastmod', date('c'));
        $this->xmlWriter->endElement(); // sitemap
    }

    private function writeSitemapIndexEnd()
    {
        $this->xmlWriter->endElement(); // sitemapindex
        $this->xmlWriter->endDocument();
    }

    /**
     * Will inform search engines about newly created sitemaps.
     * Google, Ask, Bing and Yahoo will be noticed.
     * If You don't pass yahooAppId, Yahoo still will be informed,
     * but this method can be used once per day. If You will do this often,
     * message that limit was exceeded  will be returned from Yahoo.
     * @param string $yahooAppId Your site Yahoo appid.
     * @return array of messages and http codes from each search engine
     * @access public
     * @throws BadMethodCallException
     */
    public function submitSitemap($yahooAppId = null): array
    {
        if (count($this->sitemaps) === 0) {
            throw new BadMethodCallException("To submit sitemap, call createSitemap function first.");
        }
        if (!$this->runtime->extension_loaded('curl')) {
            throw new BadMethodCallException("cURL extension is needed to do submission.");
        }
        $searchEngines = $this->searchEngines;
        $searchEngines[0] = isset($yahooAppId) ?
            str_replace("USERID", $yahooAppId, $searchEngines[0][0]) :
            $searchEngines[0][1];
        $result = [];
        for ($i = 0; $i < count($searchEngines); $i++) {
            $submitSite = curl_init($searchEngines[$i] . htmlspecialchars($this->sitemapFullURL, ENT_QUOTES));
            curl_setopt($submitSite, CURLOPT_RETURNTRANSFER, true);
            $responseContent = curl_exec($submitSite);
            $response = curl_getinfo($submitSite);
            $submitSiteShort = array_reverse(explode(".", parse_url($searchEngines[$i], PHP_URL_HOST)));
            $result[] = [
                "site" => $submitSiteShort[1] . "." . $submitSiteShort[0],
                "fullsite" => $searchEngines[$i] . htmlspecialchars($this->sitemapFullURL, ENT_QUOTES),
                "http_code" => $response['http_code'],
                "message" => str_replace("\n", " ", strip_tags($responseContent)),
            ];
        }
        return $result;
    }

    /**
     * Adds sitemap url to robots.txt file located in basePath.
     * If robots.txt file exists,
     *      the function will append sitemap url to file.
     * If robots.txt does not exist,
     *      the function will create new robots.txt file with sample content and sitemap url.
     * @access public
     * @throws BadMethodCallException
     * @throws RuntimeException
     */
    public function updateRobots(): SitemapGenerator
    {
        if (count($this->sitemaps) === 0) {
            throw new BadMethodCallException("To update robots.txt, call createSitemap function first.");
        }

        $robotsFilePath = $this->basePath . $this->robotsFileName;

        $robotsFileContent = $this->createNewRobotsContentFromFile($robotsFilePath);

        if (false === $this->fs->file_put_contents($robotsFilePath, $robotsFileContent)) {
            throw new RuntimeException(
                "Failed to write new contents of robots.txt to file $robotsFilePath. "
                . "Please check file permissions and free space presence."
            );
        }

        return $this;
    }

    /**
     * @param $filepath
     * @return string
     */
    private function createNewRobotsContentFromFile($filepath): string
    {
        if ($this->fs->file_exists($filepath)) {
            $robotsFileContent = "";
            $robotsFile = explode(PHP_EOL, $this->fs->file_get_contents($filepath));
            foreach ($robotsFile as $key => $value) {
                if (substr($value, 0, 8) == 'Sitemap:') {
                    unset($robotsFile[$key]);
                } else {
                    $robotsFileContent .= $value . PHP_EOL;
                }
            }
        } else {
            $robotsFileContent = $this->getSampleRobotsContent();
        }

        $robotsFileContent .= "Sitemap: $this->sitemapFullURL";

        return $robotsFileContent;
    }

    /**
     * @return string
     * @access private
     */
    private function getSampleRobotsContent(): string
    {
        return implode(PHP_EOL, $this->sampleRobotsLines) . PHP_EOL;
    }
}
