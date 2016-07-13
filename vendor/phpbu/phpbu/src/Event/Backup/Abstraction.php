<?php
namespace phpbu\App\Event\Backup;

use phpbu\App\Configuration\Backup;
use phpbu\App\Event\Action;

/**
 * Backup event base class.
 *
 * @package    phpbu
 * @subpackage App
 * @author     Sebastian Feldmann <sebastian@phpbu.de>
 * @copyright  Sebastian Feldmann <sebastian@phpbu.de>
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @link       http://phpbu.de/
 * @since      Class available since Release 2.0.0
 */
abstract class Abstraction extends Action
{
    /**
     * Constructor.
     *
     * @param \phpbu\App\Configuration\Backup $backup
     */
    public function __construct(Backup $backup)
    {
        $this->configuration = $backup;
    }
}
