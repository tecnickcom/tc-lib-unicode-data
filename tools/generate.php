<?php

declare(strict_types=1);

/**
 * generate.php
 *
 * Regenerates the UCD-derived source files from the Unicode Character Database:
 * src/Arabic.php, src/Bracket.php, src/Mirror.php, src/Pattern.php, src/Type.php.
 *
 * Usage:
 *     make gendata
 *     php tools/generate.php [unicode-version]
 *
 * The UCD files are downloaded once into target/ucd/<version>/ and reused.
 * The output is formatted by "mago fmt", which the gendata target runs afterwards.
 *
 * @since       2026-08-06
 * @category    Library
 * @package     UnicodeData
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2026 Nicola Asuni - Tecnick.com LTD
 * @license     https://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE)
 * @link        https://github.com/tecnickcom/tc-lib-unicode-data
 *
 * This file is part of tc-lib-unicode-data software library.
 */

const DEFAULT_UCD_VERSION = '17.0.0';

const UCD_FILES = [
    'UnicodeData.txt' => 'UnicodeData.txt',
    'ArabicShaping.txt' => 'ArabicShaping.txt',
    'BidiBrackets.txt' => 'BidiBrackets.txt',
    'BidiMirroring.txt' => 'BidiMirroring.txt',
    'DerivedBidiClass.txt' => 'extracted/DerivedBidiClass.txt',
];

/**
 * Long Bidi_Class property value names to the abbreviations used by this package.
 */
const BIDI_CLASS_ALIAS = [
    'Left_To_Right' => 'L',
    'Right_To_Left' => 'R',
    'Arabic_Letter' => 'AL',
    'European_Number' => 'EN',
    'European_Separator' => 'ES',
    'European_Terminator' => 'ET',
    'Arabic_Number' => 'AN',
    'Common_Separator' => 'CS',
    'Nonspacing_Mark' => 'NSM',
    'Boundary_Neutral' => 'BN',
    'Paragraph_Separator' => 'B',
    'Segment_Separator' => 'S',
    'White_Space' => 'WS',
    'Other_Neutral' => 'ON',
];

/**
 * General categories that default to Joining_Type=Transparent.
 */
const TRANSPARENT_CATEGORIES = ['Mn', 'Me', 'Cf'];

$version = $argv[1] ?? DEFAULT_UCD_VERSION;
$rootDir = \dirname(__DIR__);
$cacheDir = $rootDir . '/target/ucd/' . $version;
$srcDir = $rootDir . '/src';

$ucd = loadUcd($version, $cacheDir);

$unicodeData = parseUnicodeData($ucd['UnicodeData.txt']);
$bidi = parseBidiClasses($ucd['DerivedBidiClass.txt']);
$joining = parseJoiningTypes($ucd['ArabicShaping.txt'], $unicodeData['category']);
$forms = parseArabicForms($unicodeData['decomposition']);

writeFile($srcDir . '/Type.php', renderType($version, $bidi));
writeFile($srcDir . '/Pattern.php', renderPattern($version, $bidi['class']));
writeFile($srcDir . '/Mirror.php', renderMirror($version, parseMirroring($ucd['BidiMirroring.txt'])));
writeFile(
    $srcDir . '/Bracket.php',
    renderBracket($version, parseBrackets($ucd['BidiBrackets.txt']), $unicodeData['name']),
);
writeFile($srcDir . '/Arabic.php', renderArabic($version, $forms, $joining, $unicodeData['name']));

// ----------------------------------------------------------------------------------------------- UCD input

/**
 * Downloads the UCD files (once) and returns their content indexed by file name.
 *
 * @return array<string, string>
 */
function loadUcd(string $version, string $cacheDir): array
{
    if (!\is_dir($cacheDir) && !\mkdir($cacheDir, 0o775, true)) {
        throw new RuntimeException('unable to create ' . $cacheDir);
    }

    $out = [];

    foreach (UCD_FILES as $name => $path) {
        $local = $cacheDir . '/' . $name;

        if (!\is_file($local)) {
            $url = 'https://www.unicode.org/Public/' . $version . '/ucd/' . $path;
            \fwrite(\STDERR, 'downloading ' . $url . \PHP_EOL);
            $data = \file_get_contents($url);

            if ($data === false) {
                throw new RuntimeException('unable to download ' . $url);
            }

            \file_put_contents($local, $data);
        }

        $content = \file_get_contents($local);

        if ($content === false) {
            throw new RuntimeException('unable to read ' . $local);
        }

        $out[$name] = $content;
    }

    return $out;
}

/**
 * Splits a UCD data line into its semicolon-separated fields, comment excluded.
 *
 * @return array<int, string>|null
 */
function fields(string $line): ?array
{
    $data = \trim(\explode('#', $line, 2)[0]);

    if ($data === '') {
        return null;
    }

    return \array_map('trim', \explode(';', $data));
}

/**
 * Expands a "XXXX" or "XXXX..YYYY" code point field.
 *
 * @return array{int, int}
 */
function codeRange(string $field): array
{
    $part = \explode('..', $field);
    $first = (int) \hexdec($part[0]);

    return [$first, isset($part[1]) ? (int) \hexdec($part[1]) : $first];
}

/**
 * Parses UnicodeData.txt into character names, general categories and decomposition mappings.
 *
 * @return array{
 *     name: array<int, string>,
 *     category: array<int, string>,
 *     decomposition: array<int, array{string, array<int, int>}>,
 * }
 */
function parseUnicodeData(string $content): array
{
    $name = [];
    $category = [];
    $decomposition = [];
    $rangeStart = null;

    foreach (\explode("\n", $content) as $line) {
        $field = fields($line);

        if ($field === null) {
            continue;
        }

        $code = (int) \hexdec($field[0]);

        if (\str_ends_with($field[1], ', First>')) {
            $rangeStart = $code;
            continue;
        }

        if (\str_ends_with($field[1], ', Last>')) {
            for ($cpt = $rangeStart ?? $code; $cpt <= $code; ++$cpt) {
                $category[$cpt] = $field[2];
            }

            $rangeStart = null;
            continue;
        }

        $name[$code] = $field[1];
        $category[$code] = $field[2];

        if ($field[5] !== '' && \str_starts_with($field[5], '<')) {
            [$tag, $mapping] = \explode('>', $field[5], 2);
            $decomposition[$code] = [
                \substr($tag, 1),
                \array_map(static fn(string $hex): int => (int) \hexdec($hex), \preg_split('/\s+/', \trim($mapping)) ?: []),
            ];
        }
    }

    return ['name' => $name, 'category' => $category, 'decomposition' => $decomposition];
}

/**
 * Parses extracted/DerivedBidiClass.txt.
 *
 * The returned "class" map only holds the code points whose Bidi_Class is not L,
 * the "default" list holds the @missing ranges (excluding the 0000..10FFFF L default).
 *
 * @return array{class: array<int, string>, default: array<int, array{int, int, string}>}
 */
function parseBidiClasses(string $content): array
{
    $class = [];
    $default = [];

    foreach (\explode("\n", $content) as $line) {
        if (\preg_match('/^#\s*@missing:\s*([0-9A-F.]+)\s*;\s*(\w+)/', $line, $match) === 1) {
            [$first, $last] = codeRange($match[1]);

            if ($first === 0 && $last === 0x10FFFF) {
                continue;
            }

            $default[] = [$first, $last, BIDI_CLASS_ALIAS[$match[2]] ?? throw new RuntimeException($match[2])];
            continue;
        }

        $field = fields($line);

        if ($field === null || $field[1] === 'L') {
            continue;
        }

        [$first, $last] = codeRange($field[0]);

        for ($cpt = $first; $cpt <= $last; ++$cpt) {
            $class[$cpt] = $field[1];
        }
    }

    \ksort($class);
    \usort($default, static fn(array $one, array $two): int => $one[0] <=> $two[0]);

    return ['class' => $class, 'default' => $default];
}

/**
 * Parses ArabicShaping.txt and adds the Mn/Me/Cf characters that default to Transparent.
 *
 * @param array<int, string> $category
 *
 * @return array<int, string>
 */
function parseJoiningTypes(string $content, array $category): array
{
    $joining = [];

    foreach (\explode("\n", $content) as $line) {
        $field = fields($line);

        if ($field === null || !isset($field[2])) {
            continue;
        }

        $joining[(int) \hexdec($field[0])] = $field[2];
    }

    foreach ($category as $code => $gcat) {
        if (\in_array($gcat, TRANSPARENT_CATEGORIES, true) && !isset($joining[$code])) {
            $joining[$code] = 'T';
        }
    }

    \ksort($joining);

    return $joining;
}

/**
 * Parses BidiMirroring.txt.
 *
 * @return array<int, int>
 */
function parseMirroring(string $content): array
{
    $mirror = [];

    foreach (\explode("\n", $content) as $line) {
        $field = fields($line);

        if ($field === null || !isset($field[1])) {
            continue;
        }

        $mirror[(int) \hexdec($field[0])] = (int) \hexdec($field[1]);
    }

    \ksort($mirror);

    return $mirror;
}

/**
 * Parses BidiBrackets.txt.
 *
 * @return array<int, int> Opening bracket code point => closing bracket code point.
 */
function parseBrackets(string $content): array
{
    $open = [];

    foreach (\explode("\n", $content) as $line) {
        $field = fields($line);

        if ($field === null || ($field[2] ?? '') !== 'o') {
            continue;
        }

        $open[(int) \hexdec($field[0])] = (int) \hexdec($field[1]);
    }

    \ksort($open);

    return $open;
}

/**
 * Extracts the Arabic presentation forms from the decomposition mappings.
 *
 * @param array<int, array{string, array<int, int>}> $decomposition
 *
 * @return array{
 *     substitute: array<int, array<int, int>>,
 *     laa: array<int, array<int, int>>,
 *     diacritic: array<int, int>,
 * }
 */
function parseArabicForms(array $decomposition): array
{
    $shape = ['isolated' => 0, 'final' => 1, 'initial' => 2, 'medial' => 3];
    $substitute = [];
    $laa = [];
    $diacritic = [];

    foreach ($decomposition as $code => [$tag, $mapping]) {
        if (!isset($shape[$tag])) {
            continue;
        }

        $index = $shape[$tag];

        if (\count($mapping) === 1) {
            $substitute[$mapping[0]][$index] = $code;
            continue;
        }

        if (\count($mapping) === 2 && $mapping[0] === 0x0644 && $code >= 0xFEF5 && $code <= 0xFEFC) {
            // Mandatory LAM + ALEF variant ligature.
            $laa[$mapping[1]][$index] = $code;
            continue;
        }

        if (\count($mapping) === 3 && $mapping[0] === 0x0020 && \in_array(0x0651, $mapping, true)) {
            // SHADDA + diacritic ligature.
            $diacritic[$mapping[1] === 0x0651 ? $mapping[2] : $mapping[1]] = $code;
        }
    }

    return [
        'substitute' => completeForms($substitute),
        'laa' => completeForms($laa),
        'diacritic' => sortedByKey($diacritic),
    ];
}

/**
 * Fills the missing shapes of every row: the initial form falls back to the isolated
 * one and the medial form to the final one, so that every row has four entries.
 *
 * @param array<int, array<int, int>> $rows
 *
 * @return array<int, array<int, int>>
 */
function completeForms(array $rows): array
{
    $out = [];

    foreach (sortedByKey($rows) as $code => $form) {
        $isolated = $form[0] ?? $form[1] ?? null;

        if ($isolated === null) {
            continue;
        }

        $final = $form[1] ?? $isolated;
        $out[$code] = [$isolated, $final, $form[2] ?? $isolated, $form[3] ?? $final];
    }

    return $out;
}

/**
 * @param array<int, mixed> $map
 *
 * @return array<int, mixed>
 */
function sortedByKey(array $map): array
{
    \ksort($map);

    return $map;
}

// ----------------------------------------------------------------------------------------------- rendering

/**
 * Renders the file and class docblocks shared by all the generated files.
 */
function docblock(string $class, string $version, string $description, string $annotation = ''): string
{
    $extra = $annotation === '' ? '' : "\n * " . $annotation;

    return <<<PHP
        <?php

        declare(strict_types=1);

        /**
         * {$class}.php
         *
         * @since       2011-05-23
         * @category    Library
         * @package     UnicodeData
         * @author      Nicola Asuni <info@tecnick.com>
         * @copyright   2011-2026 Nicola Asuni - Tecnick.com LTD
         * @license     https://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE)
         * @link        https://github.com/tecnickcom/tc-lib-unicode-data
         *
         * This file is part of tc-lib-unicode-data software library.
         */

        namespace Com\Tecnick\Unicode\Data;

        /**
         * Com\Tecnick\Unicode\Data\\{$class}
         *
         * {$description}
         *
         * Generated by tools/generate.php from the Unicode Character Database {$version}.
         * Do not edit manually.
         *
         * @since       2011-05-23
         * @category    Library
         * @package     UnicodeData
         * @author      Nicola Asuni <info@tecnick.com>
         * @copyright   2011-2026 Nicola Asuni - Tecnick.com LTD
         * @license     https://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE)
         * @link        https://github.com/tecnickcom/tc-lib-unicode-data{$extra}
         */

        PHP;
}

/**
 * Formats a code point as a hexadecimal literal, with the digits grouped by four.
 */
function hexCode(int $code): string
{
    $digit = \sprintf('%04X', $code);

    if (\strlen($digit) > 4) {
        $digit = \substr($digit, 0, -4) . '_' . \substr($digit, -4);
    }

    return '0x' . $digit;
}

/**
 * Renders src/Type.php.
 *
 * @param array{class: array<int, string>, default: array<int, array{int, int, string}>} $bidi
 */
function renderType(string $version, array $bidi): string
{
    $uni = '';

    foreach ($bidi['class'] as $code => $type) {
        $uni .= \sprintf("        %s => '%s',\n", hexCode($code), $type);
    }

    $defaults = '';
    $min = \PHP_INT_MAX;
    $max = 0;

    foreach ($bidi['default'] as [$first, $last, $type]) {
        $defaults .= \sprintf("        [%s, %s, '%s'],\n", hexCode($first), hexCode($last), $type);
        $min = \min($min, $first);
        $max = \max($max, $last);
    }

    $head = docblock(
        'Type',
        $version,
        'Bidirectional character types (UAX #9).',
        '@SuppressWarnings("PHPMD.ExcessiveClassLength")',
    );
    $lowest = hexCode($min);
    $highest = hexCode($max);

    return $head . <<<PHP

        class Type
        {
            /**
             * Version of the Unicode Character Database the tables are derived from.
             */
            public const UNICODE_VERSION = '{$version}';

            /**
             * Array of Strong bidirectional character types.
             *
             * @var array<string, string>
             */
            public const STRONG = [
                'L' => 'L', // Left-to-Right
                'R' => 'R', // Right-to-Left
                'AL' => 'AL', // Right-to-Left Arabic
            ];

            /**
             * Array of Weak bidirectional character types.
             *
             * @var array<string, string>
             */
            public const WEAK = [
                'EN' => 'EN', // European Number
                'ES' => 'ES', // European Number Separator
                'ET' => 'ET', // European Number Terminator
                'AN' => 'AN', // Arabic Number
                'CS' => 'CS', // Common Number Separator
                'NSM' => 'NSM', // Nonspacing Mark
                'BN' => 'BN', // Boundary Neutral
            ];

            /**
             * Array of Neutral bidirectional character types.
             *
             * @var array<string, string>
             */
            public const NEUTRAL = [
                'B' => 'B', // Paragraph Separator
                'S' => 'S', // Segment Separator
                'WS' => 'WS', // Whitespace
                'ON' => 'ON', // Other Neutrals
            ];

            /**
             * Array of Explicit formatting codes.
             *
             * @var array<string, int>
             */
            public const EXPLICIT_FORMATTING = [
                'LRE' => 0x202A, // Left-to-Right Embedding
                'LRO' => 0x202D, // Left-to-Right Override
                'RLE' => 0x202B, // Right-to-Left Embedding
                'RLO' => 0x202E, // Right-to-Left Override
                'PDF' => 0x202C, // Pop Directional Format
                'LRI' => 0x2066, // Left-to-Right Isolate
                'RLI' => 0x2067, // Right-to-Left Isolate
                'FSI' => 0x2068, // First Strong Isolate
                'PDI' => 0x2069, // Pop Directional Isolate
            ];

            /**
             * Bidirectional type of the code points whose type is not L (Left-to-Right).
             * Code points missing from this map take the type returned by getType().
             *
             * @var array<int, string>
             */
            public const UNI = [
        {$uni}    ];

            /**
             * Bidirectional type of the unassigned code points of the blocks whose default
             * type is not L, as [first code point, last code point, type].
             *
             * @var array<int, array{int, int, string}>
             */
            public const DEFAULT_RANGES = [
        {$defaults}    ];

            /**
             * Get the bidirectional type of a Unicode code point.
             *
             * @param int \$ord Unicode code point.
             */
            public static function getType(int \$ord): string
            {
                if (isset(self::UNI[\$ord])) {
                    return self::UNI[\$ord];
                }

                if (\$ord < {$lowest} || \$ord > {$highest}) {
                    return 'L';
                }

                foreach (self::DEFAULT_RANGES as [\$first, \$last, \$type]) {
                    if (\$ord < \$first) {
                        break;
                    }

                    if (\$ord <= \$last) {
                        return \$type;
                    }
                }

                return 'L';
            }

            /**
             * Get the simple (non-explicit) bidirectional class of a Unicode code point
             * as a typed enum case.
             *
             * Returns null when the code point maps to an explicit formatting code
             * (LRE, LRO, RLE, RLO, PDF, LRI, RLI, FSI, PDI), which is not part of the
             * strong/weak/neutral BidiClass set.
             *
             * @param int \$ord Unicode code point.
             */
            public static function getBidiClass(int \$ord): ?BidiClass
            {
                return BidiClass::tryFrom(self::getType(\$ord));
            }
        }

        PHP;
}

/**
 * Renders src/Pattern.php.
 *
 * @param array<int, string> $class
 */
function renderPattern(string $version, array $class): string
{
    $rtl = [];
    $arabic = [];

    foreach ($class as $code => $type) {
        if ($type === 'R') {
            $rtl[] = $code;
        } elseif ($type === 'AL' || $type === 'AN') {
            $arabic[] = $code;
        }
    }

    // Explicit right-to-left formatting characters.
    $rtl[] = 0x202B; // RLE
    $rtl[] = 0x202E; // RLO
    $rtl[] = 0x2067; // RLI
    \sort($rtl);

    $head = docblock('Pattern', $version, 'Regular expressions matching directional text.');

    $rtlClass = characterClass($rtl);
    $arabicClass = characterClass($arabic);

    return $head . <<<PHP

        class Pattern
        {
            /**
             * Pattern matching the strong right-to-left characters (Bidi_Class=R) and the
             * right-to-left explicit formatting characters (RLE, RLO, RLI).
             * Arabic characters are matched by the ARABIC pattern instead.
             * The subject string must be valid UTF-8.
             */
            public const RTL = '/['
        {$rtlClass}        . ']/u';

            /**
             * Pattern matching the Arabic characters (Bidi_Class=AL and Bidi_Class=AN).
             * The subject string must be valid UTF-8.
             */
            public const ARABIC = '/['
        {$arabicClass}        . ']/u';
        }

        PHP;
}

/**
 * Renders the concatenated body of a PCRE character class covering the given code points.
 *
 * @param array<int, int> $codes
 */
function characterClass(array $codes, int $width = 92): string
{
    $items = [];

    foreach (mergeRanges($codes) as [$first, $last]) {
        $items[] = match (true) {
            $first === $last => \sprintf('\x{%04X}', $first),
            $last === $first + 1 => \sprintf('\x{%04X}\x{%04X}', $first, $last),
            default => \sprintf('\x{%04X}-\x{%04X}', $first, $last),
        };
    }

    $out = '';
    $line = '';

    foreach ($items as $item) {
        if ($line !== '' && \strlen($line . $item) > $width) {
            $out .= "        . '" . $line . "'\n";
            $line = '';
        }

        $line .= $item;
    }

    return $out . "        . '" . $line . "'\n";
}

/**
 * Groups a sorted list of code points into contiguous ranges.
 *
 * @param array<int, int> $codes
 *
 * @return array<int, array{int, int}>
 */
function mergeRanges(array $codes): array
{
    $ranges = [];
    $first = null;
    $last = null;

    foreach ($codes as $code) {
        if ($last !== null && $code === $last + 1) {
            $last = $code;
            continue;
        }

        if ($first !== null && $last !== null) {
            $ranges[] = [$first, $last];
        }

        $first = $code;
        $last = $code;
    }

    if ($first !== null && $last !== null) {
        $ranges[] = [$first, $last];
    }

    return $ranges;
}

/**
 * Renders src/Mirror.php.
 *
 * @param array<int, int> $mirror
 */
function renderMirror(string $version, array $mirror): string
{
    $rows = '';

    foreach ($mirror as $code => $pair) {
        $rows .= \sprintf("        %s => %s,\n", hexCode($code), hexCode($pair));
    }

    $head = docblock('Mirror', $version, 'Bidi_Mirroring_Glyph property values (BidiMirroring.txt).');

    return $head . <<<PHP

        class Mirror
        {
            /**
             * Mirrored form of the characters that are mirrored when displayed in a
             * right-to-left context (rule L4 of UAX #9).
             *
             * @var array<int, int>
             */
            public const UNI = [
        {$rows}    ];
        }

        PHP;
}

/**
 * Renders src/Bracket.php.
 *
 * @param array<int, int>    $open
 * @param array<int, string> $name
 */
function renderBracket(string $version, array $open, array $name): string
{
    $rows = '';
    $flipped = '';

    foreach ($open as $code => $pair) {
        $comment = \str_replace([' LEFT', 'LEFT '], ['', ''], $name[$code] ?? '');
        $rows .= \sprintf("        %s => %s, // %s\n", hexCode($code), hexCode($pair), $comment);
        $flipped .= \sprintf("        %s => %s, // %s\n", hexCode($pair), hexCode($code), $comment);
    }

    $head = docblock('Bracket', $version, 'Paired bracket properties (BidiBrackets.txt).');

    return $head . <<<PHP

        class Bracket
        {
            /**
             * Opening paired brackets (Bidi_Paired_Bracket_Type=Open) and their closing counterpart.
             *
             * @var array<int, int>
             */
            public const OPEN = [
        {$rows}    ];

            /**
             * Closing paired brackets (Bidi_Paired_Bracket_Type=Close) and their opening counterpart.
             *
             * @var array<int, int>
             */
            public const CLOSE = [
        {$flipped}    ];
        }

        PHP;
}

/**
 * Renders src/Arabic.php.
 *
 * @param array{
 *     substitute: array<int, array<int, int>>,
 *     laa: array<int, array<int, int>>,
 *     diacritic: array<int, int>,
 * } $forms
 * @param array<int, string> $joining
 * @param array<int, string> $name
 */
function renderArabic(string $version, array $forms, array $joining, array $name): string
{
    $substitute = '';

    foreach ($forms['substitute'] as $code => $shape) {
        $substitute .= \sprintf(
            "        %s => [%s, %s, %s, %s], // %s\n",
            hexCode($code),
            hexCode($shape[0]),
            hexCode($shape[1]),
            hexCode($shape[2]),
            hexCode($shape[3]),
            $name[$code] ?? '',
        );
    }

    $laa = '';

    foreach ($forms['laa'] as $code => $shape) {
        $laa .= \sprintf(
            "        %s => [%s, %s, %s, %s], // LAM + %s\n",
            hexCode($code),
            hexCode($shape[0]),
            hexCode($shape[1]),
            hexCode($shape[2]),
            hexCode($shape[3]),
            $name[$code] ?? '',
        );
    }

    $diacritic = '';

    foreach ($forms['diacritic'] as $code => $ligature) {
        $diacritic .= \sprintf(
            "        %s => %s, // SHADDA + %s\n",
            hexCode($code),
            hexCode($ligature),
            $name[$code] ?? '',
        );
    }

    $joiningRows = '';
    $end = '';

    foreach ($joining as $code => $type) {
        $joiningRows .= \sprintf("        %s => '%s',\n", hexCode($code), $type);

        if (($type === 'R' || $type === 'U') && isset($name[$code])) {
            $end .= \sprintf("        %s, // %s\n", hexCode($code), $name[$code]);
        }
    }

    $head = docblock(
        'Arabic',
        $version,
        'Arabic shaping data (ArabicShaping.txt and UnicodeData.txt).',
        '@SuppressWarnings("PHPMD.ExcessiveClassLength")',
    );

    return $head . <<<PHP

        class Arabic
        {
            /**
             * Unicode code for ARABIC QUESTION MARK (U+061F)
             */
            public const QUESTION_MARK = 0x061F;

            /**
             * Unicode code for ARABIC LETTER LAM (U+0644)
             */
            public const LAM = 0x0644;

            /**
             * Unicode code for ARABIC LETTER HEH (U+0647)
             */
            public const HEH = 0x0647;

            /**
             * Unicode code for ARABIC SHADDA (U+0651)
             */
            public const SHADDA = 0x0651;

            /**
             * Unicode code for ARABIC LIGATURE ALLAH ISOLATED FORM (U+FDF2)
             */
            public const LIGATURE_ALLAH_ISOLATED_FORM = 0xFDF2;

            /**
             * Joining_Type of a character: U (non-joining), R (right-joining), D (dual-joining),
             * C (join-causing), L (left-joining) or T (transparent).
             * Code points missing from this map are non-joining.
             *
             * @var array<int, string>
             */
            public const JOINING = [
        {$joiningRows}    ];

            /**
             * Arabic shape substitutions: char code => [isolated, final, initial, medial].
             * Characters without a distinct initial or medial form repeat the isolated
             * and final ones.
             *
             * @var array<int, array<int, int>>
             */
            public const SUBSTITUTE = [
        {$substitute}    ];

            /**
             * Lam-alef ligatures: alef char code => [isolated, final, initial, medial].
             *
             * @var array<int, array<int, int>>
             */
            public const LAA = [
        {$laa}    ];

            /**
             * Ligatures combining SHADDA (U+0651) with a second diacritic, so that the two
             * marks do not overlap: second mark char code => ligature char code.
             *
             * @var array<int, int>
             */
            public const DIACRITIC = [
        {$diacritic}    ];

            /**
             * Characters that never join to the following character
             * (Joining_Type R or U as listed in ArabicShaping.txt).
             *
             * @var array<int>
             */
            public const END = [
        {$end}    ];

            /**
             * Get the Joining_Type of a Unicode code point.
             *
             * @param int \$ord Unicode code point.
             */
            public static function getJoiningType(int \$ord): string
            {
                return self::JOINING[\$ord] ?? 'U';
            }
        }

        PHP;
}

/**
 * Writes a generated file and reports its size.
 */
function writeFile(string $path, string $content): void
{
    if (\file_put_contents($path, $content) === false) {
        throw new RuntimeException('unable to write ' . $path);
    }

    \fwrite(\STDERR, \sprintf("%-24s %7d lines\n", \basename($path), \substr_count($content, "\n")));
}
