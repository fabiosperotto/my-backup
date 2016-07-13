<?php
namespace phpbu\App\Util;

/**
 * Math utility test
 *
 * @package    phpbu
 * @subpackage tests
 * @author     Sebastian Feldmann <sebastian@phpbu.de>
 * @copyright  Sebastian Feldmann <sebastian@phpbu.de>
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @link       http://www.phpbu.de/
 * @since      Class available since Release 1.0.0
 */
class MathTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerPercentValues
     *
     * @param integer $whole
     * @param integer $part
     * @param integer $expected
     */
    public function testGetDiffInPercent($whole, $part, $expected)
    {
        $diff = Math::getDiffInPercent($whole, $part);
        $this->assertEquals(
            $expected,
            $diff,
            sprintf('diff in percent (%d,%d) should be %d', $whole, $part, $expected)
        );
    }

    /**
     * Data provider date testGetDiffInPercent.
     *
     * @return return array
     */
    public function providerPercentValues()
    {
        return array(
            array(100, 90, 10),
            array(100, 80, 20),
            array(100, 50, 50),
            array(80, 100, 20),
            array(60, 100, 40),
        );
    }
}
