<?php

namespace Icamys\SitemapGenerator\Extensions;

use InvalidArgumentException;
use XMLWriter;

class GoogleImageExtension
{
    private static $requiredFields = [
        'loc',
    ];

    public static function writeImageTag(XMLWriter $xmlWriter, array $extFields)
    {
        // If an assoc array, process it directly. If a numeric array, use foreach. Supported by PHP versions even older than 8.
        if(is_array($extFields) && array_keys($extFields) !== range(0, count($extFields) - 1))
        {
            self::writeImageTagSingle($xmlWriter, $extFields);
        }
        else
        {
            foreach ($extFields as $extFieldSingle)
            {
                self::writeImageTagSingle($xmlWriter, $extFieldSingle);
            }
        }
    }

    public static function writeImageTagSingle(XMLWriter $xmlWriter, array $extFields)
    {
        self::validate($extFields);

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

    public static function validate($extFields)
    {
        // If an assoc array, process it directly. If a numeric array, use foreach. Supported by PHP versions even older than 8.
        if(is_array($extFields) && array_keys($extFields) !== range(0, count($extFields) - 1))
        {
            self::validateSingle($extFields);
        }
        else
        {
            foreach ($extFields as $extFieldSingle)
            {
                self::validateSingle($extFieldSingle);
            }
        }
    }

    public static function validateSingle($extFields)
    {
        $extFieldNames = array_keys($extFields);

        if (count(array_intersect(self::$requiredFields, $extFieldNames)) !== count(self::$requiredFields)) {
            throw new InvalidArgumentException(
                sprintf("Missing required fields: %s", implode(', ', array_diff(self::$requiredFields, $extFieldNames)))
            );
        }
    }
}
