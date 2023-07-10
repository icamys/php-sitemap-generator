<?php

namespace Icamys\SitemapGenerator;

class FileSystem
{
    /**
     * @return string|false
     */
    public function file_get_contents(string $filepath)
    {
        return file_get_contents($filepath);
    }

    /**
     * @param mixed $content
     * @return int|false
     */
    public function file_put_contents(string $filepath, $content, int $flags = 0)
    {
        return file_put_contents($filepath, $content, $flags);
    }

    public function file_exists(string $filepath): bool
    {
        return file_exists($filepath);
    }

    public function rename(string $oldname, string $newname): bool
    {
        return rename($oldname, $newname);
    }

    public function copy(string $source, string $destination): bool
    {
        return copy($source, $destination);
    }

    public function unlink(string $filepath): bool
    {
        return unlink($filepath);
    }
}
