<?php

namespace App\Services\Conversion;

class OfficeToPdfRegistry
{
    private static ?array $configs = null;

    public static function get(string $type): OfficeToPdfConfig
    {
        $all = self::all();

        return $all[$type]
            ?? throw new \InvalidArgumentException("Unknown conversion type: {$type}");
    }

    /**
     * Central registry of all office-to-PDF conversion types.
     * To add a new type, add an entry here.
     */
    public static function all(): array
    {
        if (self::$configs !== null) {
            return self::$configs;
        }

        return self::$configs = [

            'word-to-pdf' => new OfficeToPdfConfig(
                type:         'word-to-pdf',
                operationType: 'word-to-pdf',
                mimes:        ['doc', 'docx', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                converter:    'libreoffice',
                outputPrefix: 'word-to-pdf',
                maxFiles:     10,
            ),

            'excel-to-pdf' => new OfficeToPdfConfig(
                type:         'excel-to-pdf',
                operationType: 'excel-to-pdf',
                mimes:        ['xls', 'xlsx', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                converter:    'libreoffice',
                outputPrefix: 'excel-to-pdf',
                maxFiles:     10,
            ),

            'ppt-to-pdf' => new OfficeToPdfConfig(
                type:          'ppt-to-pdf',
                operationType: 'ppt-to-pdf',
                mimes:         ['ppt', 'pptx', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
                converter:     'libreoffice',
                outputPrefix:  'ppt-to-pdf',
                maxFiles:      10,
            ),

            // Future example (uncomment & adjust when ready):
            // 'ppt-to-pdf' => new OfficeToPdfConfig(
            //     type:         'ppt-to-pdf',
            //     operationType: 'ppt-to-pdf',
            //     mimes:        ['ppt', 'pptx', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            //     converter:    'libreoffice',
            //     outputPrefix: 'ppt-to-pdf',
            //     maxFiles:     10,
            // ),

        ];
    }
}
