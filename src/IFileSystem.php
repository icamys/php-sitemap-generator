<?php

namespace Icamys\SitemapGenerator;

interface IFileSystem
{
    public function file_get_contents(string $filepath): bool|string;

    public function file_put_contents(string $filepath, mixed $content, int $flags = 0): bool|int;

    public function file_exists(string $filepath): bool;

    public function rename(string $oldName, string $newName): bool;

    public function copy(string $source, string $destination): bool;

    public function unlink(string $filepath): bool;
}