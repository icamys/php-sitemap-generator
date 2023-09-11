<?php

namespace Icamys\SitemapGenerator;

class Config implements IConfig
{
    /**
     * @var string Base URL of the website.
     * It is used as a prefix to the paths added to sitemap using addURL() method.
     */
    public string $baseURL;

    /**
     * @var string Path to the directory where the sitemap and robots files will be saved.
     */
    public string $saveDirectory = "";

    public IFileSystem|null $fs;

    public IRuntime|null $runtime;

    public function __construct()
    {
        $this->fs = null;
        $this->runtime = null;
    }

    /**
     * @return string
     */
    public function getBaseURL(): string
    {
        return $this->baseURL;
    }

    /**
     * @param string $baseURL
     * @return Config
     */
    public function setBaseURL(string $baseURL): Config
    {
        $this->baseURL = $baseURL;
        return $this;
    }

    /**
     * @return string
     */
    public function getSaveDirectory(): string
    {
        return $this->saveDirectory;
    }

    /**
     * @param string $saveDirectory
     * @return Config
     */
    public function setSaveDirectory(string $saveDirectory): Config
    {
        $this->saveDirectory = $saveDirectory;
        return $this;
    }

    /**
     * @return IFileSystem|null
     */
    public function getFS(): IFileSystem|null
    {
        return $this->fs;
    }

    /**
     * @param IFileSystem|null $fs
     * @return Config
     */
    public function setFS(IFileSystem|null $fs): Config
    {
        $this->fs = $fs;
        return $this;
    }

    /**
     * @return IRuntime|null
     */
    public function getRuntime(): IRuntime|null
    {
        return $this->runtime;
    }

    /**
     * @param IRuntime|null $runtime
     * @return Config
     */
    public function setRuntime(IRuntime|null $runtime): Config
    {
        $this->runtime = $runtime;
        return $this;
    }
}