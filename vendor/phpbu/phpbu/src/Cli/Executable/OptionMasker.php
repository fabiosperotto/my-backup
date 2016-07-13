<?php
namespace phpbu\App\Cli\Executable;

use phpbu\App\Cli\Process;
use phpbu\App\Util\Cli;

/**
 * Binary using credentials not safe for printing.
 *
 * @package    phpbu
 * @subpackage Backup
 * @author     Sebastian Feldmann <sebastian@phpbu.de>
 * @copyright  Sebastian Feldmann <sebastian@phpbu.de>
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @link       http://phpbu.de/
 * @since      Class available since Release 3.0.5
 */
trait OptionMasker
{
    /**
     * List of properties to mask for print safe output
     *
     * @var array
     */
    protected $maskCandidates = [];

    /**
     * Return the command line to execute.
     *
     * @return string
     */
    public abstract function getCommandLine();

    /**
     * Return the command with masked passwords or keys.
     *
     * @return string
     */
    public function getCommandLinePrintable()
    {
        $propertiesToMask = $this->getPropertiesToMask();
        // no candidates need masking
        if (0 === count($propertiesToMask)) {
            return $this->getCommandLine();
        }

        $masked = $this->mask($propertiesToMask);
        $cmd    = $this->getCommandLine();
        $this->restore($masked);

        return $cmd;
    }

    /**
     * Set potentially insecure properties.
     *
     * @param array $candidates
     */
    protected function setMaskCandidates(array $candidates)
    {
        $this->maskCandidates = $candidates;
    }

    /**
     * Mask given properties and return map with original values.
     *
     * @param  array $properties
     * @return array
     */
    private function mask(array $properties)
    {
        $masked = [];
        foreach ($properties as $p) {
            $masked[$p] = $this->$p;
            $this->$p   = '******';
        }
        return $masked;
    }

    /**
     * Restore masked properties.
     *
     * @param array $masked
     */
    private function restore(array $masked)
    {
        foreach ($masked as $p => $value) {
            $this->$p = $value;
        }
    }

    /**
     * Return list of properties that actually needs masking.
     *
     * @return array
     */
    private function getPropertiesToMask()
    {
        $properties = [];
        foreach ($this->maskCandidates as $p) {
            if ($this->$p !== null) {
                $properties[] = $p;
            }
        }
        return $properties;
    }
}
