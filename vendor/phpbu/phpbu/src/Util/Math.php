<?php
namespace phpbu\App\Util;

/**
 * Math Util class.
 *
 * @package    phpbu
 * @subpackage Util
 * @author     Sebastian Feldmann <sebastian@phpbu.de>
 * @copyright  Sebastian Feldmann <sebastian@phpbu.de>
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @link       http://phpbu.de/
 * @since      Class available since Release 1.0.0
 */
class Math
{
    /**
     * Calculates the difference of two values in percent
     *
     * @param  integer $a
     * @param  integer $b
     * @return integer
     */
    public static function getDiffInPercent($a, $b)
    {
        if ($a > $b) {
            $whole = $a;
            $part  = $b;
        } else {
            $whole = $b;
            $part  = $a;
        }
        return 100 - ceil(($part / $whole) * 100);
    }
}
