<?php

namespace Icamys\SitemapGenerator;

class FileSystem implements FileSystemInterface
{
    public function file_get_contents($filepath)
    {
        return file_get_contents($filepath);
    }

    public function file_put_contents($filepath, $content, $flags = 0)
    {
        return file_put_contents($filepath, $content, $flags);
    }

    public function gzopen($filepath, $mode)
    {
        return gzopen($filepath, $mode);
    }

    public function gzwrite($file, $content)
    {
        return gzwrite($file, $content);
    }

    public function gzclose($file)
    {
        return gzclose($file);
    }

    public function file_exists($filepath)
    {
        return file_exists($filepath);
    }

    public function rename($oldname, $newname) {
        return rename($oldname, $newname);
    }
}
