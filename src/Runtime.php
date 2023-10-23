<?php

namespace Icamys\SitemapGenerator;

use CurlHandle;

class Runtime implements IRuntime
{
    public function extension_loaded(string $extname): bool
    {
        return extension_loaded($extname);
    }

    public function is_writable(string $filepath): bool
    {
        return is_writable($filepath);
    }

    public function curl_init(?string $url): CurlHandle|bool
    {
        return curl_init($url);
    }

    public function curl_setopt(CurlHandle $handle, int $option, mixed $value): bool
    {
        return curl_setopt($handle, $option, $value);
    }

    public function curl_exec(CurlHandle $handle): bool|string
    {
        return curl_exec($handle);
    }

    public function curl_getinfo(CurlHandle $handle, ?int $option = null): mixed
    {
        return curl_getinfo($handle, $option);
    }

    public function curl_error(CurlHandle $handle): string
    {
        return curl_error($handle);
    }
}
