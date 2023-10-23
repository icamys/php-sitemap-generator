<?php

namespace Unit\Extensions;

use PHPUnit\Framework\TestCase;
use function Icamys\SitemapGenerator\Extensions\has_string_keys;

class GoogleImageExtensionTest extends TestCase
{
    public function testHasStringKeys()
    {
        $this->assertTrue(has_string_keys(['foo' => 'bar']));
        $this->assertTrue(has_string_keys(['bar', 'baz' => 'foo']));
        $this->assertFalse(has_string_keys([0 => 'bar']));
        $this->assertFalse(has_string_keys(['bar', 'foo']));
    }
}
