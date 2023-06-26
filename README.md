# PHP Sitemap Generator

![Testing status](https://github.com/icamys/php-sitemap-generator/actions/workflows/run-tests.yml/badge.svg)
[![codecov.io](https://codecov.io/github/icamys/php-sitemap-generator/coverage.svg?branch=master)](https://codecov.io/github/icamys/php-sitemap-generator?branch=master)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.3%20%7C%7C%20%3E%3D%208.0-8892BF.svg)](https://php.net/)
[![Latest Stable Version](https://poser.pugx.org/icamys/php-sitemap-generator/v/stable.png)](https://packagist.org/packages/icamys/php-sitemap-generator)
[![Total Downloads](https://poser.pugx.org/icamys/php-sitemap-generator/downloads)](https://packagist.org/packages/icamys/php-sitemap-generator)

Library for sitemap generation and submission.

Features:
* Follows [sitemaps.org](https://sitemaps.org/) protocol
* Supports alternative links for multi-language pages (see [google documentation](https://webmasters.googleblog.com/2012/05/multilingual-and-multinational-site.html))
* Supports video and image sitemap generation  
* Low memory usage for any amount of URLs
* Supports sitemap stylesheets

Installation with Composer:

```
composer require icamys/php-sitemap-generator
```

## Survey

If you found this package useful, please [take a short survey](https://forms.gle/ngeponiTd1zWgmkC9) to improve your sitemap generation experience.

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

// Optional: add sitemap stylesheet. Note that you need to create
// the file 'sitemap.xsl' beforehand on your own.
$generator->setSitemapStylesheet('sitemap.xsl');

// Flush all stored urls from memory to the disk and close all necessary tags.
$generator->flush();

// Move flushed files to their final location. Compress if the option is enabled.
$generator->finalize();

// Update robots.txt file in output directory or create a new one
$generator->updateRobots();

// Submit your sitemaps to Google and Yandex.
$generator->submitSitemap();
```

### Video sitemap example

To create video sitemap, pass the `$extensions` parameter to the `addURL()` method as follows:

```php
<?php

// Initialize the generator
// ...

// Initialize variable with video tags
// For more see the official google documentation:
// https://developers.google.com/search/docs/advanced/sitemaps/video-sitemaps
$videoTags = [
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
];


$extensions = [
    'google_video' => $videoTags
];

$generator->addURL('/path/to/page/', null, null, null, null, $extensions);

// generate, flush, etc.
// ...
```


### Image sitemap example

To create image sitemap, pass the `$extensions` parameter to the `addURL()` method as follows:

```php
<?php

// Initialize the generator
// ...

// Initialize variable with image tags
// For more see the official google documentation:
// https://developers.google.com/search/docs/advanced/sitemaps/image-sitemaps
$imageTags = [
    'loc' => 'https://www.example.com/thumbs/123.jpg',
    'title' => 'Cat vs Cabbage',
    'caption' => 'A funny picture of a cat eating cabbage',
    'geo_location' => 'Lyon, France',
    'license' => 'https://example.com/image-license',
];

$extensions = [
    'google_image' => $imageTags
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

## Todo

* Remove `$yahooAppId` parameter. 
