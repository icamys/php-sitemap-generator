<?php

namespace Icamys\SitemapGenerator;

use phpmock\phpunit\PHPMock;
use phpmock\spy\Spy;
use PHPUnit\Framework\TestCase;

class FileSystemTest extends TestCase
{
    use PHPMock;

    /**
     * @var FileSystem
     */
    private $fs;

    /**
     * @var Spy for file_put_contents function
     */
    private $filePutContentsSpy;

    /**
     * @var Spy for file_get_contents function
     */
    private $fileGetContentsSpy;

    /**
     * @var Spy for file_exists function
     */
    private $fileExistsSpy;

    public function testFilePutContentsCall() {
        $this->fs->file_put_contents('path', 'contents');
        $this->assertCount(1, $this->filePutContentsSpy->getInvocations());
        $this->assertEquals('path', $this->filePutContentsSpy->getInvocations()[0]->getArguments()[0]);
        $this->assertEquals('contents', $this->filePutContentsSpy->getInvocations()[0]->getArguments()[1]);
    }

    public function testFileGetContentsCall() {
        $this->fs->file_get_contents('path');
        $this->assertCount(1, $this->fileGetContentsSpy->getInvocations());
        $this->assertEquals('path', $this->fileGetContentsSpy->getInvocations()[0]->getArguments()[0]);
    }

    public function testFileExistsCall() {
        $this->fs->file_exists('path');
        $this->assertCount(1, $this->fileExistsSpy->getInvocations());
        $this->assertEquals('path', $this->fileExistsSpy->getInvocations()[0]->getArguments()[0]);
    }

    /**
     * @throws \phpmock\MockEnabledException
     */
    protected function setUp(): void
    {
        $this->fs = new FileSystem();
        $this->filePutContentsSpy = new Spy(__NAMESPACE__, "file_put_contents", function (){});
        $this->filePutContentsSpy->enable();
        $this->fileGetContentsSpy = new Spy(__NAMESPACE__, "file_get_contents", function (){});
        $this->fileGetContentsSpy->enable();
        $this->fileExistsSpy = new Spy(__NAMESPACE__, "file_exists", function (){});
        $this->fileExistsSpy->enable();
    }

    protected function tearDown(): void
    {
        unset($this->g);
        $this->filePutContentsSpy->disable();
        $this->fileGetContentsSpy->disable();
        $this->fileExistsSpy->disable();
    }
}