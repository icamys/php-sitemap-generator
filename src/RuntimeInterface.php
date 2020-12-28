<?php

namespace Icamys\SitemapGenerator;

interface RuntimeInterface
{
    public function extension_loaded($extname);

    public function is_writable($filepath);
}
