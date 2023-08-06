<?php

namespace Icamys\SitemapGenerator;

use BadMethodCallException;
use DateTime;
use Icamys\SitemapGenerator\Extensions\GoogleImageExtension;
use Icamys\SitemapGenerator\Extensions\GoogleVideoExtension;
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
     * Sitemap Stylesheet link.
     * @var string
     */
    private $sitemapStylesheetLink = "";
    /**
     * Quantity of URLs per single sitemap file.
     * If Your links are very long, sitemap file can be bigger than 10MB,
     * in this case use smaller value.
     * @var int
     * @access public
     */
    private $maxUrlsPerSitemap = self::MAX_URLS_PER_SITEMAP;
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
    private $classVersion = "4.7.0";
    /**
     * Search engines URLs
     * @var array of strings
     * @access private
     */
    private $searchEngines = [
        "http://www.google.com/ping?sitemap=",
        "http://www.webmaster.yandex.ru/ping?sitemap=",
    ];
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
     * @var FileSystem object used to communicate with file system
     */
    private $fs;
    /**
     * @var Runtime object used to communicate with runtime
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
    private $totalUrlCount = 0;

    /**
     * @var int
     */
    private $urlsetClosingTagLen = 10; // strlen("</urlset>\n")
    private $sitemapUrlCount = 0;
    private $generatedFiles = [];

    /**
     * @param string $baseURL You site URL
     * @param string $basePath Relative path where sitemap and robots should be stored.
     * @param FileSystem|null $fs
     * @param Runtime|null $runtime
     */
    public function __construct(string $baseURL, string $basePath = "", FileSystem $fs = null, Runtime $runtime = null)
    {
        $this->baseURL = rtrim($baseURL, '/');

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

        if ($this->runtime->is_writable($basePath) === false) {
            throw new InvalidArgumentException(
                sprintf('the provided basePath (%s) should be a writable directory,', $basePath) .
                ' please check its existence and permissions'
            );
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
     * @param string $path
     * @return SitemapGenerator
     */
    public function setSitemapStylesheet(string $path): SitemapGenerator
    {
        if (strlen($path) === 0) {
            throw new InvalidArgumentException('sitemap stylesheet path should not be empty');
        }
        $this->sitemapStylesheetLink = $path;
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
    public function setMaxUrlsPerSitemap(int $value): SitemapGenerator
    {
        if ($value < 1 || self::MAX_URLS_PER_SITEMAP < $value) {
            throw new OutOfRangeException(
                sprintf('value %d is out of range 1-%d', $value, self::MAX_URLS_PER_SITEMAP)
            );
        }
        $this->maxUrlsPerSitemap = $value;
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

    public function isCompressionEnabled(): bool
    {
        return $this->isCompressionEnabled;
    }

    public function validate(
        string   $path,
        DateTime $lastModified = null,
        string   $changeFrequency = null,
        float    $priority = null,
        array    $alternates = null,
        array    $extensions = [])
    {
        if (!(1 <= mb_strlen($path) && mb_strlen($path) <= self::MAX_URL_LEN)) {
            throw new InvalidArgumentException(
                sprintf("The urlPath argument length must be between 1 and %d.", self::MAX_URL_LEN)
            );
        }
        if ($changeFrequency !== null && !in_array($changeFrequency, $this->validChangefreqValues)) {
            throw new InvalidArgumentException(
                'The change frequency argument should be one of: %s' . implode(',', $this->validChangefreqValues)
            );
        }
        if ($priority !== null && !in_array($priority, $this->validPriorities)) {
            throw new InvalidArgumentException("Priority argument should be a float number in the range [0.0..1.0]");
        }
        if ($extensions !== null) {
            if (isset($extensions['google_video'])) {
                GoogleVideoExtension::validate($this->baseURL . $path, $extensions['google_video']);
            }

            if (isset($extensions['google_image'])) {
                GoogleImageExtension::validate($extensions['google_image']);
            }
        }
    }

    /**
     * Add url components.
     * Instead of storing all urls in the memory, the generator will flush sets of added urls
     * to the temporary files created on your disk.
     * The file format is 'sm-{index}-{timestamp}.xml'
     * @param string $path
     * @param DateTime|null $lastModified
     * @param string|null $changeFrequency
     * @param float|null $priority
     * @param array|null $alternates
     * @param array $extensions
     * @return $this
     */
    public function addURL(
        string   $path,
        DateTime $lastModified = null,
        string   $changeFrequency = null,
        float    $priority = null,
        array    $alternates = null,
        array    $extensions = []
    ): SitemapGenerator
    {
        $this->validate($path, $lastModified, $changeFrequency, $priority, $alternates, $extensions);

        if ($this->totalUrlCount >= self::TOTAL_MAX_URLS) {
            throw new OutOfRangeException(
                sprintf("Max url limit reached (%d)", self::TOTAL_MAX_URLS)
            );
        }
        if ($this->isSitemapStarted === false) {
            $this->writeSitemapStart();
        }

        $this->writeSitemapUrl($this->baseURL . $path, $lastModified, $changeFrequency, $priority, $alternates, $extensions);

        if ($this->totalUrlCount % 1000 === 0 || $this->sitemapUrlCount >= $this->maxUrlsPerSitemap) {
            $this->flushWriter();
        }

        if ($this->sitemapUrlCount === $this->maxUrlsPerSitemap) {
            $this->writeSitemapEnd();
        }

        return $this;
    }

    protected function writeSitemapStart(): void
    {
        $this->xmlWriter->startDocument("1.0", "UTF-8");
        if ($this->sitemapStylesheetLink != "") {
            $this->xmlWriter->writePi('xml-stylesheet',
                sprintf('type="text/xsl" href="%s"', $this->sitemapStylesheetLink));
        }
        $this->xmlWriter->writeComment(sprintf('generator-class="%s"', get_class($this)));
        $this->xmlWriter->writeComment(sprintf('generator-version="%s"', $this->classVersion));
        $this->xmlWriter->writeComment(sprintf('generated-on="%s"', date('c')));
        $this->xmlWriter->startElement('urlset');
        $this->xmlWriter->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->xmlWriter->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        $this->xmlWriter->writeAttribute('xmlns:video', 'http://www.google.com/schemas/sitemap-video/1.1');
        $this->xmlWriter->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
        $this->xmlWriter->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $this->xmlWriter->writeAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
        $this->isSitemapStarted = true;
    }

    private function encodeEscapeURL($url): string {
        // In-place encoding only on non-ASCII characters, like browsers do.
        $encoded = preg_replace_callback('/[^\x20-\x7f]/', function($match) {
            return urlencode($match[0]);
        }, $url);
        return htmlspecialchars($encoded, ENT_QUOTES, 'UTF-8');
    }

    private function writeSitemapUrl($loc, $lastModified, $changeFrequency, $priority, $alternates, $extensions)
    {
        $this->xmlWriter->startElement('url');
        $this->xmlWriter->writeElement('loc', $this->encodeEscapeURL($loc));

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
                    $this->xmlWriter->startElement('xhtml:link');
                    $this->xmlWriter->writeAttribute('rel', 'alternate');
                    $this->xmlWriter->writeAttribute('hreflang', $alternate['hreflang']);
                    $this->xmlWriter->writeAttribute('href', $alternate['href']);
                    $this->xmlWriter->endElement();
                }
            }
        }

        foreach ($extensions as $extName => $extFields) {
            if ($extName === 'google_video') {
                GoogleVideoExtension::writeVideoTag($this->xmlWriter, $loc, $extFields);
            }
            if ($extName === 'google_image') {
                GoogleImageExtension::writeImageTag($this->xmlWriter, $extFields);
            }
        }

        $this->xmlWriter->endElement(); // url
        $this->sitemapUrlCount++;
        $this->totalUrlCount++;
    }

    private function flushWriter()
    {
        $targetSitemapFilepath = $this->basePath . sprintf($this->flushedSitemapFilenameFormat, $this->flushedSitemapCounter);
        $flushedString = $this->xmlWriter->outputMemory(true);
        $flushedStringLen = mb_strlen($flushedString);

        if ($flushedStringLen === 0) {
            return;
        }

        $this->flushedSitemapSize += $flushedStringLen;

        if ($this->flushedSitemapSize > self::MAX_FILE_SIZE - $this->urlsetClosingTagLen) {
            $this->writeSitemapEnd();
            $this->writeSitemapStart();
        }
        $this->fs->file_put_contents($targetSitemapFilepath, $flushedString, FILE_APPEND);
    }

    private function writeSitemapEnd()
    {
        $targetSitemapFilepath = $this->basePath . sprintf($this->flushedSitemapFilenameFormat, $this->flushedSitemapCounter);
        $this->xmlWriter->endElement(); // urlset
        $this->xmlWriter->endDocument();
        $this->fs->file_put_contents($targetSitemapFilepath, $this->xmlWriter->flush(true), FILE_APPEND);
        $this->isSitemapStarted = false;
        $this->flushedSitemaps[] = $targetSitemapFilepath;
        $this->flushedSitemapCounter++;
        $this->sitemapUrlCount = 0;
        $this->flushedSitemapSize = 0;
    }

    /**
     * Flush all stored urls from memory to the disk and close all necessary tags.
     */
    public function flush()
    {
        $this->flushWriter();
        if ($this->isSitemapStarted) {
            $this->writeSitemapEnd();
        }
    }

    /**
     * Move flushed files to their final location. Compress if necessary.
     */
    public function finalize()
    {
        $this->generatedFiles = [];

        if (count($this->flushedSitemaps) === 1) {
            $targetSitemapFilename = $this->sitemapFileName;
            if ($this->isCompressionEnabled) {
                $targetSitemapFilename .= '.gz';
            }

            $targetSitemapFilepath = $this->basePath . $targetSitemapFilename;

            if ($this->isCompressionEnabled) {
                $this->fs->copy($this->flushedSitemaps[0], 'compress.zlib://' . $targetSitemapFilepath);
                $this->fs->unlink($this->flushedSitemaps[0]);
            } else {
                $this->fs->rename($this->flushedSitemaps[0], $targetSitemapFilepath);
            }
            $this->generatedFiles['sitemaps_location'] = [$targetSitemapFilepath];
            $this->generatedFiles['sitemaps_index_url'] = $this->baseURL . '/' . $targetSitemapFilename;
        } else if (count($this->flushedSitemaps) > 1) {
            $ext = '.' . pathinfo($this->sitemapFileName, PATHINFO_EXTENSION);
            $targetExt = $ext;
            if ($this->isCompressionEnabled) {
                $targetExt .= '.gz';
            }

            $sitemapsUrls = [];
            $targetSitemapFilepaths = [];
            foreach ($this->flushedSitemaps as $i => $flushedSitemap) {
                $targetSitemapFilename = str_replace($ext, ($i + 1) . $targetExt, $this->sitemapFileName);
                $targetSitemapFilepath = $this->basePath . $targetSitemapFilename;

                if ($this->isCompressionEnabled) {
                    $this->fs->copy($flushedSitemap, 'compress.zlib://' . $targetSitemapFilepath);
                    $this->fs->unlink($flushedSitemap);
                } else {
                    $this->fs->rename($flushedSitemap, $targetSitemapFilepath);
                }
                $sitemapsUrls[] = htmlspecialchars($this->baseURL . '/' . $targetSitemapFilename, ENT_QUOTES);
                $targetSitemapFilepaths[] = $targetSitemapFilepath;
            }

            $targetSitemapIndexFilepath = $this->basePath . $this->sitemapIndexFileName;
            $this->createSitemapIndex($sitemapsUrls, $targetSitemapIndexFilepath);
            $this->generatedFiles['sitemaps_location'] = $targetSitemapFilepaths;
            $this->generatedFiles['sitemaps_index_location'] = $targetSitemapIndexFilepath;
            $this->generatedFiles['sitemaps_index_url'] = $this->baseURL . '/' . $this->sitemapIndexFileName;
        } else {
            throw new RuntimeException('failed to finalize, please add urls and flush first');
        }
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
        );
    }

    protected function writeSitemapIndexStart()
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
        $this->xmlWriter->writeElement('loc', $this->encodeEscapeURL($url));
        $this->xmlWriter->writeElement('lastmod', date('c'));
        $this->xmlWriter->endElement(); // sitemap
    }

    private function writeSitemapIndexEnd()
    {
        $this->xmlWriter->endElement(); // sitemapindex
        $this->xmlWriter->endDocument();
    }

    /**
     * @return array Array of previously generated files
     */
    public function getGeneratedFiles(): array
    {
        return $this->generatedFiles;
    }

    /**
     * Will inform search engines about newly created sitemaps.
     * Google and Yandex will be notified.
     * @param string $yahooAppId Your site Yahoo appid. This is a deprecated parameter and will be removed in future versions.
     * @return array of messages and http codes from each search engine
     * @access public
     * @throws BadMethodCallException
     */
    public function submitSitemap($yahooAppId = null): array
    {
        if (count($this->generatedFiles) === 0) {
            throw new BadMethodCallException("To update robots.txt, call finalize() first.");
        }
        if (!$this->runtime->extension_loaded('curl')) {
            throw new BadMethodCallException("curl extension is needed to do submission.");
        }
        $searchEngines = $this->searchEngines;
        $result = [];
        for ($i = 0; $i < count($searchEngines); $i++) {
            $submitUrl = $searchEngines[$i] . htmlspecialchars($this->generatedFiles['sitemaps_index_url'], ENT_QUOTES);
            $curlResource = $this->runtime->curl_init($submitUrl);
            if ($curlResource == false) {
                throw new RuntimeException("failed to execute curl_init for url " . $submitUrl);
            }
            if ($this->runtime->curl_setopt($curlResource, CURLOPT_RETURNTRANSFER, true) == false) {
                throw new RuntimeException(
                    "failed to set curl option CURLOPT_RETURNTRANSFER to true, error: "
                    . $this->runtime->curl_error($curlResource)
                );
            }
            $responseContent = $this->runtime->curl_exec($curlResource);
            if ($responseContent == false) {
                throw new RuntimeException(
                    "failed to run curl_exec, error: " . $this->runtime->curl_error($curlResource)
                );
            }
            $response = $this->runtime->curl_getinfo($curlResource);
            $submitSiteShort = array_reverse(explode(".", parse_url($searchEngines[$i], PHP_URL_HOST)));
            $result[] = [
                "site" => $submitSiteShort[1] . "." . $submitSiteShort[0],
                "fullsite" => $submitUrl,
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
        if (count($this->generatedFiles) === 0) {
            throw new BadMethodCallException("To update robots.txt, call finalize() first.");
        }

        $robotsFilePath = $this->basePath . $this->robotsFileName;

        $robotsFileContent = $this->createNewRobotsContentFromFile($robotsFilePath);

        $this->fs->file_put_contents($robotsFilePath, $robotsFileContent);

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

        $robotsFileContent .= "Sitemap: {$this->generatedFiles['sitemaps_index_url']}";

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
