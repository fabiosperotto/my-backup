<?php
namespace phpbu\App\Cli\Executable;

use phpbu\App\Cli\Cmd;
use phpbu\App\Cli\Executable;
use phpbu\App\Cli\Process;
use phpbu\App\Exception;

/**
 * OpenSSL executable class.
 *
 * @package    phpbu
 * @subpackage Backup
 * @author     Sebastian Feldmann <sebastian@phpbu.de>
 * @copyright  Sebastian Feldmann <sebastian@phpbu.de>
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @link       http://phpbu.de/
 * @since      Class available since Release 2.1.6
 */
class OpenSSL extends Abstraction implements Executable
{
    use OptionMasker;

    /**
     * Encryption modes
     */
    const MODE_CERT = 'smime';
    const MODE_PASS = 'enc';

    /**
     * Use password or key
     *
     * @var string
     */
    private $mode;

    /**
     * File to encrypt
     *
     * @var string
     */
    private $sourceFile;

    /**
     * SSL Cert file
     *
     * @var string
     */
    private $certFile;

    /**
     * Password
     *
     * @var string
     */
    private $password;

    /**
     * Algorithm to use
     *
     * @var string
     */
    private $algorithm;

    /**
     * Use base64 encoding
     *
     * @var boolean
     */
    private $base64;

    /**
     * Path to the encrypted file
     *
     * @var string
     */
    private $targetFile;

    /**
     * List of available algorithms
     *
     * @var array
     */
    private $availableAlgorithms = array(
        'enc'   => array(
            'aes-128-cbc'  => true,
            'aes-128-ecb'  => true,
            'aes-192-cbc'  => true,
            'aes-192-ecb'  => true,
            'aes-256-cbc'  => true,
            'aes-256-ecb'  => true,
            'base64'       => true,
            'bf'           => true,
            'bf-cbc'       => true,
            'bf-cfb'       => true,
            'bf-ecb'       => true,
            'bf-ofb'       => true,
            'cast'         => true,
            'cast-cbc'     => true,
            'cast5-cbc'    => true,
            'cast5-cfb'    => true,
            'cast5-ecb'    => true,
            'cast5-ofb'    => true,
            'des'          => true,
            'des-cbc'      => true,
            'des-cfb'      => true,
            'des-ecb'      => true,
            'des-ede'      => true,
            'des-ede-cbc'  => true,
            'des-ede-cfb'  => true,
            'des-ede-ofb'  => true,
            'des-ede3'     => true,
            'des-ede3-cbc' => true,
            'des-ede3-cfb' => true,
            'des-ede3-ofb' => true,
            'des-ofb'      => true,
            'des3'         => true,
            'desx'         => true,
            'rc2'          => true,
            'rc2-40-cbc'   => true,
            'rc2-64-cbc'   => true,
            'rc2-cbc'      => true,
            'rc2-cfb'      => true,
            'rc2-ecb'      => true,
            'rc2-ofb'      => true,
            'rc4'          => true,
            'rc4-40'       => true,
            'rc5'          => true,
            'rc5-cbc'      => true,
            'rc5-cfb'      => true,
            'rc5-ecb'      => true,
            'rc5-ofb'      => true,
            'seed'         => true,
            'seed-cbc'     => true,
            'seed-cfb'     => true,
            'seed-ecb'     => true,
            'seed-ofb'     => true,
        ),
        'smime' => array(
            'des3'    => true,
            'des'     => true,
            'seed'    => true,
            'rc2-40'  => true,
            'rc2-64'  => true,
            'rc2-128' => true,
            'aes128'  => true,
            'aes192'  => true,
            'aes256'  => true,
        )
    );

    /**
     * Keep the not encrypted file
     *
     * @var boolean
     */
    private $deleteUncrypted = true;

    /**
     * Constructor.
     *
     * @param string $path
     */
    public function __construct($path = null)
    {
        $this->setup('openssl', $path);
        $this->setMaskCandidates(['password']);
    }

    /**
     * Encrypt a file.
     *
     * @param  string $file
     * @return \phpbu\App\Cli\Executable\OpenSSL
     * @throws Exception
     */
    public function encryptFile($file)
    {
        $this->sourceFile = $file;
        $this->targetFile = $file . '.enc';
        return $this;
    }

    /**
     * Delete the uncrypted data.
     *
     * @param  boolean $bool
     * @return \phpbu\App\Cli\Executable\OpenSSL
     */
    public function deleteUncrypted($bool)
    {
        $this->deleteUncrypted = $bool;
        return $this;
    }

    /**
     * Password to use for encryption.
     *
     * @param  string $password
     * @return \phpbu\App\Cli\Executable\OpenSSL
     * @throws \phpbu\App\Exception
     */
    public function usePassword($password)
    {
        if (self::MODE_CERT === $this->mode) {
            throw new Exception('Cert file already set');
        }
        $this->mode = self::MODE_PASS;
        $this->password = $password;
        return $this;
    }

    /**
     * Set algorithm to use.
     *
     * @param  string $algorithm
     * @return \phpbu\App\Cli\Executable\OpenSSL
     * @throws \phpbu\App\Exception
     */
    public function useAlgorithm($algorithm)
    {
        if (null === $this->mode) {
            throw new Exception('choose mode first, password or cert');
        }
        if (!isset($this->availableAlgorithms[$this->mode][$algorithm])) {
            throw new Exception('invalid algorithm');
        }
        $this->algorithm = $algorithm;
        return $this;
    }

    /**
     * Use base64 encoding
     *
     * @param boolean $encode
     * @return \phpbu\App\Cli\Executable\OpenSSL
     */
    public function encodeBase64($encode)
    {
        $this->base64 = $encode;
        return $this;
    }

    /**
     * Public key to use.
     *
     * @param  string $file
     * @return \phpbu\App\Cli\Executable\OpenSSL
     * @throws \phpbu\App\Exception
     */
    public function useSSLCert($file)
    {
        if (self::MODE_PASS === $this->mode) {
            throw new Exception('Password already set');
        }
        $this->mode = self::MODE_CERT;
        $this->certFile = $file;
        return $this;
    }

    /**
     * OpenSSL Process generator.
     *
     * @return \phpbu\App\Cli\Process
     * @throws \phpbu\App\Exception
     */
    protected function createProcess()
    {
        if (empty($this->sourceFile)) {
            throw new Exception('file is missing');
        }
        if (empty($this->mode)) {
            throw new Exception('one of \'password\' or \'cert\' is mandatory');
        }
        if (empty($this->algorithm)) {
            throw new Exception('no algorithm specified');
        }

        $process = new Process();
        $cmd     = new Cmd($this->binary);

        $process->addCommand($cmd);

        $this->setOptions($cmd);
        $this->addDeleteCommand($process);

        return $process;
    }

    /**
     * Set the openssl command line options
     *
     * @param \phpbu\App\Cli\Cmd $cmd
     */
    protected function setOptions(Cmd $cmd)
    {
        if ($this->mode == self::MODE_CERT) {
            $this->setCertOptions($cmd);
        } else {
            $this->setPasswordOptions($cmd);
        }
    }

    /**
     * Set command line options for SSL cert encryption.
     *
     * @param \phpbu\App\Cli\Cmd $cmd
     */
    protected function setCertOptions(Cmd $cmd)
    {
        $cmd->addOption('smime');
        $cmd->addOption('-encrypt');
        $cmd->addOption('-' . $this->algorithm);
        $cmd->addOption('-binary');
        $cmd->addOption('-in', $this->sourceFile, ' ');
        $cmd->addOption('-out', $this->targetFile, ' ');
        $cmd->addOption('-outform DER');
        $cmd->addArgument($this->certFile);
    }

    /**
     * Set command line options for password encryption
     *
     * @param \phpbu\App\Cli\Cmd $cmd
     */
    protected function setPasswordOptions(Cmd $cmd)
    {
        $password = 'pass:' . $this->password;

        $cmd->addOption('enc');
        $cmd->addOption('-e');
        $cmd->addOptionIfNotEmpty('-a', $this->base64, false);
        $cmd->addOption('-' . $this->algorithm);
        $cmd->addOption('-pass', $password, ' ');
        $cmd->addOption('-in', $this->sourceFile, ' ');
        $cmd->addOption('-out', $this->targetFile, ' ');
    }

    /**
     * Add the 'rm' command to remove the uncrypted file.
     *
     * @param \phpbu\App\Cli\Process $process
     */
    protected function addDeleteCommand(Process $process)
    {
        if ($this->deleteUncrypted) {
            $cmd = new Cmd('rm');
            $cmd->addArgument($this->sourceFile);
            $process->addCommand($cmd);
        }
    }
}
