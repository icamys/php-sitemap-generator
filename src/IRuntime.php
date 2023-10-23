<?php

namespace Icamys\SitemapGenerator;

use CurlHandle;

interface IRuntime
{
    public function extension_loaded(string $extname);

    public function is_writable(string $filepath);

    public function curl_init(?string $url);

    public function curl_setopt(CurlHandle $handle, int $option, mixed $value);

    public function curl_exec(CurlHandle $handle);

    public function curl_getinfo(CurlHandle $handle, ?int $option = null): mixed;

    public function curl_error(CurlHandle $handle);
}