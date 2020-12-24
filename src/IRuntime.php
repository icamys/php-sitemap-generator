<?php

namespace Icamys\SitemapGenerator;

interface IRuntime
{
    public function extension_loaded($extname);
}