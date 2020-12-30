<?php

use Icamys\SitemapGenerator\Extensions\GoogleVideoExtension;
use PHPUnit\Framework\TestCase;

class GoogleVideoExtensionTest extends TestCase
{
    public function testMissingFieldsException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields: title, description');

        $fields = [
            'thumbnail_loc' => 'test',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testMissingAtLeastOneFieldException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one of the following values are required but missing');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testTooLongDescriptionException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The field description must be less than or equal to a 2048');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => str_repeat('a', 2100),
            'content_loc' => 'test',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testContentLocSameAsLocException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The field content_loc must not be the same as');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'content_loc' => 'http://e.com',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testPlayerLocSameAsLocException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The field player_loc must not be the same as');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'http://e.com',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testInvalidDurationException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The duration value should be between 1 and 28800');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'duration' => 30000,
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testExpirationDateException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid expiration_date value');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'expiration_date' => '10.10.2020',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testInvalidRatingException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid rating value');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'rating' => '5.5',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testPublicationDateException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid publication_date value');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'publication_date' => '10.10.2020',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testFamlilyFriendlyException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid family_friendly value');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'family_friendly' => 'yep',
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testVideoRestrictionRelationshipException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid restriction.relationship value. Allowed values are allow or deny.');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'restriction' => [
                'relationship' => 'permit',
                'value' => 'UK',
            ],
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }

    public function testVideoRestrictionValueException() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value restriction.value is required');

        $fields = [
            'thumbnail_loc' => 'test',
            'title' => 'test',
            'description' => 'test',
            'player_loc' => 'test',
            'restriction' => [
                'relationship' => 'allow',
            ],
        ];
        GoogleVideoExtension::validate('http://e.com', $fields);
    }
}