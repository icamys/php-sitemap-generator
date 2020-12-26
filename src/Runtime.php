<?php

namespace Icamys\SitemapGenerator;

class Runtime implements RuntimeInterface
{
    public function extension_loaded($extname)
    {
        return extension_loaded($extname);
    }
}
