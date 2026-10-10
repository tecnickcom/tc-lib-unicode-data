<?php

/**
 * LineBreakTest.php
 *
 * @since       2026-10-09
 * @category    Library
 * @package     UnicodeData
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2026 Nicola Asuni - Tecnick.com LTD
 * @license     https://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE)
 * @link        https://github.com/tecnickcom/tc-lib-unicode-data
 *
 * This file is part of tc-lib-unicode-data software library.
 */

namespace Test;

use Com\Tecnick\Unicode\Data\LineBreak;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * LineBreak Test
 *
 * @since       2026-10-09
 * @category    Library
 * @package     UnicodeData
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2026 Nicola Asuni - Tecnick.com LTD
 * @license     https://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE)
 * @link        https://github.com/tecnickcom/tc-lib-unicode-data
 */
class LineBreakTest extends TestCase
{
    /**
     * @return array<string, array{int, string}>
     */
    public static function classProvider(): array
    {
        return [
            'latin letter' => [0x0041, ''],
            'space' => [0x0020, ''],
            'exclamation mark' => [0x0021, 'EX'],
            'quotation mark' => [0x0022, 'QU'],
            'left parenthesis' => [0x0028, 'OP'],
            'right parenthesis' => [0x0029, 'CP'],
            'comma' => [0x002C, 'IS'],
            'em dash' => [0x2014, 'B2'],
            'horizontal ellipsis' => [0x2026, 'IN'],
            'ideographic comma' => [0x3001, 'CL'],
            'ideographic full stop' => [0x3002, 'CL'],
            'ideographic iteration mark' => [0x3005, 'NS'],
            'left corner bracket' => [0x300C, 'OP'],
            'right corner bracket' => [0x300D, 'CL'],
            'hiragana a' => [0x3042, 'ID'],
            'hiragana small a' => [0x3041, 'CJ'],
            'katakana small a' => [0x30A1, 'CJ'],
            'prolonged sound mark' => [0x30FC, 'CJ'],
            'katakana a' => [0x30A2, 'ID'],
            'first unified ideograph' => [0x4E00, 'ID'],
            'last unified ideograph' => [0x9FFF, 'ID'],
            'extension A' => [0x3400, 'ID'],
            'compatibility ideograph' => [0xF900, 'ID'],
            'hangul syllable ga (LV)' => [0xAC00, 'H2'],
            'hangul syllable gag (LVT)' => [0xAC01, 'H3'],
            'hangul syllable gae (LV)' => [0xAC1C, 'H2'],
            'last hangul syllable' => [0xD7A3, 'H3'],
            'hangul jamo' => [0x1100, ''],
            'fullwidth exclamation mark' => [0xFF01, 'EX'],
            'fullwidth left parenthesis' => [0xFF08, 'OP'],
            'plane 2 unassigned' => [0x2FFFD, 'ID'],
            'plane 3 unassigned' => [0x3FFFD, 'ID'],
            'plane 2 noncharacter' => [0x2FFFE, ''],
            'emoji base' => [0x1F466, ''],
            'emoji modifier' => [0x1F3FB, ''],
            'last code point' => [0x10FFFF, ''],
        ];
    }

    #[DataProvider('classProvider')]
    public function testGetClass(int $ord, string $class): void
    {
        $this->assertSame($class, LineBreak::getClass($ord));
    }

    public function testRangesAreSortedDisjointAndUseKnownClasses(): void
    {
        $prev = -1;
        foreach (LineBreak::RANGES as [$first, $last, $class]) {
            $this->assertGreaterThan($prev, $first);
            $this->assertGreaterThanOrEqual($first, $last);
            $this->assertContains($class, LineBreak::CLASSES);
            $this->assertTrue($last < LineBreak::HANGUL_FIRST || $first > LineBreak::HANGUL_LAST);
            $prev = $last;
        }
    }

    public function testRangeBoundaries(): void
    {
        foreach (LineBreak::RANGES as [$first, $last, $class]) {
            $this->assertSame($class, LineBreak::getClass($first));
            $this->assertSame($class, LineBreak::getClass($last));
        }
    }
}
