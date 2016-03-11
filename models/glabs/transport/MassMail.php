<?php

namespace app\models\glabs\transport;

use app\models\glabs\TransportException;
use app\models\glabs\db\MassMail as MassMailModel;

/**
 * Mass mail transport.
 *
 * @package    glabs
 * @subpackage app\models\glabs\transport
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class MassMail
{
    /**
     * @var string
     */
    const FROM = 'info@zoheny.com';

    /**
     * @var MassMailModel
     */
    private $massmail;

    /**
     * Constructor.
     *
     * @param MassMailModel $massmail
     */
    public function __construct(MassMailModel $massmail)
    {
        $this->massmail = $massmail;
    }

    /**
     * Send email.
     *
     * @param bool $isTest
     *
     * @return bool
     *
     * @throws TransportException
     */
    public function send($isTest = false)
    {
        /* @var \Swift_Message $message */
        $message = \Swift_Message::newInstance()
            ->setSubject($this->massmail->subject)
            ->setFrom([self::FROM => 'Zoheny.com'])
            ->setReturnPath(self::FROM)
            ->setTo([$this->massmail->to])
            ->setBody($this->massmail->message, 'text/html', 'uft-8');
        $transport = \Swift_MailTransport::newInstance();
        $mailer = \Swift_Mailer::newInstance($transport);

        if (!$mailer->send($message, $failures)) {
            throw new TransportException('Mail send errors: ' . implode(', ', $failures));
        }

        return true;
    }
}
