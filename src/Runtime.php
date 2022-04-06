<?php

namespace Icamys\SitemapGenerator;

class Runtime
{
    public function extension_loaded($extname)
    {
        return extension_loaded($extname);
    }

    public function is_writable($filepath)
    {
        return is_writable($filepath);
    }

    public function curl_init($url)
    {
        return curl_init($url);
    }

    public function curl_setopt($handle, $option, $value)
    {
        return curl_setopt($handle, $option, $value);
    }

    public function curl_exec($handle)
    {
        return curl_exec($handle);
    }

    public function curl_getinfo($handle, $option = null)
    {
        return curl_getinfo($handle, $option);
    }

    public function curl_error($handle)
    {
        return curl_error($handle);
    }
}
