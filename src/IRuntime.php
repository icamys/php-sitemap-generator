<?php

namespace Icamys\SitemapGenerator;

interface IRuntime
{
    public function extension_loaded($extname);

    public function is_writable($filepath);

    public function curl_init($url);

    public function curl_setopt($handle, $option, $value);

    public function curl_exec($handle);

    public function curl_getinfo($handle, $option = null);

    public function curl_error($handle);
}