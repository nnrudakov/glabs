<?php

namespace app\models\glabs\transport;

use Yii;
use app\models\glabs\TransportException;
use app\models\glabs\db\MassMail as MassMailModel;
use yii\base\InvalidParamException;

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
     * @throws InvalidParamException
     */
    public function send($isTest = false)
    {
        /* @var \Swift_Message $message */
        $message = \Swift_Message::newInstance();
        $body = $this->massmail->message;
        $body = str_replace(
            ['{$title}', '{$cover_img}', '{$appstore_img}', '{$googleplay_img}'],
            [
                $this->massmail->subject,
                $message->embed(\Swift_Image::fromPath(Yii::getAlias('@runtime/data/cover_img.png'))),
                $message->embed(\Swift_Image::fromPath(Yii::getAlias('@runtime/data/appstore_img.png'))),
                $message->embed(\Swift_Image::fromPath(Yii::getAlias('@runtime/data/googleplay_img.png')))
            ],
            $body
        );

        $message
            ->setSubject($this->massmail->subject)
            ->setFrom([self::FROM => 'Zoheny.com'])
            ->setReturnPath(self::FROM)
            ->setTo([$this->massmail->to])
            ->setBody($body, 'text/html', 'uft-8');
        $transport = \Swift_MailTransport::newInstance();
        $mailer = \Swift_Mailer::newInstance($transport);

        if (!$mailer->send($message, $failures)) {
            throw new TransportException('Mail send errors: ' . implode(', ', $failures));
        }

        return true;
    }
}
