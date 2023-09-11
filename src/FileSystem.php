<?php

namespace Icamys\SitemapGenerator;

class FileSystem implements IFileSystem
{
    public function file_get_contents(string $filepath): bool|string
    {
        return file_get_contents($filepath);
    }

    public function file_put_contents(string $filepath, mixed $content, int $flags = 0): bool|int
    {
        return file_put_contents($filepath, $content, $flags);
    }

    public function file_exists(string $filepath): bool
    {
        return file_exists($filepath);
    }

    public function rename(string $oldName, string $newName): bool
    {
        return rename($oldName, $newName);
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
