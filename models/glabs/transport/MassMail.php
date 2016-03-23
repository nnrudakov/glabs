<?php

namespace app\models\glabs\transport;

use yii;
use yii\base\InvalidParamException;
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
    }
}
