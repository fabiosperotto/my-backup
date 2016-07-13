<?php
namespace phpbu\App\Log;

use phpbu\App\Exception;
use phpbu\App\Event;
use phpbu\App\Listener;
use phpbu\App\Result;
use phpbu\App\Log\MailTemplate as TPL;
use phpbu\App\Util\Arr;
use phpbu\App\Util\Str;
use PHP_Timer;
use Swift_Mailer;
use Swift_Message;

/**
 * Mail Logger
 *
 * @package    phpbu
 * @subpackage Log
 * @author     Sebastian Feldmann <sebastian@phpbu.de>
 * @copyright  Sebastian Feldmann <sebastian@phpbu.de>
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @link       http://phpbu.de/
 * @since      Class available since Release 1.0.0
 */
class Mail implements Listener, Logger
{
    /**
     * Mailer instance
     *
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * Mail subject
     *
     * @var string
     */
    protected $subject;

    /**
     * From email address
     *
     * @var string
     */
    protected $senderMail;

    /**
     * From name
     *
     * @var string
     */
    protected $senderName;

    /**
     * Transport type [mail|smtp|null]
     *
     * @var string
     */
    protected $transportType;

    /**
     * List of mail recipients
     *
     * @var array<string>
     */
    protected $recipients = array();

    /**
     * Amount of executed backups
     *
     * @var integer
     */
    private $numBackups = 0;

    /**
     * Amount of executed checks
     *
     * @var integer
     */
    private $numChecks = 0;

    /**
     * Amount of executed Syncs
     *
     * @var integer
     */
    private $numSyncs = 0;

    /**
     * Amount of executed Crypts
     *
     * @var integer
     */
    private $numCrypts = 0;

    /**
     * Amount of executed Cleanups
     *
     * @var integer
     */
    private $numCleanups = 0;

    /**
     * Send mail only if there was an error
     *
     * @var boolean
     */
    private $sendOnlyOnError;

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return array(
            'phpbu.backup_start'  => 'onBackupStart',
            'phpbu.check_start'   => 'onCheckStart',
            'phpbu.crypt_start'   => 'onCryptStart',
            'phpbu.sync_start'    => 'onSyncStart',
            'phpbu.cleanup_start' => 'onCleanupStart',
            'phpbu.app_end'       => 'onPhpbuEnd',
        );
    }

    /**
     * Setup the Logger.
     *
     * @see    \phpbu\Log\Logger::setup
     * @param  array $options
     * @throws \phpbu\App\Exception
     */
    public function setup(array $options)
    {
        if (empty($options['recipients'])) {
            throw new Exception('no recipients given');
        }
        $mails                 = $options['recipients'];
        $server                = gethostname();
        $this->sendOnlyOnError = Str::toBoolean(Arr::getValue($options, 'sendOnlyOnError'), false);
        $this->subject         = Arr::getValue($options, 'subject', 'PHPBU backup report from ' . $server);
        $this->senderMail      = Arr::getValue($options, 'sender.mail', 'phpbu@' . $server);
        $this->senderName      = Arr::getValue($options, 'sender.name');
        $this->transportType   = Arr::getValue($options, 'transport', 'mail');
        $this->recipients      = array_map('trim', explode(';', $mails));

        // create transport an mailer
        $transport    = $this->createTransport($this->transportType, $options);
        $this->mailer = Swift_Mailer::newInstance($transport);
    }

    /**
     * Handle the phpbu end event.
     *
     * @param  \phpbu\App\Event\App\End $event
     * @throws \phpbu\App\Exception
     */
    public function onPhpbuEnd(Event\App\End $event)
    {
        $result  = $event->getResult();
        $allGood = $result->allOk();

        if (!$this->sendOnlyOnError || !$allGood) {
            $header  = $this->getHeaderHtml();
            $status  = $this->getStatusHtml($result);
            $errors  = $this->getErrorHtml($result);
            $info    = $this->getInfoHtml($result);
            $footer  = $this->getFooterHtml();
            $body    = '<html><body '. TPL::getSnippet('sBody') . '>'
                     . $header
                     . $status
                     . $errors
                     . $info
                     . $footer
                     . '</body></html>';
            $sent    = null;

            try {
                /** @var \Swift_Message $message */
                $message = Swift_Message::newInstance();
                $message->setSubject($this->subject)
                        ->setFrom($this->senderMail, $this->senderName)
                        ->setTo($this->recipients)
                        ->setBody($body, 'text/html');

                $sent = $this->mailer->send($message);
            } catch (\Exception $e) {
                throw new Exception($e->getMessage());
            }
            if (!$sent) {
                throw new Exception('mail could not be sent');
            }
        }
    }

    /**
     * Backup start event.
     *
     * @param \phpbu\App\Event\Backup\Start $event
     */
    public function onBackupStart(Event\Backup\Start $event)
    {
        $this->numBackups++;
    }

    /**
     * Check start event.
     *
     * @param \phpbu\App\Event\Check\Start $event
     */
    public function onCheckStart(Event\Check\Start $event)
    {
        $this->numChecks++;
    }

    /**
     * Crypt start event.
     *
     * @param \phpbu\App\Event\Crypt\Start $event
     */
    public function onCryptStart(Event\Crypt\Start $event)
    {
        $this->numCrypts++;
    }

    /**
     * Sync start event.
     *
     * @param \phpbu\App\Event\Sync\Start $event
     */
    public function onSyncStart(Event\Sync\Start $event)
    {
        $this->numSyncs++;
    }

    /**
     * Cleanup start event.
     *
     * @param \phpbu\App\Event\Cleanup\Start $event
     */
    public function onCleanupStart(Event\Cleanup\Start $event)
    {
        $this->numCleanups++;
    }

    /**
     * Create a Swift_Mailer_Transport.
     *
     * @param  string $type
     * @param  array  $options
     * @throws \phpbu\App\Exception
     * @return \Swift_Transport
     */
    protected function createTransport($type, array $options)
    {
        switch ($type) {
            // null transport, don't send any mails
            case 'null':
                 /* @var $transport \Swift_NullTransport */
                $transport = \Swift_NullTransport::newInstance();
                break;

            case 'mail':
                /* @var $transport \Swift_MailTransport */
                $transport = \Swift_MailTransport::newInstance();
                break;

            case 'smtp':
                $transport = $this->getSmtpTransport($options);
                break;

            case 'sendmail':
                $transport = $this->getSendmailTransport($options);
                break;

            // UPS! no transport given
            default:
                throw new Exception(sprintf('mail transport not supported: \'%s\'', $type));
        }
        return $transport;
    }

    /**
     * Create Swift Smtp Transport.
     *
     * @param  array $options
     * @return \Swift_SmtpTransport
     * @throws \phpbu\App\Exception
     */
    protected function getSmtpTransport(array $options)
    {
        if (!isset($options['smtp.host'])) {
            throw new Exception('option \'smtp.host\' ist missing');
        }
        $host       = $options['smtp.host'];
        $port       = Arr::getValue($options, 'smtp.port', 25);
        $username   = Arr::getValue($options, 'smtp.username');
        $password   = Arr::getValue($options, 'smtp.password');
        $encryption = Arr::getValue($options, 'smtp.encryption');

        /* @var $transport \Swift_SmtpTransport */
        $transport = \Swift_SmtpTransport::newInstance($host, $port);

        if ($username && $password) {
            $transport->setUsername($username)
                      ->setPassword($password);
        }
        if ($encryption) {
            $transport->setEncryption($encryption);
        }
        return $transport;
    }

    /**
     * Create a Swift Sendmail Transport.
     *
     * @param  array $options
     * @return \Swift_SendmailTransport
     * @throws \phpbu\App\Exception
     */
    protected function getSendmailTransport(array $options)
    {
        if (!isset($options['sendmail.path'])) {
            throw new Exception('option \'sendmail.path\' ist missing');
        }
        $path    = $options['sendmail.path'];
        $options = isset($options['sendmail.options']) ? ' ' . $options['sendmail.options'] : '';

        /* @var $transport \Swift_SendmailTransport */
        $transport = \Swift_SendmailTransport::newInstance($path . $options);

        return $transport;
    }

    /**
     * Return mail header html
     *
     * @return string
     */
    protected function getHeaderHtml()
    {
        return '<table ' . TPL::getSnippet('sTableContent') . '><tr><td ' . TPL::getSnippet('sTableContentCol') . '>' .
                 '<table ' . TPL::getSnippet('sTableHeader') . '><tr><td>PHPBU - backup report</td></tr></table>';
    }

    /**
     * Return mail status html
     *
     * @param  \phpbu\App\Result $result
     * @return string
     */
    protected function getStatusHtml(Result $result)
    {
        if (count($result->getBackups()) === 0) {
            $color  = TPL::getSnippet('cStatusWARN');
            $status = 'WARNING';
        } elseif ($result->allOk()) {
            $color  = TPL::getSnippet('cStatusOK');
            $status = 'OK';
        } elseif ($result->backupOkButSkipsOrFails()) {
            $color  = TPL::getSnippet('cStatusWARN');
            $status = 'WARNING';
        } else {
            $color  = TPL::getSnippet('cStatusFAIL');
            $status = 'FAILURE';
        }
        $info = sprintf(
            '(%d %s, %d %s, %d %s, %d %s, %d %s)',
            count($result->getBackups()),
            Str::appendPluralS('backup', count($result->getBackups())),
            $this->numChecks,
            Str::appendPluralS('check', $this->numChecks),
            $this->numCrypts,
            Str::appendPluralS('crypt', $this->numCrypts),
            $this->numSyncs,
            Str::appendPluralS('sync', $this->numSyncs),
            $this->numCleanups,
            Str::appendPluralS('cleanup', $this->numCleanups)
        );
        $html = '<table ' . sprintf(TPL::getSnippet('sTableStatus'), $color) .'>' .
                 '<tr><td>' .
                  '<span ' . TPL::getSnippet('sTableStatusText') . '>' . date('Y-m-d H:i') . '</span>' .
                  '<h1 ' . TPL::getSnippet('sTableStatusHead') . '>' . $status . '</h1>' .
                  '<span ' . TPL::getSnippet('sTableStatusText') . '>' . $info . '</span>' .
                 '</td></tr>' .
                '</table>';

        return $html;
    }

    /**
     * Get error information.
     *
     * @param  \phpbu\App\Result $result
     * @return string
     */
    protected function getErrorHtml(Result $result)
    {
        $html   = '';
        $errors = $result->getErrors();
        if (count($errors)) {
            $html .= '<table ' . TPL::getSnippet('sTableError') . '>';
            /* @var $e Exception */
            foreach ($errors as $e) {
                $html .= '<tr><td ' . TPL::getSnippet('sTableErrorCol') . '>' .
                    sprintf(
                        "Exception '%s' with message '%s' in %s:%d",
                        get_class($e),
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine()
                    ) .
                    '</td></tr>';

            }
            $html .= '</table>';
        }
        return $html;
    }

    /**
     * Return backup html information.
     *
     * @param  \phpbu\App\Result $result
     * @return string
     */
    protected function getInfoHtml(Result $result)
    {
        $html    = '';
        $backups = $result->getBackups();
        if (count($backups)) {
            $html .= '<table ' . TPL::getSnippet('sTableBackup') . '>';
            /** @var \phpbu\App\Result\Backup $backup */
            foreach ($backups as $backup) {
                if ($backup->allOk()) {
                    $color  = TPL::getSnippet('cStatusOK');
                    $status = 'OK';
                } elseif($backup->okButSkipsOrFails()) {
                    $color  = TPL::getSnippet('cStatusWARN');
                    $status = 'WARNING';
                } else {
                    $color  = TPL::getSnippet('cStatusFAIL');
                    $status = 'FAILURE';
                }
                $html .= '<tr>' .
                          '<td ' . sprintf(TPL::getSnippet('sTableBackupStatusColumn'), $color) . ' colspan="4">' .
                          sprintf('backup <em>%s</em>', $backup->getName()) .
                          ' <span ' . TPL::getSnippet('sTableBackupStatusText') . '>' . $status .'</span>'.
                          '</td>' .
                         '</tr>' .
                         '<tr>' .
                          '<td ' . TPL::getSnippet('sRowHead') . '>&nbsp;</td>' .
                          '<td ' . TPL::getSnippet('sRowHead') . ' align="right">executed</td>' .
                          '<td ' . TPL::getSnippet('sRowHead') . ' align="right">skipped</td>' .
                          '<td ' . TPL::getSnippet('sRowHead') . ' align="right">failed</td>' .
                         '</tr>';

                $html .= '<tr>' .
                          '<td ' . TPL::getSnippet('sRowCheck') . '>checks</td>' .
                          '<td ' . TPL::getSnippet('sRowCheck') . ' align="right">' .
                            $backup->checkCount() . '
                           </td>' .
                          '<td ' . TPL::getSnippet('sRowCheck') . ' align="right">
                            &nbsp;
                           </td>' .
                          '<td ' . TPL::getSnippet('sRowCheck') . ' align="right">' .
                            $backup->checkCountFailed() .
                          '</td>' .
                         '</tr>' .
                         '<tr>' .
                          '<td ' . TPL::getSnippet('sRowCrypt') . '>crypts</td>' .
                          '<td ' . TPL::getSnippet('sRowCrypt') . ' align="right">' .
                            $backup->cryptCount() .
                          '</td>' .
                          '<td ' . TPL::getSnippet('sRowCrypt') . ' align="right">' .
                            $backup->cryptCountSkipped() .
                          '</td>' .
                          '<td ' . TPL::getSnippet('sRowCrypt') . ' align="right">' .
                            $backup->cryptCountFailed() .
                          '</td>' .
                         '</tr>' .
                         '<tr>' .
                          '<td ' . TPL::getSnippet('sRowSync') . '>syncs</td>' .
                          '<td ' . TPL::getSnippet('sRowSync') . ' align="right">' .
                            $backup->syncCount() . '</td>' .
                          '<td ' . TPL::getSnippet('sRowSync') . ' align="right">' .
                            $backup->syncCountSkipped() .
                          '</td>' .
                          '<td ' . TPL::getSnippet('sRowSync') . ' align="right">' .
                            $backup->syncCountFailed() .
                          '</td>' .
                         '</tr>' .
                         '<tr>' .
                          '<td ' . TPL::getSnippet('sRowCleanup') . '>cleanups</td>' .
                          '<td ' . TPL::getSnippet('sRowCleanup') . ' align="right">' .
                            $backup->cleanupCount() .
                          '</td>' .
                          '<td ' . TPL::getSnippet('sRowCleanup') . ' align="right">' .
                            $backup->cleanupCountSkipped() .
                          '</td>' .
                          '<td ' . TPL::getSnippet('sRowCleanup') . ' align="right">' .
                            $backup->cleanupCountFailed() .
                          '</td>' .
                         '</tr>';

            }
            $html .= '</table>';
        }
        return $html;
    }

    /**
     * Return mail body footer.
     *
     * @return string
     */
    protected function getFooterHtml()
    {
        return '<p ' . TPL::getSnippet('sStats') . '>' . PHP_Timer::resourceUsage() . '</p>' .
               '</td></tr></table>';
    }
}
