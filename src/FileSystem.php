<?php

namespace Icamys\SitemapGenerator;

class FileSystem
{
    public function file_get_contents($filepath)
    {
        return file_get_contents($filepath);
    }

    public function file_put_contents($filepath, $content, $flags = 0)
    {
        return file_put_contents($filepath, $content, $flags);
    }

    public function file_exists($filepath)
    {
        return file_exists($filepath);
    }

    public function rename($oldname, $newname)
    {
        return rename($oldname, $newname);
    }

    public function copy($source, $destination)
    {
        return copy($source, $destination);
    }

    public function unlink($filepath)
    {
        return unlink($filepath);
    }
}
