# PHP Sitemap Generator

[![Build Status](https://travis-ci.org/icamys/php-sitemap-generator.svg?branch=master)](https://travis-ci.org/icamys/php-sitemap-generator)
[![codecov.io](https://codecov.io/github/icamys/php-sitemap-generator/coverage.svg?branch=master)](https://codecov.io/github/icamys/php-sitemap-generator?branch=master)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.3%20%7C%7C%20%3E%3D%208.0-8892BF.svg)](https://php.net/)
[![Latest Stable Version](https://poser.pugx.org/icamys/php-sitemap-generator/v/stable.png)](https://packagist.org/packages/icamys/php-sitemap-generator)
[![Total Downloads](https://poser.pugx.org/icamys/php-sitemap-generator/downloads)](https://packagist.org/packages/icamys/php-sitemap-generator)

Library for sitemap generation and submission.

Features:
* Follows [sitemaps.org](https://sitemaps.org/) protocol
* Supports alternative links for multi-language pages (see [google docs](https://webmasters.googleblog.com/2012/05/multilingual-and-multinational-site.html))
* Supports video sitemap generation  
* Low memory usage for any amount of URLs

Installation with Composer:

```
composer require icamys/php-sitemap-generator
```

## Usage

```php
<?php

include "vendor/autoload.php";

$yourSiteUrl = 'https://example.com';

// Setting the current working directory to be output directory
// for generated sitemaps (and, if needed, robots.txt)
// The output directory setting is optional and provided for demonstration purposes.
// The generator writes output to the current directory by default. 
$outputDir = getcwd();

$generator = new \Icamys\SitemapGenerator\SitemapGenerator($yourSiteUrl, $outputDir);

// Create a compressed sitemap
$generator->enableCompression();

// Determine how many urls should be put into one file;
// this feature is useful in case if you have too large urls
// and your sitemap is out of allowed size (50Mb)
// according to the standard protocol 50000 urls per sitemap
// is the maximum allowed value (see http://www.sitemaps.org/protocol.html)
$generator->setMaxUrlsPerSitemap(50000);

// Set the sitemap file name
$generator->setSitemapFileName("sitemap.xml");

// Set the sitemap index file name
$generator->setSitemapIndexFileName("sitemap-index.xml");

// Add alternate languages if needed
$alternates = [
    ['hreflang' => 'de', 'href' => "http://www.example.com/de"],
    ['hreflang' => 'fr', 'href' => "http://www.example.com/fr"],
];

// Add url components: `path`, `lastmodified`, `changefreq`, `priority`, `alternates`
// Instead of storing all urls in the memory, the generator will flush sets of added urls
// to the temporary files created on your disk.
// The file format is 'sm-{index}-{timestamp}.xml'
$generator->addURL('/path/to/page/', new DateTime(), 'always', 0.5, $alternates);

// Flush all stored urls from memory to the disk and close all necessary tags.
$generator->flush();

// Move flushed files to their final location. Compress if the option is enabled.
$generator->finalize();

// Update robots.txt file in output directory or create a new one
$generator->updateRobots();

// Submit your sitemaps to Google, Yahoo, Bing and Ask.com
$generator->submitSitemap();
```

### Video sitemap

To create video sitemap, pass the `$extensions` parameter to the `addURL()` method as follows:

```php
<?php

// Initialize the generator
// ...

// Initialize variable with video tags
// Also see the google docs about that:
// https://developers.google.com/search/docs/advanced/sitemaps/video-sitemaps
$videoTags = [
    'video:thumbnail_loc' => 'http://www.example.com/thumbs/123.jpg',
    'video:title' => 'Grilling steaks for summer',
    'video:description' => 'Alkis shows you how to get perfectly done steaks every time',
    'video:content_loc' => 'http://streamserver.example.com/video123.mp4',
    'video:player_loc' => 'http://www.example.com/videoplayer.php?video=123',
    'video:duration' => 600,
    'video:expiration_date' => '2021-11-05T19:20:30+08:00',
    'video:rating' => 4.2,
    'video:view_count' => 12345,
    'video:publication_date' => '2007-11-05T19:20:30+08:00',
    'video:family_friendly' => 'yes',
    'video:restriction' => [
        'relationship' => 'allow',
        'value' => 'IE GB US CA',
    ],
    'video:platform' => [
        'relationship' => 'allow',
        'value' => 'web mobile',
    ],
    'video:price' => [
        [
            'currency' => 'EUR',
            'value' => 1.99,
            'type' => 'rent',
            'resolution' => 'hd',
        ]
    ],
    'video:requires_subscription' => 'yes',
    'video:uploader' => [
        'info' => 'https://example.com/users/grillymcgrillerson',
        'value' => 'GrillyMcGrillerson',
    ],
    'video:live' => 'no',
    'video:tag' => [
        "steak", "meat", "summer", "outdoor"
    ],
    'video:category' => 'baking',
];


$extensions = [
    'google_video' => $videoTags
];

$generator->addURL('/path/to/page/', null, null, null, null, $extensions);

// generate, flush, etc.
// ...
```

## Testing

Run tests with command:

```bash
$ ./vendor/bin/phpunit
```

Run code coverage:

```bash
$ XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html ./coverage
```

## Changelog

You can find full changelog on the [releases page](https://github.com/icamys/php-sitemap-generator/releases).
