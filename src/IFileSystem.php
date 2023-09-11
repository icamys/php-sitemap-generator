<?php

namespace Icamys\SitemapGenerator;

interface IFileSystem
{
    public function file_get_contents($filepath);

    public function file_put_contents($filepath, $content, $flags = 0);

    public function file_exists($filepath);

    public function rename($oldname, $newname);

    public function copy($source, $destination);

    public function unlink($filepath);
}