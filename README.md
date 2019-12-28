PHP Sitemap Generator
=====================

This class can be used to generate sitemaps.

Internally uses SplFixedArrays, thus is faster and uses less memory.

Features:
* Follows [sitemaps.org](https://sitemaps.org/) protocol
* Supports alternative links for multi-language pages (see [google docs](https://webmasters.googleblog.com/2012/05/multilingual-and-multinational-site.html))

Usage example:

```php
<?php

include "src/SitemapGenerator.php";

// Setting the current working directory to be output directory// 
// for generated sitemaps (and, if needed, robots.txt)
// The output directory setting is optional and provided for demonstration purpose.
// By default output is written to current directory. 
$outputDir = getcwd();

$generator = new \Icamys\SitemapGenerator\SitemapGenerator('example.com', $outputDir);

// will create also compressed (gzipped) sitemap
$generator->toggleGZipFileCreation();

// determine how many urls should be put into one file
// according to standard protocol 50000 is maximum value (see http://www.sitemaps.org/protocol.html)
$generator->setMaxURLsPerSitemap(50000);

// sitemap file name
$generator->setSitemapFileName("sitemap.xml");

// sitemap index file name
$generator->setSitemapIndexFileName("sitemap-index.xml");

// alternate languages
$alternates = [
    ['hreflang' => 'de', 'href' => "http://www.example.com/de"],
    ['hreflang' => 'fr', 'href' => "http://www.example.com/fr"],
];

// adding url `loc`, `lastmodified`, `changefreq`, `priority`, `alternates`
$generator->addUrl('http://example.com/url/path/', new DateTime(), 'always', 0.5, $alternates);

// generating internally a sitemap
$generator->createSitemap();

// writing early generated sitemap to file
$generator->writeSitemap();

// update robots.txt file in output directory or create a new one
$generator->updateRobots();
```

### Testing

Run tests with command:

```bash
$ ./vendor/bin/phpunit
```

Run code coverage:

```bash
$ ./vendor/bin/phpunit --coverage-html ./coverage
```

### Changelog

New in 2.0.0:
* Major code rework
* No more public properties in generator, using only methods
* Fixed bug with robots.txt update
* Unit tests added for quality assurance
* Updated limits according to [sitemaps spec](https://www.sitemaps.org/protocol.html)