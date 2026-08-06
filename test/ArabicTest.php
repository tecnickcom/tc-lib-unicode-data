<?php

/**
 * ArabicTest.php
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
 * Arabic Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     UnicodeData
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2026 Nicola Asuni - Tecnick.com LTD
 * @license     https://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE)
 * @link        https://github.com/tecnickcom/tc-lib-unicode-data
 */
class ArabicTest extends TestCase
{
    public function testDiacritic(): void
    {
        $this->assertEquals(6, \count(\Com\Tecnick\Unicode\Data\Arabic::DIACRITIC));
        // SHADDA (U+0651) + SUPERSCRIPT ALEF (U+0670)
        $this->assertEquals(0xFC63, \Com\Tecnick\Unicode\Data\Arabic::DIACRITIC[0x0670] ?? null);
    }

    public function testlaa(): void
    {
        $this->assertEquals(4, \count(\Com\Tecnick\Unicode\Data\Arabic::LAA));
    }

    public function testSubstitute(): void
    {
        $this->assertEquals(76, \count(\Com\Tecnick\Unicode\Data\Arabic::SUBSTITUTE));

        foreach (\Com\Tecnick\Unicode\Data\Arabic::SUBSTITUTE as $ord => $forms) {
            $this->assertCount(4, $forms, \sprintf('U+%04X', $ord));
        }

        // TEH MARBUTA (U+0629) has no initial or medial form: they repeat the isolated and final ones.
        $this->assertEquals(
            [0xFE93, 0xFE94, 0xFE93, 0xFE94],
            \Com\Tecnick\Unicode\Data\Arabic::SUBSTITUTE[0x0629] ?? null,
        );
    }

    public function testJoining(): void
    {
        $this->assertEquals('D', \Com\Tecnick\Unicode\Data\Arabic::getJoiningType(0x0628)); // BEH
        $this->assertEquals('R', \Com\Tecnick\Unicode\Data\Arabic::getJoiningType(0x0627)); // ALEF
        $this->assertEquals('C', \Com\Tecnick\Unicode\Data\Arabic::getJoiningType(0x0640)); // TATWEEL
        $this->assertEquals('C', \Com\Tecnick\Unicode\Data\Arabic::getJoiningType(0x200D)); // ZWJ
        $this->assertEquals('U', \Com\Tecnick\Unicode\Data\Arabic::getJoiningType(0x200C)); // ZWNJ
        $this->assertEquals('U', \Com\Tecnick\Unicode\Data\Arabic::getJoiningType(0x0621)); // HAMZA
        $this->assertEquals('T', \Com\Tecnick\Unicode\Data\Arabic::getJoiningType(0x064E)); // FATHA
        $this->assertEquals('U', \Com\Tecnick\Unicode\Data\Arabic::getJoiningType(0x0041)); // LATIN CAPITAL A
    }

    public function testEnd(): void
    {
        // Characters that never join to the following one.
        foreach ([0x0621, 0x0627, 0x0629, 0x0648, 0x0671, 0x0691, 0x06C7, 0x06D2] as $ord) {
            $this->assertContains($ord, \Com\Tecnick\Unicode\Data\Arabic::END, \sprintf('U+%04X', $ord));
        }

        // Dual-joining and join-causing characters.
        foreach ([0x0628, 0x0644, 0x0640] as $ord) {
            $this->assertNotContains($ord, \Com\Tecnick\Unicode\Data\Arabic::END, \sprintf('U+%04X', $ord));
        }
    }
}
