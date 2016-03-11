<?php

namespace app\models\glabs\objects\massmail;

use Yii;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\CurlException;
use PHPHtmlParser\Exceptions\EmptyCollectionException;
use app\commands\GlabsController;
use app\models\glabs\db\MassMail;
use app\models\glabs\objects\Craigslist as BaseCraigslist;
use app\models\glabs\objects\ObjectException;
use app\models\glabs\TransportException;
use app\models\glabs\transport\MassMail as TransportMassMail;

/**
 * Class of users of craigslist.org.
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class Craigslist extends BaseCraigslist
{
    /**
     * @var integer
     */
    protected $object_id;

    /**
     * @var string
     */
    protected $reply_url;

    /**
     * @var string
     */
    protected $email;

    /**
     * @inheritdoc
     */
    protected function loadDom()
    {
        if (!$this->reply_url) {
            preg_match('/(\w+)\/(\d+)\.html/', $this->url, $matches);
            if (!isset($matches[1])) {
                throw new ObjectException('Can\'t match reply URL.');
            }

            $this->object_id = (int) $matches[2];
            $this->reply_url = 'http://' . parse_url($this->url, PHP_URL_HOST) . '/reply/lax/' . $matches[1] . '/' . $this->object_id;
        }

        try {
            $curl = GlabsController::$curl;
            $curl::$referer = $this->url;
            self::$dom->loadFromUrl($this->reply_url, [], GlabsController::$curl);
        } catch (CurlException $e) {
            if (false !== strpos($e->getMessage(), 'timed out')) {
                GlabsController::showMessage(' ...trying again', false);
                return $this->loadDom();
            }

            throw new ObjectException($e->getMessage());
        } catch (EmptyCollectionException $e) {
            throw new ObjectException($e->getMessage());
        }

        return true;
    }

    /**
     * Parse object page.
     *
     * @return bool
     *
     * @throws CurlException
     * @throws ObjectException
     * @throws EmptyCollectionException
     */
    public function parse()
    {
        $this->loadDom();

        $this->setEmail();

        return true;
    }

    /**
     * Returns reply URL.
     *
     * @return string
     */
    public function getReplyUrl()
    {
        return $this->reply_url;
    }

    /**
     * Returns email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email.
     *
     * @return true
     *
     * @throws ObjectException
     * @throws EmptyCollectionException
     */
    protected function setEmail()
    {
        /* @var \PHPHtmlParser\Dom\AbstractNode $postingbody */
        $postingbody = self::$dom->find('.anonemail', 0);
        if (!$postingbody) {
            throw new ObjectException('No email in content.');
        }

        $this->email = $postingbody->text();

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new ObjectException('Has no email.');
        }

        return true;
    }

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

        if ($massmail && $massmail->is_sent) {
            throw new ObjectException('Email has been already sent.');
        }

        if (!$massmail) {
            $massmail             = new MassMail();
            $massmail->object_id  = $this->object_id;
            $massmail->object_url = $this->url;
            $massmail->reply_url  = $this->reply_url;
            $massmail->subject    = $this->title;
            $massmail->to         = $this->email;
            $massmail->message    = file_get_contents(Yii::getAlias('@runtime/data/blank_email.html'));
            $massmail->created_at = time();
        }

        if (!$massmail->save()) {
            throw new ObjectException('Model save errors: ' . $this->getErrors($massmail));
        }

        $massmail->is_sent = (new TransportMassMail($massmail))->send($isTest);

        if ($massmail->is_sent) {
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
