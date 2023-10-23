<?php

namespace Icamys\SitemapGenerator;

interface IConfig
{
    /**
     * @return string Base URL of the website.
     */
    public function getBaseURL(): string;

    /**
     * @return string URL of the sitemap file.
     */
    public function getSitemapIndexURL(): string;

    /**
     * @return string Path to the directory where the sitemap and robots files will be saved.
     */
    public function getSaveDirectory(): string;

    /**
     * @return IFileSystem|null File system abstraction.
     */
    public function getFS(): IFileSystem|null;

    /**
     * @return IRuntime|null Runtime abstraction.
     */
    public function getRuntime(): IRuntime|null;
}