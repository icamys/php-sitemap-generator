# PHP Sitemap Generator

[![Build Status](https://travis-ci.org/icamys/php-sitemap-generator.svg?branch=master)](https://travis-ci.org/icamys/php-sitemap-generator)
[![codecov.io](https://codecov.io/github/icamys/php-sitemap-generator/coverage.svg?branch=master)](https://codecov.io/github/icamys/php-sitemap-generator?branch=master)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.2-8892BF.svg)](https://php.net/)
[![Latest Stable Version](https://poser.pugx.org/icamys/php-sitemap-generator/v/stable.png)](https://packagist.org/packages/icamys/php-sitemap-generator)
[![Total Downloads](https://poser.pugx.org/icamys/php-sitemap-generator/downloads)](https://packagist.org/packages/icamys/php-sitemap-generator)

Library for sitemap generation and submission.

Internally uses SplFixedArrays, thus is faster and uses less memory then alternatives.

Features:
* Follows [sitemaps.org](https://sitemaps.org/) protocol
* Supports alternative links for multi-language pages (see [google docs](https://webmasters.googleblog.com/2012/05/multilingual-and-multinational-site.html))

Installation with Composer:

```
composer require icamys/php-sitemap-generator
```

Usage example:

```php
<?php

include "src/SitemapGenerator.php";

// Setting the current working directory to be output directory
// for generated sitemaps (and, if needed, robots.txt)
// The output directory setting is optional and provided for demonstration purpose.
// By default output is written to current directory. 
$outputDir = getcwd();

$generator = new \Icamys\SitemapGenerator\SitemapGenerator('example.com', $outputDir);

// will create also compressed (gzipped) sitemap
$generator->toggleGZipFileCreation();

// determine how many urls should be put into one file;
// this feature is useful in case if you have too large urls
// and your sitemap is out of allowed size (50Mb)
// according to the standard protocol 50000 is maximum value (see http://www.sitemaps.org/protocol.html)
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
$generator->addURL('http://example.com/url/path/', new DateTime(), 'always', 0.5, $alternates);

// generate internally a sitemap
$generator->createSitemap();

// write early generated sitemap to file(s)
$generator->writeSitemap();

// update robots.txt file in output directory or create a new one
$generator->updateRobots();

// submit your sitemaps to Google, Yahoo, Bing and Ask.com
$generator->submitSitemap();
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
* Removed `addUrls` method in favor of `addUrl`
* Fixed bug with robots.txt update
* Fixed bug in addURL method (empty loc)
* Unit tests added for quality assurance
* Updated limits according to [sitemaps spec](https://www.sitemaps.org/protocol.html)
* Updated search engines urls
* Added change frequency validation
