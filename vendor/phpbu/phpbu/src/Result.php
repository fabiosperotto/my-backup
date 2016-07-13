<?php
namespace phpbu\App;

use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Runner result.
 *
 * Heavily 'inspired' by Sebastian Bergmann's phpunit PHPUnit_Framework_TestResult.
 *
 * @package    phpbu
 * @subpackage App
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @author     Sebastian Feldmann <sebastian@phpbu.de>
 * @copyright  Sebastian Feldmann <sebastian@phpbu.de>
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @link       http://phpbu.de/
 * @since      Class available since Release 1.0.0
 */
class Result
{
    /**
     * Symfony EventDispatcher.
     *
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * List of executed Backups.
     *
     * @var array<\phpbu\App\Result\Backup>
     */
    protected $backups = [];

    /**
     * Currently running backup.
     *
     * @var \phpbu\App\Result\Backup
     */
    protected $backupActive;

    /**
     * List of errors.
     *
     * @var array
     */
    protected $errors = [];

    /**
     * @var integer
     */
    protected $backupsFailed = 0;

    /**
     * @var integer
     */
    protected $checksFailed = 0;

    /**
     * @var integer
     */
    protected $cryptsSkipped = 0;

    /**
     * @var integer
     */
    protected $cryptsFailed = 0;

    /**
     * @var integer
     */
    protected $syncsSkipped = 0;

    /**
     * @var integer
     */
    protected $syncsFailed = 0;

    /**
     * @var integer
     */
    protected $cleanupsSkipped = 0;

    /**
     * @var integer
     */
    protected $cleanupsFailed = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->eventDispatcher = new EventDispatcher();
    }

    /**
     * Registers a Listener/Subscriber.
     *
     * @param \phpbu\App\Listener
     */
    public function addListener(Listener $subscriber)
    {
        $this->eventDispatcher->addSubscriber($subscriber);
    }

    /**
     * No errors at all?
     *
     * @return boolean
     */
    public function allOk()
    {
        return $this->wasSuccessful() && $this->noneSkipped() && $this->noneFailed();
    }

    /**
     * Backup without errors, but some tasks where skipped or failed.
     *
     * @return boolean
     */
    public function backupOkButSkipsOrFails()
    {
        return $this->wasSuccessful() && (!$this->noneSkipped() || !$this->noneFailed());
    }

    /**
     * Backup without errors?
     *
     * @return boolean
     */
    public function wasSuccessful()
    {
        return $this->backupsFailed === 0;
    }

    /**
     * Nothing skipped?
     *
     * @return boolean
     */
    public function noneSkipped()
    {
        return $this->cryptsSkipped + $this->syncsSkipped + $this->cleanupsSkipped === 0;
    }

    /**
     * Nothing failed?
     *
     * @return boolean
     */
    public function noneFailed()
    {
        return $this->checksFailed + $this->cryptsFailed + $this->syncsFailed + $this->cleanupsFailed === 0;
    }

    /**
     * Add Exception to error list
     *
     * @param \Exception $e
     */
    public function addError(\Exception $e)
    {
        $this->errors[] = $e;
    }

    /**
     * Return current error count.
     *
     * @return integer
     */
    public function errorCount()
    {
        return count($this->errors);
    }

    /**
     * Return list of errors.
     *
     * @return array<Exception>
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Return list of executed backups.
     *
     * @return array<\phpbu\App\Configuration\Backup>
     */
    public function getBackups()
    {
        return $this->backups;
    }

    /**
     * phpbu start event.
     *
     * @param \phpbu\App\Configuration $configuration
     */
    public function phpbuStart(Configuration $configuration)
    {
        $event = new Event\App\Start($configuration);
        $this->eventDispatcher->dispatch(Event\App\Start::NAME, $event);
    }

    /**
     * phpbu end event.
     */
    public function phpbuEnd()
    {
        $event = new Event\App\End($this);
        $this->eventDispatcher->dispatch(Event\App\End::NAME, $event);
    }

    /**
     * Backup start event.
     *
     * @param \phpbu\App\Configuration\Backup $backup
     */
    public function backupStart(Configuration\Backup $backup)
    {
        $this->backupActive = new Result\Backup($backup->getName());
        $this->backups[]    = $this->backupActive;

        $event = new Event\Backup\Start($backup);
        $this->eventDispatcher->dispatch(Event\Backup\Start::NAME, $event);
    }

    /**
     * Backup failed event.
     *
     * @param \phpbu\App\Configuration\Backup $backup
     */
    public function backupFailed(Configuration\Backup $backup)
    {
        $this->backupsFailed++;
        $this->backupActive->fail();

        $event = new Event\Backup\Failed($backup);
        $this->eventDispatcher->dispatch(Event\Backup\Failed::NAME, $event);
    }

    /**
     * Return amount of failed backups
     *
     * @return integer
     */
    public function backupsFailedCount()
    {
        return $this->backupsFailed;
    }

    /**
     * Backup end event.
     *
     * @param \phpbu\App\Configuration\Backup $backup
     */
    public function backupEnd(Configuration\Backup $backup)
    {
        $event = new Event\Backup\End($backup);
        $this->eventDispatcher->dispatch(Event\Backup\End::NAME, $event);
    }

    /**
     * Check start event.
     *
     * @param \phpbu\App\Configuration\Backup\Check $check
     */
    public function checkStart(Configuration\Backup\Check $check)
    {
        $this->backupActive->checkAdd($check);

        $event = new Event\Check\Start($check);
        $this->eventDispatcher->dispatch(Event\Check\Start::NAME, $event);
    }

    /**
     * Check failed event.
     *
     * @param \phpbu\App\Configuration\Backup\Check $check
     */
    public function checkFailed(Configuration\Backup\Check $check)
    {
        // if this is the first check that fails
        if ($this->backupActive->wasSuccessful()) {
            $this->backupsFailed++;
        }

        $this->checksFailed++;
        $this->backupActive->fail();
        $this->backupActive->checkFailed($check);

        $event = new Event\Check\Failed($check);
        $this->eventDispatcher->dispatch(Event\Check\Failed::NAME, $event);
    }

    /**
     * Return amount of failed checks.
     *
     * @return integer
     */
    public function checksFailedCount()
    {
        return $this->checksFailed;
    }

    /**
     * Check end event.
     *
     * @param \phpbu\App\Configuration\Backup\Check $check
     */
    public function checkEnd(Configuration\Backup\Check $check)
    {
        $event = new Event\Check\End($check);
        $this->eventDispatcher->dispatch(Event\Check\End::NAME, $event);
    }

    /**
     * Crypt start event.
     *
     * @param \phpbu\App\Configuration\Backup\Crypt $crypt
     */
    public function cryptStart(Configuration\Backup\Crypt $crypt)
    {
        $this->backupActive->cryptAdd($crypt);

        $event = new Event\Crypt\Start($crypt);
        $this->eventDispatcher->dispatch(Event\Crypt\Start::NAME, $event);
    }

    /**
     * Crypt skipped event.
     *
     * @param \phpbu\App\Configuration\Backup\Crypt $crypt
     */
    public function cryptSkipped(Configuration\Backup\Crypt $crypt)
    {
        $this->cryptsSkipped++;
        $this->backupActive->cryptSkipped($crypt);

        $event = new Event\Crypt\Skipped($crypt);
        $this->eventDispatcher->dispatch(Event\Crypt\Skipped::NAME, $event);
    }

    /**
     * Return amount of skipped crypts.
     *
     * @return integer
     */
    public function cryptsSkippedCount()
    {
        return $this->cryptsSkipped;
    }


    /**
     * Crypt failed event.
     *
     * @param \phpbu\App\Configuration\Backup\Crypt $crypt
     */
    public function cryptFailed(Configuration\Backup\Crypt $crypt)
    {
        $this->cryptsFailed++;
        $this->backupActive->fail();
        $this->backupActive->cryptFailed($crypt);

        $event = new Event\Crypt\Failed($crypt);
        $this->eventDispatcher->dispatch(Event\Crypt\Failed::NAME, $event);
    }

    /**
     * Return amount of failed crypts.
     *
     * @return integer
     */
    public function cryptsFailedCount()
    {
        return $this->cryptsFailed;
    }

    /**
     * Crypt end event.
     *
     * @param \phpbu\App\Configuration\Backup\Crypt $crypt
     */
    public function cryptEnd(Configuration\Backup\Crypt $crypt)
    {
        $event = new Event\Crypt\End($crypt);
        $this->eventDispatcher->dispatch(Event\Crypt\End::NAME, $event);
    }

    /**
     * Sync start event.
     *
     * @param \phpbu\App\Configuration\Backup\Sync $sync
     */
    public function syncStart(Configuration\Backup\Sync $sync)
    {
        $this->backupActive->syncAdd($sync);

        $event = new Event\Sync\Start($sync);
        $this->eventDispatcher->dispatch(Event\Sync\Start::NAME, $event);
    }

    /**
     * Sync skipped event.
     *
     * @param \phpbu\App\Configuration\Backup\Sync $sync
     */
    public function syncSkipped(Configuration\Backup\Sync $sync)
    {
        $this->syncsSkipped++;
        $this->backupActive->syncSkipped($sync);

        $event = new Event\Sync\Skipped($sync);
        $this->eventDispatcher->dispatch(Event\Sync\Skipped::NAME, $event);
    }

    /**
     * Return amount of skipped syncs.
     *
     * @return integer
     */
    public function syncsSkippedCount()
    {
        return $this->syncsSkipped;
    }

    /**
     * Sync failed event.
     *
     * @param \phpbu\App\Configuration\Backup\Sync $sync
     */
    public function syncFailed(Configuration\Backup\Sync $sync)
    {
        $this->syncsFailed++;
        $this->backupActive->syncFailed($sync);

        $event = new Event\Sync\Failed($sync);
        $this->eventDispatcher->dispatch(Event\Sync\Failed::NAME, $event);
    }

    /**
     * Return amount of failed syncs.
     *
     * @return integer
     */
    public function syncsFailedCount()
    {
        return $this->syncsFailed;
    }

    /**
     * Sync end event.
     *
     * @param \phpbu\App\Configuration\Backup\Sync $sync
     */
    public function syncEnd(Configuration\Backup\Sync $sync)
    {
        $event = new Event\Sync\End($sync);
        $this->eventDispatcher->dispatch(Event\Sync\End::NAME, $event);
    }

    /**
     * Cleanup start event.
     *
     * @param \phpbu\App\Configuration\Backup\Cleanup $cleanup
     */
    public function cleanupStart(Configuration\Backup\Cleanup $cleanup)
    {
        $this->backupActive->cleanupAdd($cleanup);

        $event = new Event\Cleanup\Start($cleanup);
        $this->eventDispatcher->dispatch(Event\Cleanup\Start::NAME, $event);
    }

    /**
     * Cleanup skipped event.
     *
     * @param \phpbu\App\Configuration\Backup\Cleanup $cleanup
     */
    public function cleanupSkipped(Configuration\Backup\Cleanup $cleanup)
    {
        $this->cleanupsSkipped++;
        $this->backupActive->cleanupSkipped($cleanup);

        $event = new Event\Cleanup\Skipped($cleanup);
        $this->eventDispatcher->dispatch(Event\Cleanup\Skipped::NAME, $event);
    }

    /**
     * Return amount of skipped cleanups.
     *
     * @return integer
     */
    public function cleanupsSkippedCount()
    {
        return $this->cleanupsSkipped;
    }

    /**
     * Cleanup failed event.
     *
     * @param \phpbu\App\Configuration\Backup\Cleanup $cleanup
     */
    public function cleanupFailed(Configuration\Backup\Cleanup $cleanup)
    {
        $this->cleanupsFailed++;
        $this->backupActive->cleanupFailed($cleanup);

        $event = new Event\Cleanup\Failed($cleanup);
        $this->eventDispatcher->dispatch(Event\Cleanup\Failed::NAME, $event);
    }

    /**
     * Return amount of failed cleanups.
     *
     * @return integer
     */
    public function cleanupsFailedCount()
    {
        return $this->cleanupsFailed;
    }

    /**
     * Cleanup end event.
     *
     * @param \phpbu\App\Configuration\Backup\Cleanup $cleanup
     */
    public function cleanupEnd(Configuration\Backup\Cleanup $cleanup)
    {
        $event = new Event\Cleanup\End($cleanup);
        $this->eventDispatcher->dispatch(Event\Cleanup\End::NAME, $event);
    }

    /**
     * Debug.
     *
     * @param string $msg
     */
    public function debug($msg)
    {
        $event = new Event\Debug($msg);
        $this->eventDispatcher->dispatch(Event\Debug::NAME, $event);
    }
}
