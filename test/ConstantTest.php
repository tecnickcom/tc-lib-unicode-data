<?php
/**
 * ConstantTest.php
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
 * Constant Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfFontData
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2015 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-font-data
 */
class ConstantTest extends \PHPUnit_Framework_TestCase
{
    public function testConstants()
    {
        $oClass = new \ReflectionClass('\Com\Tecnick\Pdf\Font\Data\Constant');
        $this->assertEquals(7, count($oClass->getConstants()));
    }
}
