<?php
namespace phpbu\App\Backup;

use SplFileInfo;
use phpbu\App\Exception;

/**
 * File
 *
 * @package    phpbu
 * @subpackage Backup
 * @author     Sebastian Feldmann <sebastian@phpbu.de>
 * @copyright  Sebastian Feldmann <sebastian@phpbu.de>
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @link       http://phpbu.de/
 * @since      Class available since Release 1.0.0
 */
class File
{
    /**
     * FileInfo
     *
     * @var \SplFileInfo
     */
    protected $fileInfo;

    /**
     * Constructor
     *
     * @param SplFileInfo $fileInfo
     */
    public function __construct(SplFileInfo $fileInfo)
    {
        $this->fileInfo = $fileInfo;
    }

    /**
     * FileInfo getter.
     *
     * @return SplFileInfo
     */
    public function getFileInfo()
    {
        return $this->fileInfo;
    }

    /**
     * Return the filesize.
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->fileInfo->getSize();
    }

    /**
     * Return the filename.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->fileInfo->getFilename();
    }

    /**
     * Return the full path and filename.
     *
     * @return string
     */
    public function getPathname()
    {
        return $this->fileInfo->getPathname();
    }

    /**
     * Return the path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->fileInfo->getPath();
    }

    /**
     * Return last modified date as unix timestamp.
     *
     * @return integer
     */
    public function getMTime()
    {
        return $this->fileInfo->getMTime();
    }

    /**
     * Return whether the file is writable or not.
     *
     * @return boolean
     */
    public function isWritable()
    {
        return $this->fileInfo->isWritable();
    }

    /**
     * Deletes the file.
     *
     * @throws \phpbu\App\Exception
     */
    public function unlink()
    {
        $old = error_reporting(0);
        if (!unlink($this->fileInfo->getPathname())) {
            error_reporting($old);
            throw new Exception(sprintf('can\'t delete file: %s', $this->fileInfo->getPathname()));
        }
        error_reporting($old);
    }
}
