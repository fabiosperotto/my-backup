<?php
namespace phpbu\App\Cli;

/**
 * Executable
 *
 * @package    phpbu
 * @subpackage Backup
 * @author     Sebastian Feldmann <sebastian@phpbu.de>
 * @copyright  Sebastian Feldmann <sebastian@phpbu.de>
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @link       http://phpbu.de/
 * @since      Class available since Release 2.1.0
 */
interface Executable
{
    /**
     * Executes the cli commands.
     *
     * @return \phpbu\App\Cli\Result
     * @throws \phpbu\App\Exception
     */
    public function run();

    /**
     * Return the command line to execute.
     *
     * @return string
     */
    public function getCommandLine();

    /**
     * Return the command with masked passwords or keys.
     *
     * @return string
     */
    public function getCommandLinePrintable();
}
