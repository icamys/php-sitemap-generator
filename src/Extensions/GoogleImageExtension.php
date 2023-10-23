<?php

namespace Icamys\SitemapGenerator\Extensions;

use InvalidArgumentException;
use XMLWriter;

class GoogleImageExtension
{
    /**
     * @var int Maximum number of images allowed per page.
     */
    private const maxImageCount = 1000;

    /**
     * @var string
     */
    private const maxImageCountRefLink = 'https://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd';

    private static array $requiredFields = [
        'loc',
    ];

    /**
     * @param XMLWriter $xmlWriter
     * @param array $extFields
     * @return void
     * @throws InvalidArgumentException
     */
    public static function writeImageTag(XMLWriter $xmlWriter, array $extFields): void
    {
        if (has_string_keys($extFields)) {
            self::writeImageTagSingle($xmlWriter, $extFields);
        } else {
            if (count($extFields) > self::maxImageCount) {
                throw new InvalidArgumentException(
                    sprintf("Too many images for a single URL. Maximum number of images allowed per page is %d, got %d. For more information, see %s",
                        self::maxImageCount,
                        count($extFields),
                        self::maxImageCountRefLink
                    )
                );
            }

            foreach ($extFields as $extFieldSingle) {
                self::writeImageTagSingle($xmlWriter, $extFieldSingle);
            }
        }
    }

    /**
     * @param XMLWriter $xmlWriter
     * @param array $extFields
     * @return void
     * @throws InvalidArgumentException
     */
    private static function writeImageTagSingle(XMLWriter $xmlWriter, array $extFields): void
    {
        self::validateEntryFields($extFields);

        $xmlWriter->startElement('image:image');
        $xmlWriter->writeElement('image:loc', $extFields['loc']);

        if (isset($extFields['title'])) {
            $xmlWriter->writeElement('image:title', htmlentities($extFields['title'], ENT_QUOTES));
        }

        if (isset($extFields['caption'])) {
            $xmlWriter->writeElement('image:caption', $extFields['caption']);
        }

        if (isset($extFields['geo_location'])) {
            $xmlWriter->writeElement('image:geo_location', $extFields['geo_location']);
        }

        if (isset($extFields['license'])) {
            $xmlWriter->writeElement('image:license', $extFields['license']);
        }

        $xmlWriter->endElement();
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function validateEntryFields($fields): void
    {
        if (has_string_keys($fields))  {
            self::validateSingleEntryFields($fields);
        }  else {
            foreach ($fields as $extFieldSingle) {
                self::validateSingleEntryFields($extFieldSingle);
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function validateSingleEntryFields($fields): void
    {
        $extFieldNames = array_keys($fields);

        if (count(array_intersect(self::$requiredFields, $extFieldNames)) !== count(self::$requiredFields)) {
            throw new InvalidArgumentException(
                sprintf("Missing required fields: %s", implode(', ', array_diff(self::$requiredFields, $extFieldNames)))
            );
        }
    }
}

/**
 * @param array $array
 * @return bool
 */
function has_string_keys(array $array): bool
{
    return count(array_filter(array_keys($array), 'is_string')) > 0;
}
