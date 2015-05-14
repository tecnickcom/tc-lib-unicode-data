<?php
/**
 * IdentityTest.php
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
 * Identity Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfFontData
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2015 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-font-data
 */
class IdentityTest extends \PHPUnit_Framework_TestCase
{
    public function testConstants()
    {
        $this->assertEquals('a3f30375025e8570745ff463479522b6', md5(\Com\Tecnick\Pdf\Font\Data\Identity::CIDHMAP));
    }
}
