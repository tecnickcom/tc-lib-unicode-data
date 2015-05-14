<?php
/**
 * EncodingTest.php
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
 * Encoding Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfFontData
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2015 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-font-data
 */
class EncodingTest extends \PHPUnit_Framework_TestCase
{
    public function testMap()
    {
        $this->assertEquals(22, count(\Com\Tecnick\Pdf\Font\Data\Encoding::$map));
        $this->assertEquals(256, count(\Com\Tecnick\Pdf\Font\Data\Encoding::$map['iso-8859-1']));
    }
}
