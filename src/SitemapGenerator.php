<?php

namespace Icamys\SitemapGenerator;

use BadMethodCallException;
use DateTime;
use Icamys\SitemapGenerator\Extensions\GoogleImageExtension;
use Icamys\SitemapGenerator\Extensions\GoogleVideoExtension;
use InvalidArgumentException;
use OutOfRangeException;
use RuntimeException;
use UnexpectedValueException;
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
    private string $robotsFileName = "robots.txt";
    /**
     * Name of sitemap file
     * @var string
     * @access public
     */
    private string $sitemapFileName = "sitemap.xml";
    /**
     * Name of sitemap index file
     * @var string
     * @access public
     */
    private string $sitemapIndexFileName = "sitemap-index.xml";
    /**
     * Sitemap Stylesheet link.
     * @var string
     */
    private string $sitemapStylesheetLink = "";
    /**
     * Quantity of URLs per single sitemap file.
     * If Your links are very long, sitemap file can be bigger than 10MB,
     * in this case use smaller value.
     * @var int
     * @access public
     */
    private int $maxURLsPerSitemap = self::MAX_URLS_PER_SITEMAP;
    /**
     * If true, two sitemap files (.xml and .xml.gz) will be created and added to robots.txt.
     * If true, .gz file will be submitted to search engines.
     * If quantity of URLs will be bigger than 50.000, option will be ignored,
     * all sitemap files except sitemap index will be compressed.
     * @var bool
     * @access public
     */
    private bool $isCompressionEnabled = false;
    /**
     * URL to Your site.
     * Script will use it to send sitemaps to search engines.
     * @var string
     * @access private
     */
    private string $baseURL;
    /**
     * URL to sitemap file(s).
     * Script will use it to reference sitemap files in robots.txt and sitemap index.
     * @var string
     * @access private
     */
    private string $sitemapIndexURL;
    /**
     * Base path. Relative to script location.
     * Use this if Your sitemap and robots files should be stored in other
     * directory then script.
     * @var string
     * @access private
     */
    private string $saveDirectory;
    /**
     * Version of this class
     * @var string
     * @access private
     */
    private string $classVersion = "5.0.0";
    /**
     * Search engines URLs
     * @var array of strings
     * @access private
     */
    private array $searchEngines = [
        "https://webmaster.yandex.ru/ping?sitemap=",
    ];
    /**
     * Lines for robots.txt file that are written if file does not exist
     * @var array
     */
    private array $sampleRobotsLines = [
        "User-agent: *",
        "Allow: /",
    ];
    /**
     * @var array list of valid changefreq values according to the spec
     */
    private array $validChangefreqValues = [
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
    private array $validPriorities = [
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
     * @var IFileSystem object used to communicate with file system
     */
    private IFileSystem $fs;
    /**
     * @var IRuntime object used to communicate with runtime
     */
    private IRuntime $runtime;

    /**
     * @var XMLWriter Used for writing xml to files
     */
    private XMLWriter $xmlWriter;

    /**
     * @var string
     */
    private string $flushedSitemapFilenameFormat;

    /**
     * @var int
     */
    private int $flushedSitemapSize = 0;

    /**
     * @var int
     */
    private int $flushedSitemapCounter = 0;

    /**
     * @var array
     */
    private array $flushedSitemaps = [];

    /**
     * @var bool
     */
    private bool $isSitemapStarted = false;

    /**
     * @var int
     */
    private int $totalURLCount = 0;

    /**
     * @var int
     */
    private int $urlsetClosingTagLen = 10; // strlen("</urlset>\n")
    private int $sitemapURLCount = 0;
    private array $generatedFiles = [];

    /**
     * @param IConfig $config Configuration object.
     * @throws InvalidArgumentException
     */
    public function __construct(IConfig $config)
    {
        if ($config->getBaseURL() === '') {
            throw new InvalidArgumentException('baseURL config parameter is required');
        }

        $this->baseURL = rtrim($config->getBaseURL(), '/');
        $this->sitemapIndexURL = rtrim($config->getBaseURL(), '/');

        if ($config->getSitemapIndexURL()) {
            $this->sitemapIndexURL = rtrim($config->getSitemapIndexURL(), '/');
        }

        $configFS = $config->getFS();
        if ($configFS === null) {
            $this->fs = new FileSystem();
        } else {
            $this->fs = $configFS;
        }

        $configRuntime = $config->getRuntime();
        if ($configRuntime === null) {
            $this->runtime = new Runtime();
        } else {
            $this->runtime = $configRuntime;
        }

        if ($this->runtime->is_writable($config->getSaveDirectory()) === false) {
            throw new InvalidArgumentException(
                sprintf('the provided basePath (%s) should be a writable directory,', $config->getSaveDirectory()) .
                ' please check its existence and permissions'
            );
        }

        $this->saveDirectory = $config->getSaveDirectory();
        if (strlen($this->saveDirectory) > 0 && substr($this->saveDirectory, -1) != DIRECTORY_SEPARATOR) {
            $this->saveDirectory = $this->saveDirectory . DIRECTORY_SEPARATOR;
        }

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
     *
     * @return SitemapGenerator
     *
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
     *
     * @return $this
     *
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
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
     * @throws OutOfRangeException
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

    public function isCompressionEnabled(): bool
    {
        return $this->isCompressionEnabled;
    }

    /**
     * @param string $path
     * @param string|null $changeFrequency
     * @param float|null $priority
     * @param array $extensions
     * @return void
     * @throws InvalidArgumentException
     */
    public function validate(
        string   $path,
        string   $changeFrequency = null,
        float    $priority = null,
        array    $extensions = []): void
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
        if (count($extensions) > 0) {
            if (isset($extensions['google_video'])) {
                GoogleVideoExtension::validate($this->baseURL . $path, $extensions['google_video']);
            }

            if (isset($extensions['google_image'])) {
                GoogleImageExtension::validateEntryFields($extensions['google_image']);
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
     * @throws OutOfRangeException
     * @throws UnexpectedValueException
     * @throws InvalidArgumentException
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
        $this->validate($path, $changeFrequency, $priority, $extensions);

        if ($this->totalURLCount >= self::TOTAL_MAX_URLS) {
            throw new OutOfRangeException(
                sprintf("Max url limit reached (%d)", self::TOTAL_MAX_URLS)
            );
        }
        if ($this->isSitemapStarted === false) {
            $this->writeSitemapStart();
        }

        $this->writeSitemapUrl($this->baseURL . $path, $lastModified, $changeFrequency, $priority, $alternates, $extensions);

        if ($this->totalURLCount % 1000 === 0 || $this->sitemapURLCount >= $this->maxURLsPerSitemap) {
            $this->flushWriter();
        }

        if ($this->sitemapURLCount === $this->maxURLsPerSitemap) {
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

    /**
     * @param string $url
     * @return string
     * @throws UnexpectedValueException
     */
    private function encodeEscapeURL(string $url): string
    {
        // In-place encoding only on non-ASCII characters, like browsers do.
        $encoded = preg_replace_callback('/[^\x20-\x7f]/', function ($match) {
            return urlencode($match[0]);
        }, $url);
        if (!is_string($encoded)) {
            throw new UnexpectedValueException('Failed to encode URL');
        }
        return htmlspecialchars($encoded, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param string $loc
     * @param DateTime|null $lastModified
     * @param string|null $changeFrequency
     * @param float|null $priority
     * @param array|null $alternates
     * @param array $extensions
     * @throws UnexpectedValueException
     */
    private function writeSitemapUrl(
        string   $loc,
        DateTime $lastModified = null,
        string   $changeFrequency = null,
        float    $priority = null,
        array    $alternates = null,
        array    $extensions = []
    ): void {
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
        $this->sitemapURLCount++;
        $this->totalURLCount++;
    }

    private function flushWriter(): void
    {
        $targetSitemapFilepath = $this->saveDirectory . sprintf($this->flushedSitemapFilenameFormat, $this->flushedSitemapCounter);
        $flushedString = $this->xmlWriter->outputMemory();
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

    private function writeSitemapEnd(): void
    {
        $targetSitemapFilepath = $this->saveDirectory . sprintf($this->flushedSitemapFilenameFormat, $this->flushedSitemapCounter);
        $this->xmlWriter->endElement(); // urlset
        $this->xmlWriter->endDocument();
        $this->fs->file_put_contents($targetSitemapFilepath, $this->xmlWriter->flush(), FILE_APPEND);
        $this->isSitemapStarted = false;
        $this->flushedSitemaps[] = $targetSitemapFilepath;
        $this->flushedSitemapCounter++;
        $this->sitemapURLCount = 0;
        $this->flushedSitemapSize = 0;
    }

    /**
     * Flush all stored urls from memory to the disk and close all necessary tags.
     */
    public function flush(): void
    {
        $this->flushWriter();
        if ($this->isSitemapStarted) {
            $this->writeSitemapEnd();
        }
    }

    /**
     * Move flushed files to their final location. Compress if necessary.
     * @throws RuntimeException
     */
    public function finalize(): void
    {
        $this->generatedFiles = [];

        if (count($this->flushedSitemaps) === 1) {
            $targetSitemapFilename = $this->sitemapFileName;
            if ($this->isCompressionEnabled) {
                $targetSitemapFilename .= '.gz';
            }

            $targetSitemapFilepath = $this->saveDirectory . $targetSitemapFilename;

            if ($this->isCompressionEnabled) {
                $this->fs->copy($this->flushedSitemaps[0], 'compress.zlib://' . $targetSitemapFilepath);
                $this->fs->unlink($this->flushedSitemaps[0]);
            } else {
                $this->fs->rename($this->flushedSitemaps[0], $targetSitemapFilepath);
            }
            $this->generatedFiles['sitemaps_location'] = [$targetSitemapFilepath];
            $this->generatedFiles['sitemaps_index_url'] = $this->sitemapIndexURL . '/' . $targetSitemapFilename;
        } else if (count($this->flushedSitemaps) > 1) {
            $ext = '.' . pathinfo($this->sitemapFileName, PATHINFO_EXTENSION);
            $targetExt = $ext;
            if ($this->isCompressionEnabled) {
                $targetExt .= '.gz';
            }

            $sitemapsUrls = [];
            $targetSitemapFilepaths = [];
            foreach ($this->flushedSitemaps as $i => $flushedSitemap) {
                $targetSitemapFilename = str_replace($ext, ((int)$i + 1) . $targetExt, $this->sitemapFileName);
                $targetSitemapFilepath = $this->saveDirectory . $targetSitemapFilename;

                if ($this->isCompressionEnabled) {
                    $this->fs->copy($flushedSitemap, 'compress.zlib://' . $targetSitemapFilepath);
                    $this->fs->unlink($flushedSitemap);
                } else {
                    $this->fs->rename($flushedSitemap, $targetSitemapFilepath);
                }
                $sitemapsUrls[] = htmlspecialchars(
                    $this->sitemapIndexURL . '/' . $targetSitemapFilename, ENT_QUOTES);
                $targetSitemapFilepaths[] = $targetSitemapFilepath;
            }

            $targetSitemapIndexFilepath = $this->saveDirectory . $this->sitemapIndexFileName;
            $this->createSitemapIndex($sitemapsUrls, $targetSitemapIndexFilepath);
            $this->generatedFiles['sitemaps_location'] = $targetSitemapFilepaths;
            $this->generatedFiles['sitemaps_index_location'] = $targetSitemapIndexFilepath;
            $this->generatedFiles['sitemaps_index_url'] = $this->sitemapIndexURL . '/' . $this->sitemapIndexFileName;
        } else {
            throw new RuntimeException('failed to finalize, please add urls and flush first');
        }
    }

    private function createSitemapIndex(array $sitemapsUrls, string $sitemapIndexFileName): void
    {
        $this->xmlWriter->flush();
        $this->writeSitemapIndexStart();
        foreach ($sitemapsUrls as $sitemapsUrl) {
            $this->writeSitemapIndexUrl($sitemapsUrl);
        }
        $this->writeSitemapIndexEnd();
        $this->fs->file_put_contents(
            $sitemapIndexFileName,
            $this->xmlWriter->flush(),
        );
    }

    protected function writeSitemapIndexStart(): void
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

    /**
     * @param string $url
     * @throws UnexpectedValueException
     */
    private function writeSitemapIndexUrl(string $url): void
    {
        $this->xmlWriter->startElement('sitemap');
        $this->xmlWriter->writeElement('loc', $this->encodeEscapeURL($url));
        $this->xmlWriter->writeElement('lastmod', date('c'));
        $this->xmlWriter->endElement(); // sitemap
    }

    private function writeSitemapIndexEnd(): void
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
     * @return array of messages and http codes from each search engine
     * @access public
     * @throws BadMethodCallException
     */
    public function submitSitemap(): array
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
            if (is_bool($curlResource) && !$curlResource) {
                throw new RuntimeException("failed to execute curl_init for url " . $submitUrl);
            }
            if (!$this->runtime->curl_setopt($curlResource, CURLOPT_RETURNTRANSFER, true)) {
                throw new RuntimeException(
                    "failed to set curl option CURLOPT_RETURNTRANSFER to true, error: "
                    . $this->runtime->curl_error($curlResource)
                );
            }
            $responseContent = $this->runtime->curl_exec($curlResource);
            if (is_bool($responseContent) && !$responseContent) {
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

        $robotsFilePath = $this->saveDirectory . $this->robotsFileName;

        $robotsFileContent = $this->createNewRobotsContentFromFile($robotsFilePath);

        $this->fs->file_put_contents($robotsFilePath, $robotsFileContent);

        return $this;
    }

    /**
     * @param string $filepath
     * @return string
     * @throws RuntimeException
     */
    private function createNewRobotsContentFromFile(string $filepath): string
    {
        if ($this->fs->file_exists($filepath)) {
            $existingContent = $this->fs->file_get_contents($filepath);
            // if $existingContent is bool and false, it means that file exists but is not readable
            if (is_bool($existingContent) && !$existingContent) {
                throw new RuntimeException("Failed to read existing robots.txt file: $filepath");
            }
            if (is_string($existingContent)) {
                $contentLines = explode(PHP_EOL, $existingContent);
            } else {
                $contentLines = [];
            }
            $newContent = "";
            foreach ($contentLines as $key => $line) {
                if (str_starts_with($line, 'Sitemap:')) {
                    unset($contentLines[$key]);
                } else {
                    $newContent .= $line . PHP_EOL;
                }
            }
        } else {
            $newContent = $this->getSampleRobotsContent();
        }

        $newContent .= "Sitemap: {$this->generatedFiles['sitemaps_index_url']}";

        return $newContent;
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
