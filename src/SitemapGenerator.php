<?php

namespace Icamys\SitemapGenerator;

use BadMethodCallException;
use DateTime;
use DOMDocument;
use InvalidArgumentException;
use LengthException;
use OutOfRangeException;
use RuntimeException;
use SimpleXMLElement;
use SplFixedArray;

interface IFileSystem
{
    public function file_get_contents($filepath);

    public function file_put_contents($filepath, $content);

    public function file_exists($filepath);

    public function gzopen($filepath, $mode);

    public function gzwrite($file, $content);

    public function gzclose($file);
}

interface IRuntime
{
    public function extension_loaded($extname);
}

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
    const MAX_FILE_SIZE = 52428800;

    /**
     * Max number of urls per sitemap according to spec.
     * @see https://www.sitemaps.org/protocol.html
     */
    const MAX_URLS_PER_SITEMAP = 50000;

    /**
     * Max number of sitemaps per index file according to spec.
     * @see http://www.sitemaps.org/protocol.html
     */
    const MAX_SITEMAPS_PER_INDEX = 50000;

    /**
     * Max url length according to spec.
     * @see https://www.sitemaps.org/protocol.html#xmlTagDefinitions
     */
    const MAX_URL_LEN = 2048;

    const ATTR_KEY_LOC = 0;
    const ATTR_KEY_LASTMOD = 1;
    const ATTR_KEY_CHANGEFREQ = 2;
    const ATTR_KEY_PRIORITY = 3;
    const ATTR_KEY_ALTERNATES = 4;

    const ATTR_NAME_LOC = 'loc';
    const ATTR_NAME_LASTMOD = 'lastmod';
    const ATTR_NAME_CHANGEFREQ = 'changefreq';
    const ATTR_NAME_PRIORITY = 'priority';
    const ATTR_NAME_ALTERNATES = 'alternates';

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
    private $createGZipFile = false;
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
    private $classVersion = "2.0.3";
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
     * @var SplFixedArray of strings
     * @access private
     */
    private $urls;
    /**
     * @var integer number of currently added urls
     */
    private $urlsCount = 0;
    /**
     * Array with sitemap
     * @var array of strings
     * @access private
     */
    private $sitemaps = [];
    /**
     * Array with sitemap index
     * @var array of strings
     * @access private
     */
    private $sitemapIndex = [];
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
     * @var IFileSystem object used to communicate with file system
     */
    private $fs;
    /**
     * @var IRuntime object used to communicate with runtime
     */
    private $runtime;

    /**
     * @param string $baseURL You site URL
     * @param string $basePath Relative path where sitemap and robots should be stored.
     * @param IFileSystem $fs
     * @param IRuntime $runtime
     */
    public function __construct(string $baseURL, string $basePath = "", IFileSystem $fs = null, IRuntime $runtime = null)
    {
        $this->urls = new SplFixedArray();
        $this->baseURL = $baseURL;
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
    }

    /**
     * @param string $filename
     * @return SitemapGenerator
     */
    public function setSitemapFilename(string $filename = ''): SitemapGenerator
    {
        if (strlen($filename) === 0) {
            throw new InvalidArgumentException('filename should not be empty');
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

    /**
     * @return SitemapGenerator
     */
    public function toggleGZipFileCreation(): SitemapGenerator
    {
        $this->createGZipFile = !$this->createGZipFile;
        return $this;
    }

    /**
     * Use this to add single URL to sitemap.
     * @param string $loc
     * @param DateTime|null $lastModified
     * @param string|null $changeFrequency ex. 'always'
     * @param float|null $priority ex. '0.5'
     * @param array|null $alternates
     * @return SitemapGenerator
     * @throws InvalidArgumentException
     * @todo add scheme and domain check
     * @todo check that port of url is the same as base url port
     * @see http://php.net/manual/en/function.date.php
     * @see http://en.wikipedia.org/wiki/ISO_8601
     */
    public function addURL(
        string $loc = '',
        DateTime $lastModified = null,
        string $changeFrequency = null,
        float $priority = null,
        array $alternates = null
    ): SitemapGenerator
    {
        if (strlen($loc) === 0) {
            throw new InvalidArgumentException("loc parameter is required");
        }
        if (mb_strlen($loc) > self::MAX_URL_LEN) {
            throw new InvalidArgumentException(
                sprintf("url is too large (%d of %d)", mb_strlen($loc), self::MAX_URL_LEN)
            );
        }
        $tmp = new SplFixedArray(1);

        $tmp[self::ATTR_KEY_LOC] = $loc;

        if (isset($lastModified)) {
            $tmp->setSize(2);
            $tmp[self::ATTR_KEY_LASTMOD] = $lastModified->format(DateTime::ATOM);
        }

        if (isset($changeFrequency)) {
            if ($this->isValidChangefreqValue($changeFrequency) === false) {
                throw new InvalidArgumentException(
                    'invalid change frequency passed, valid values are: %s' . implode(',', $this->validChangefreqValues)
                );
            }
            $tmp->setSize(3);
            $tmp[self::ATTR_KEY_CHANGEFREQ] = $changeFrequency;
        }

        if (isset($priority)) {
            if (!$this->isValidPriorityValue($priority)) {
                throw new InvalidArgumentException("priority should be a float number in the range [0.0..1.0]");
            }

            $tmp->setSize(4);
            $tmp[self::ATTR_KEY_PRIORITY] = number_format($priority, 1, ".", "");
        }

        if (isset($alternates)) {
            $tmp->setSize(5);
            $tmp[self::ATTR_KEY_ALTERNATES] = $alternates;
        }

        if ($this->urls->getSize() === 0) {
            $this->urls->setSize(1);
        } else {
            if ($this->urls->getSize() === $this->urls->key()) {
                $this->urls->setSize($this->urls->getSize() * 2);
            }
        }

        $this->urls[$this->urls->key()] = $tmp;
        $this->urls->next();
        $this->urlsCount++;
        return $this;
    }

    public function isValidChangefreqValue($value)
    {
        return in_array($value, $this->validChangefreqValues);
    }

    public function isValidPriorityValue(float $value): bool
    {
        return in_array($value, $this->validPriorities);
    }

    /**
     * Creates sitemap and stores it in memory.
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     * @throws LengthException
     */
    public function createSitemap(): SitemapGenerator
    {
        if ($this->urls->getSize() === 0) {
            throw new BadMethodCallException(
                "No urls added to generator. " .
                "Please add urls by calling \"addUrl\" function."
            );
        }

        $generatorInfo = implode(PHP_EOL, [
            sprintf('<!-- generator-class="%s" -->', get_class($this)),
            sprintf('<!-- generator-version="%s" -->', $this->classVersion),
            sprintf('<!-- generated-on="%s" -->', date('c')),
        ]);

        $sitemapHeader = implode(PHP_EOL, [
            '<?xml version="1.0" encoding="UTF-8"?>',
            $generatorInfo,
            '<urlset',
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"',
            'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"',
            'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
            '</urlset>',
        ]);

        $sitemapIndexHeader = implode(PHP_EOL, [
            '<?xml version="1.0" encoding="UTF-8"?>',
            $generatorInfo,
            '<sitemapindex',
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"',
            'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd"',
            'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
            '</sitemapindex>',
        ]);

        $chunkSize = $this->maxURLsPerSitemap;
        $chunksCount = ceil($this->urlsCount / $chunkSize);

        for ($chunkCounter = 0; $chunkCounter < $chunksCount; $chunkCounter++) {
            $sitemapXml = new SimpleXMLElement($sitemapHeader);
            for ($urlCounter = $chunkCounter * $chunkSize;
                 $urlCounter < ($chunkCounter + 1) * $chunkSize && $urlCounter < $this->urlsCount; $urlCounter++
            ) {
                $row = $sitemapXml->addChild('url');

                $row->addChild(
                    self::ATTR_NAME_LOC,
                    htmlspecialchars($this->baseURL . $this->urls[$urlCounter][self::ATTR_KEY_LOC], ENT_QUOTES, 'UTF-8')
                );

                if ($this->urls[$urlCounter]->getSize() > 1) {
                    if (isset($this->urls[$urlCounter][self::ATTR_KEY_LASTMOD])) {
                        $row->addChild(self::ATTR_NAME_LASTMOD, $this->urls[$urlCounter][self::ATTR_KEY_LASTMOD]);
                    }
                }
                if ($this->urls[$urlCounter]->getSize() > 2) {
                    $row->addChild(self::ATTR_NAME_CHANGEFREQ, $this->urls[$urlCounter][self::ATTR_KEY_CHANGEFREQ]);
                }
                if ($this->urls[$urlCounter]->getSize() > 3) {
                    $row->addChild(self::ATTR_NAME_PRIORITY, $this->urls[$urlCounter][self::ATTR_KEY_PRIORITY]);
                }
                if ($this->urls[$urlCounter]->getSize() > 4) {
                    foreach ($this->urls[$urlCounter][self::ATTR_KEY_ALTERNATES] as $alternate) {
                        if (isset($alternate['hreflang']) && isset($alternate['href'])) {
                            $tag = $row->addChild('link', null, null);
                            $tag->addAttribute('rel', 'alternate');
                            $tag->addAttribute('hreflang', $alternate['hreflang']);
                            $tag->addAttribute('href', $alternate['href']);
                        }
                    }
                }
            }

            $sitemapStr = $sitemapXml->asXML();
            $sitemapStrLen = strlen($sitemapStr);

            if ($sitemapStrLen > self::MAX_FILE_SIZE) {
                $diff = number_format($this->getDiffInPercents(self::MAX_FILE_SIZE, $sitemapStrLen), 2);
                throw new LengthException(
                    "Sitemap size limit reached " .
                    sprintf("(current limit = %d bytes, file size = %d bytes, diff = %s%%), ", $sitemapStrLen, self::MAX_FILE_SIZE, $diff)
                    . "please decrease max urls per sitemap setting in generator instance"
                );
            }
            $this->sitemaps[] = $sitemapStr;
        }
        $sitemapsCount = count($this->sitemaps);
        if ($sitemapsCount > 1) {
            if ($sitemapsCount > self::MAX_SITEMAPS_PER_INDEX) {
                throw new LengthException(
                    sprintf("Number of sitemaps per index has reached its limit (%s)", self::MAX_SITEMAPS_PER_INDEX)
                );
            }
            for ($i = 0; $i < $sitemapsCount; $i++) {
                $this->sitemaps[$i] = [
                    'filename' => str_replace(".xml", ($i + 1) . ".xml", $this->sitemapFileName),
                    'source' => $this->sitemaps[$i],
                ];
            }
            $sitemapXml = new SimpleXMLElement($sitemapIndexHeader);
            foreach ($this->sitemaps as $sitemap) {
                $row = $sitemapXml->addChild('sitemap');
                $row->addChild(self::ATTR_NAME_LOC, $this->baseURL . "/" . $this->appendGzPostfixIfEnabled(htmlentities($sitemap['filename'])));
                $row->addChild(self::ATTR_NAME_LASTMOD, date('c'));
            }
            $this->sitemapFullURL = $this->baseURL . "/" . $this->appendGzPostfixIfEnabled($this->sitemapIndexFileName);
            $this->sitemapIndex = [
                'filename' => $this->sitemapIndexFileName,
                'source' => $sitemapXml->asXML(),
            ];
        } else {
            $this->sitemapFullURL = $this->baseURL . "/" . $this->appendGzPostfixIfEnabled($this->sitemapFileName);
            $this->sitemaps[0] = [
                'filename' => $this->sitemapFileName,
                'source' => $this->sitemaps[0],
            ];
        }

        return $this;
    }

    /**
     * @param int $total
     * @param int $part
     * @return float
     */
    private function getDiffInPercents(int $total, int $part): float
    {
        return $part * 100 / $total - 100;
    }

    /**
     * @param string $str
     * @return string
     */
    private function appendGzPostfixIfEnabled(string $str): string
    {
        if ($this->createGZipFile) {
            return $str . ".gz";
        }
        return $str;
    }

    /**
     * Returns created sitemaps as array of strings.
     * Useful in case if you want to work with sitemap without saving it as files.
     * @return array of strings
     * @access public
     */
    public function toArray(): array
    {
        if (count($this->sitemapIndex) > 0) {
            return array_merge([$this->sitemapIndex], $this->sitemaps);
        } else {
            return $this->sitemaps;
        }
    }

    /**
     * Will write sitemaps as files.
     * @access public
     * @throws BadMethodCallException
     */
    public function writeSitemap(): SitemapGenerator
    {
        if (count($this->sitemaps) === 0) {
            throw new BadMethodCallException("To write sitemap, call createSitemap function first.");
        }

        if (count($this->sitemapIndex) > 0) {
            $this->document->loadXML($this->sitemapIndex['source']);
            $indexStr = $this->document->saveXML();
            $indexFilepath = $this->basePath . $this->sitemapIndex['filename'];
            $this->writeFile($indexStr, $indexFilepath);
            if ($this->createGZipFile) {
                $this->writeGZipFile($indexStr, $indexFilepath . '.gz');
            }
            foreach ($this->sitemaps as $sitemap) {
                $filepath = $this->basePath . $sitemap['filename'];
                if ($this->createGZipFile) {
                    $this->writeGZipFile($sitemap['source'], $filepath . '.gz');
                } else {
                    $this->writeFile($sitemap['source'], $filepath);
                }
            }
        } else {
            $sitemap = $this->sitemaps[0];
            $this->document->loadXML($sitemap['source']);
            $docStr = $this->document->saveXML();
            $filepath = $this->basePath . $sitemap['filename'];
            $this->writeFile($docStr, $filepath);
            if ($this->createGZipFile) {
                $this->writeGZipFile($docStr, $filepath . '.gz');
            }
        }
        return $this;
    }

    /**
     * Write file to path
     * @param string $content
     * @param string $filepath
     * @return SitemapGenerator
     * @access private
     */
    private function writeFile(string $content, string $filepath): SitemapGenerator
    {
        if ($this->fs->file_put_contents($filepath, $content) === false) {
            throw new RuntimeException('failed to write content to file ' . $filepath);
        }

        return $this;
    }

    /**
     * Save GZipped file.
     * @param string $content
     * @param string $filepath
     * @return SitemapGenerator
     * @access private
     */
    private function writeGZipFile(string $content, string $filepath): SitemapGenerator
    {
        $file = $this->fs->gzopen($filepath, 'w');
        if ($file === false) {
            throw new RuntimeException(sprintf('failed to open file %s for writing', $filepath));
        }

        $contentLen = strlen($content);

        if ($contentLen > 0) {
            if ($this->fs->gzwrite($file, $content) === 0) {
                throw new RuntimeException('failed to write content to file ' . $filepath);
            }
        }

        if ($this->fs->gzclose($file) === false) {
            throw new RuntimeException('failed to close file ' . $filepath);
        }
        return $this;
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
    public function submitSitemap($yahooAppId = null)
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
            $submitSite = curl_init($searchEngines[$i] . htmlspecialchars($this->sitemapFullURL, ENT_QUOTES, 'UTF-8'));
            curl_setopt($submitSite, CURLOPT_RETURNTRANSFER, true);
            $responseContent = curl_exec($submitSite);
            $response = curl_getinfo($submitSite);
            $submitSiteShort = array_reverse(explode(".", parse_url($searchEngines[$i], PHP_URL_HOST)));
            $result[] = [
                "site" => $submitSiteShort[1] . "." . $submitSiteShort[0],
                "fullsite" => $searchEngines[$i] . htmlspecialchars($this->sitemapFullURL, ENT_QUOTES, 'UTF-8'),
                "http_code" => $response['http_code'],
                "message" => str_replace("\n", " ", strip_tags($responseContent)),
            ];
        }
        return $result;
    }

    /**
     * Returns array of URLs
     * Converts internal SplFixedArray to array
     * @return array array of URLs
     */
    public function getURLsArray(): array
    {
        $urls = $this->urls->toArray();

        /**
         * @var int $key
         * @var SplFixedArray $urlSplArr
         */
        foreach ($urls as $key => $urlSplArr) {
            if (!is_null($urlSplArr)) {
                $urlArr = $urlSplArr->toArray();
                $url = [];
                foreach ($urlArr as $paramIndex => $paramValue) {
                    switch ($paramIndex) {
                        case static::ATTR_KEY_LOC:
                            $url[self::ATTR_NAME_LOC] = $paramValue;
                            break;
                        case static::ATTR_KEY_CHANGEFREQ:
                            $url[self::ATTR_NAME_CHANGEFREQ] = $paramValue;
                            break;
                        case static::ATTR_KEY_LASTMOD:
                            $url[self::ATTR_NAME_LASTMOD] = $paramValue;
                            break;
                        case static::ATTR_KEY_PRIORITY:
                            $url[self::ATTR_NAME_PRIORITY] = $paramValue;
                            break;
                        case static::ATTR_KEY_ALTERNATES:
                            $url[self::ATTR_NAME_ALTERNATES] = $paramValue;
                            break;
                    }
                }
                $urls[$key] = $url;
            }
        }

        return $urls;
    }

    /**
     * @return int number of URLs added to generator
     */
    public function getURLsCount(): int
    {
        return $this->urlsCount;
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

class FileSystem implements IFileSystem
{
    public function file_get_contents($filepath)
    {
        return file_get_contents($filepath);
    }

    public function file_put_contents($filepath, $content)
    {
        return file_put_contents($filepath, $content);
    }

    public function gzopen($filepath, $mode)
    {
        return gzopen($filepath, $mode);
    }

    public function gzwrite($file, $content)
    {
        return gzwrite($file, $content);
    }

    public function gzclose($file)
    {
        return gzclose($file);
    }

    public function file_exists($filepath)
    {
        return file_exists($filepath);
    }
}

class Runtime implements IRuntime
{
    public function extension_loaded($extname)
    {
        return extension_loaded($extname);
    }
}
