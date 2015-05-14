<?php
/**
 * PatternTest.php
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfFontData
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2015 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-font-data
 *
 * This file is part of tc-lib-pdf-font-data software library.
 */

namespace Test;

/**
 * Pattern Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfFontData
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2015 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-font-data
 */
class PatternTest extends \PHPUnit_Framework_TestCase
{
    public function testPatterns()
    {
        $str = 'hello world';
        $this->assertEquals(0, preg_match(\Com\Tecnick\Pdf\Font\Data\Pattern::ARABIC, $str));
        $this->assertEquals(0, preg_match(\Com\Tecnick\Pdf\Font\Data\Pattern::RTL, $str));

        $str = 'مرحبا بالعالم';
        $this->assertEquals(1, preg_match(\Com\Tecnick\Pdf\Font\Data\Pattern::ARABIC, $str));

        $str = 'שלום עולם';
        $this->assertEquals(0, preg_match(\Com\Tecnick\Pdf\Font\Data\Pattern::ARABIC, $str));
        $this->assertEquals(1, preg_match(\Com\Tecnick\Pdf\Font\Data\Pattern::RTL, $str));
    }
}
