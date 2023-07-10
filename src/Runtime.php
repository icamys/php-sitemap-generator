<?php

namespace Icamys\SitemapGenerator;

use CurlHandle;

class Runtime
{
    public function extension_loaded(string $extname): bool
    {
        return extension_loaded($extname);
    }

    public function is_writable(string $filepath): bool
    {
        return is_writable($filepath);
    }

    /**
     * @return resource|false|CurlHandle
     */
    public function curl_init(?string $url)
    {
        return curl_init($url);
    }

    /**
     * @param CurlHandle|resource $handle
     * @param mixed $value
     */
    public function curl_setopt($handle, int $option, $value): bool
    {
        return curl_setopt($handle, $option, $value);
    }

    /**
     * @param CurlHandle|resource $handle
     * @return string|bool
     */
    public function curl_exec($handle)
    {
        return curl_exec($handle);
    }

    /**
     * @param CurlHandle|resource $handle
     * @return mixed
     */
    public function curl_getinfo($handle, ?int $option = null)
    {
        return curl_getinfo($handle, $option);
    }

    /**
     * @param CurlHandle|resource $handle
     */
    public function curl_error($handle): string
    {
        return curl_error($handle);
    }
}
