<?php
namespace phpbu\App;

/**
 * Configuration test
 *
 * @package    phpbu
 * @subpackage tests
 * @author     Sebastian Feldmann <sebastian@phpbu.de>
 * @copyright  Sebastian Feldmann <sebastian@phpbu.de>
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @link       http://www.phpbu.de/
 * @since      Class available since Release 1.1.5
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests Configuration::setVerbose
     */
    public function testVerbose()
    {
        $conf = new Configuration();
        $conf->setFilename('/tmp/foo.xml');
        $this->assertEquals(false, $conf->getVerbose());
        $conf->setVerbose(true);
        $this->assertEquals(true, $conf->getVerbose());
    }

    /**
     * Tests Configuration::setColors
     */
    public function testColors()
    {
        $conf = new Configuration();
        $conf->setFilename('/tmp/foo.xml');
        $this->assertEquals(false, $conf->getColors());
        $conf->setColors(true);
        $this->assertEquals(true, $conf->getColors());
    }

    /**
     * Tests Configuration::setDebug
     */
    public function testDebug()
    {
        $conf = new Configuration();
        $conf->setFilename('/tmp/foo.xml');
        $this->assertEquals(false, $conf->getDebug());
        $conf->setDebug(true);
        $this->assertEquals(true, $conf->getDebug());
    }

    /**
     * Tests Configuration::setSimulate
     */
    public function testSimulate()
    {
        $conf = new Configuration();
        $conf->setFilename('/tmp/foo.xml');
        $this->assertEquals(false, $conf->isSimulation());
        $conf->setSimulate(true);
        $this->assertEquals(true, $conf->isSimulation());
    }

    /**
     * Tests Configuration::setBootstrap
     */
    public function testBootstrap()
    {
        $conf = new Configuration();
        $conf->setFilename('/tmp/foo.xml');
        $this->assertEquals(null, $conf->getBootstrap());
        $conf->setBootstrap('file.php');
        $this->assertEquals('file.php', $conf->getBootstrap());
    }

    /**
     * Tests Configuration::addIncludePath
     */
    public function testIncludePath()
    {
        $conf = new Configuration();
        $conf->setFilename('/tmp/foo.xml');
        $this->assertEquals(array(), $conf->getIncludePaths());
        $conf->addIncludePath('/tmp');
        $this->assertEquals(1, count($conf->getIncludePaths()));
    }

    /**
     * Tests Configuration::addIniSettings
     */
    public function testIniSettings()
    {
        $conf = new Configuration();
        $conf->setFilename('/tmp/foo.xml');
        $this->assertEquals(array(), $conf->getIniSettings());
        $conf->addIniSetting('max_execution_time', 0);
        $this->assertEquals(1, count($conf->getIniSettings()));
    }

    /**
     * Tests Configuration::getWorkingDirectory
     */
    public function testGetWorkingDirectory()
    {
        $conf = new Configuration();
        $conf->setFilename('/tmp/foo.xml');
        $this->assertEquals('/tmp/foo.xml', $conf->getFilename());
        $this->assertEquals('/tmp', $conf->getWorkingDirectory());
    }

    /**
     * Tests Configuration::setWorkingDirectory
     */
    public function testSetWorkingDirectory()
    {
        $conf = new Configuration();
        $conf->setWorkingDirectory('/tmp');
        $this->assertEquals('/tmp', $conf->getWorkingDirectory());
    }

    /**
     * Tests Configuration::addBackup
     */
    public function testBackup()
    {
        $conf = new Configuration();
        $conf->setFilename('/tmp/foo.xml');
        $backup = new Configuration\Backup('backup', true);
        $this->assertEquals(array(), $conf->getBackups());
        $conf->addBackup($backup);
        $this->assertEquals(1, count($conf->getBackups()));
    }

    /**
     * Tests Configuration::addLogger
     */
    public function testLoggerConfiguration()
    {
        $conf = new Configuration();
        $conf->setFilename('/tmp/foo.xml');
        $logger = new Configuration\Logger('json', array());
        $this->assertEquals(array(), $conf->getLoggers());
        $conf->addLogger($logger);
        $this->assertEquals(1, count($conf->getLoggers()));
    }

    /**
     * Tests Configuration::addLogger
     */
    public function testLoggerListener()
    {
        $conf = new Configuration();
        $conf->setFilename('/tmp/foo.xml');
        $logger = new Result\PrinterCli(false, false, false);
        $this->assertEquals(array(), $conf->getLoggers());
        $conf->addLogger($logger);
        $this->assertEquals(1, count($conf->getLoggers()));
    }

    /**
     * Tests Configuration::addLogger
     *
     * @expectedException \phpbu\App\Exception
     */
    public function testLoggerInvalid()
    {
        $conf = new Configuration();
        $conf->setFilename('/tmp/foo.xml');
        $conf->addLogger('no valid logger at all');
    }
}
