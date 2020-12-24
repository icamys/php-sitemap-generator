<?php

namespace Icamys\SitemapGenerator;

interface IFileSystem
{
    public function file_get_contents($filepath);

    public function file_put_contents($filepath, $content);

    public function file_exists($filepath);

    public function gzopen($filepath, $mode);

    public function gzwrite($file, $content);

    public function gzclose($file);
}