<?php

namespace Icamys\SitemapGenerator;

class Runtime implements IRuntime
{
    public function extension_loaded($extname)
    {
        return extension_loaded($extname);
    }
}
