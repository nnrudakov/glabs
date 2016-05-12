<?php

namespace app\models\glabs\objects\massmail;

use yii;
use yii\base\InvalidParamException;
use yii\base\Object;
use yii\helpers\ArrayHelper;
use PHPHtmlParser\Dom;
use app\models\glabs\db\MassMail;
use app\models\glabs\objects\ObjectException;
use app\models\glabs\TransportException;
use app\models\glabs\transport\MassMail as TransportMassMail;

/**
 * Class of simple mail
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class SimpleObject extends Object
{
    /**
     * @var integer
     */
    public $object_id;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $title;

    /**
     * Send email.
     *
     * @param bool $isTest
     *
     * @return bool
     *
     * @throws ObjectException
     * @throws InvalidParamException
     * @throws TransportException
     */
    public function send($isTest = false)
    {
        $massmail = MassMail::findByObjectId($this->object_id);

        if ($massmail && $massmail->is_send) {
            throw new ObjectException('Email has been already sent.');
        }

        if (!$massmail) {
            $massmail             = new MassMail();
            $massmail->object_id  = $this->object_id;
            $massmail->subject    = $this->title;
            $massmail->to         = $this->email;
            $massmail->message    = file_get_contents(Yii::getAlias('@runtime/massmail/blank_email.html'));
            $massmail->created_at = time();
        }

        if (!$massmail->save()) {
            throw new ObjectException('Model save errors: ' . $this->getErrors($massmail));
        }

        $massmail->is_send = (new TransportMassMail($massmail))->send($isTest);

        if (!$massmail->is_send) {
            throw new ObjectException('Email not send, see logs.');
        }

        if ($massmail->is_send) {
            $massmail->sent_at = time();
        }

        $massmail->save();

        return true;
    }

    /**
     * @param \yii\mongodb\ActiveRecord $model
     *
     * @return string
     */
    protected function getErrors($model)
    {
        $errors = [];
        foreach ($model->getErrors() as $field_error) {
            $errors = ArrayHelper::merge($errors, $field_error);
        }

        return implode(', ', $errors);
    }
}
