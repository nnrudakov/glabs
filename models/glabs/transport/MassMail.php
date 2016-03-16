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
    const FROM = 'help@zoheny.com';

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
        $mailer = Yii::$app->mailer->compose('zoheny', [
            'title' => $this->massmail->subject,
            'img1' => Yii::getAlias('@runtime/massmail/img1.png'),
            'img2' => Yii::getAlias('@runtime/massmail/img2.png'),
            'img3' => Yii::getAlias('@runtime/massmail/img3.png'),
            'img6' => Yii::getAlias('@runtime/massmail/img6.png'),
            'img9' => Yii::getAlias('@runtime/massmail/img9.png')
        ])
            ->setSubject($this->massmail->subject)
            ->setFrom([self::FROM => 'Zoheny.com'])
            ->setReplyTo(self::FROM)
            ->setTo([$this->massmail->to]);

        if ($isTest) {
            return false;
        }

        return $mailer->send();
        /* @var \Swift_Message $message */
        /*$message = \Swift_Message::newInstance();
        $body = $this->massmail->message;
        $body = str_replace(
            ['{$title}', '{$img1}', '{$img2}', '{$img3}', '{$img6}', '{$img9}'],
            [
                $this->massmail->subject,
                $message->embed(\Swift_Image::fromPath(Yii::getAlias('@runtime/massmail/img1.png'))),
                $message->embed(\Swift_Image::fromPath(Yii::getAlias('@runtime/massmail/img2.png'))),
                $message->embed(\Swift_Image::fromPath(Yii::getAlias('@runtime/massmail/img3.png'))),
                $message->embed(\Swift_Image::fromPath(Yii::getAlias('@runtime/massmail/img6.png'))),
                $message->embed(\Swift_Image::fromPath(Yii::getAlias('@runtime/massmail/img9.png')))
            ],
            $body
        );

        $message
            ->setSubject($this->massmail->subject)
            ->setFrom([self::FROM => 'Zoheny.com'])
            ->setReturnPath(self::FROM)
            ->setTo([$this->massmail->to])
            ->setBody($body, 'text/html', 'utf-8');
        $transport = \Swift_MailTransport::newInstance();
        $mailer = \Swift_Mailer::newInstance($transport);

        if ($isTest) {
            return false;
        }

        if (!$mailer->send($message, $failures)) {
            throw new TransportException('Mail send errors: ' . implode(', ', $failures));
        }

        return true;*/
    }
}
