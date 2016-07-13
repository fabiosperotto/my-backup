<?php
namespace phpbu\App\Event\App;

use phpbu\App\Configuration;

/**
 * Start test
 *
 * @package    phpbu
 * @subpackage tests
 * @author     Sebastian Feldmann <sebastian@phpbu.de>
 * @copyright  Sebastian Feldmann <sebastian@phpbu.de>
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @link       http://www.phpbu.de/
 * @since      Class available since Release 2.0.0
 */
class StartTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests Start::getConfig
     */
    public function testGetConfig()
    {
        $conf = new Configuration('/tmp/foo.xml');
        $conf->setDebug(true);

        $start  = new Start($conf);
        $config = $start->getConfiguration();

        $this->assertEquals(true, $config->getDebug());
    }
}
