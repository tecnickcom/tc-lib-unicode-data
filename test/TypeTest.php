<?php

/**
 * TypeTest.php
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

namespace Test;

use PHPUnit\Framework\TestCase;

/**
 * Type Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     UnicodeData
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2026 Nicola Asuni - Tecnick.com LTD
 * @license     https://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE)
 * @link        https://github.com/tecnickcom/tc-lib-unicode-data
 */
class TypeTest extends TestCase
{
    public function testStrong(): void
    {
        $this->assertEquals(3, \count(\Com\Tecnick\Unicode\Data\Type::STRONG));
    }

    public function testWeak(): void
    {
        $this->assertEquals(7, \count(\Com\Tecnick\Unicode\Data\Type::WEAK));
    }

    public function testNeutral(): void
    {
        $this->assertEquals(4, \count(\Com\Tecnick\Unicode\Data\Type::NEUTRAL));
    }

    public function testExplicitFormatting(): void
    {
        $this->assertEquals(9, \count(\Com\Tecnick\Unicode\Data\Type::EXPLICIT_FORMATTING));
    }

    public function testUnicodeVersion(): void
    {
        $this->assertEquals('17.0.0', \Com\Tecnick\Unicode\Data\Type::UNICODE_VERSION);
    }

    public function testUni(): void
    {
        $this->assertEquals(16_377, \count(\Com\Tecnick\Unicode\Data\Type::UNI));
        $this->assertArrayNotHasKey(0x0041, \Com\Tecnick\Unicode\Data\Type::UNI);
    }

    public function testGetType(): void
    {
        $expected = [
            0x0041 => 'L', // LATIN CAPITAL LETTER A (not listed, default L)
            0x4E00 => 'L', // CJK UNIFIED IDEOGRAPH-4E00 (not listed, default L)
            0x0020 => 'WS', // SPACE
            0x05D0 => 'R', // HEBREW LETTER ALEF
            0x05EF => 'R', // HEBREW YOD TRIANGLE
            0x061C => 'AL', // ARABIC LETTER MARK
            0x0660 => 'AN', // ARABIC-INDIC DIGIT ZERO
            0x08A1 => 'AL', // ARABIC LETTER BEH WITH HAMZA ABOVE (Arabic Extended-A)
            0x0870 => 'AL', // ARABIC LETTER ALEF WITH ATTACHED FATHA (Arabic Extended-B)
            0x0800 => 'R', // SAMARITAN LETTER ALAF
            0x1E900 => 'R', // ADLAM CAPITAL LETTER ALIF
            0x2066 => 'LRI', // LEFT-TO-RIGHT ISOLATE
            0x2069 => 'PDI', // POP DIRECTIONAL ISOLATE
            0x202A => 'LRE', // LEFT-TO-RIGHT EMBEDDING
            0x05C8 => 'R', // unassigned in the Hebrew block
            0x074B => 'AL', // unassigned in the Syriac block
            0x20C2 => 'ET', // unassigned in the Currency Symbols block
            0x8700A => 'L', // unassigned outside the default ranges
        ];

        foreach ($expected as $ord => $type) {
            $this->assertEquals($type, \Com\Tecnick\Unicode\Data\Type::getType($ord), \sprintf('U+%04X', $ord));
        }
    }
}
