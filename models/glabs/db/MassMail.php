<?php

namespace app\models\glabs\db;

use yii\mongodb\ActiveRecord;

/**
 * Model for mass mails.
 *
 * @property \MongoId $_id        ID.
 * @property integer  $object_id  Object ID.
 * @property string   $object_url Object URL.
 * @property string   $reply_url  URL that contains email.
 * @property string   $subject    Email subject.
 * @property string   $to         Email.
 * @property string   $message    Email text which sent to user.
 * @property bool     $is_sent    Email is sent.
 * @property integer  $created_at Created date.
 * @property integer  $sent_at    Sent date.
 *
 * @package    glabs
 * @subpackage massmail
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class MassMail extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function collectionName()
    {
        return 'massmail';
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            '_id', 'object_id', 'object_url', 'reply_url', 'subject', 'to', 'message', 'is_send',
            'created_at', 'sent_at'
        ];
    }

    /**
     * Find by object ID.
     *
     * @param integer $id
     *
     * @return MassMail
     */
    public static function findByObjectId($id)
    {
        return static::findOne(['object_id' => $id]);
    }
}
