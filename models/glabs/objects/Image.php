<?php

namespace app\models\glabs\objects;

use app\commands\GlabsController;
use app\models\glabs\ProxyCurl;
use app\models\glabs\TorCurl;
use PHPHtmlParser\Exceptions\CurlException;
use yii\base\InvalidParamException;
use yii\base\Object;

/**
 * Object image class.
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class Image extends Object
{
    /**
     * URL.
     *
     * @var string
     */
    public $url;

    /**
     * File name.
     *
     * @var string
     */
    public $filename;

    /**
     * Binary data.
     *
     * @var string
     */
    public $data;

    /**
     * Object URL.
     *
     * @var string
     */
    public $object_url;

    /**
     * @inheritdoc
     *
     * @throws CurlException
     * @throws InvalidParamException
     * @throws ImageException
     */
    public function init()
    {
        $this->setData();
    }

    /**
     * Return URL.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set URL.
     *
     * @param string $url URL.
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Return filename.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set file name.ame.
     */
    public function setFilename()
    {
        $this->filename = basename($this->getUrl());
    }

    /**
     * Return data.
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get image content.
     *
     * @throws CurlException
     * @throws ImageException
     * @throws InvalidParamException
     */
    protected function setData()
    {
        /* @var ProxyCurl | TorCurl $curl */
        $curl = GlabsController::$curl;
        $curl::$referer = $this->object_url;
        try {
            $this->data = $curl->get($this->url);
        } catch (CurlException $e) {
            throw new ImageException('Cannot get image: ' . $e->getMessage());
        }

        if (false !== strpos($this->data, '<html')) {
            throw new ImageException('Response is HTML. Proxy error.');
        }

        $this->setUrl($curl->connectedURL);
        $this->setFilename();
        $this->storeFile();
    }

    /**
     * Get local file.
     *
     * @return string
     *
     * @throws ImageException
     * @throws InvalidParamException
     */
    public function getLocalFile()
    {
        $filename = \Yii::getAlias('@runtime/data/' . $this->filename);
        if (!file_exists($filename)) {
            throw new ImageException('File "' . $filename . '" not found.');
        }

        return $filename;
    }

    /**
     * Strore file to disk.
     *
     * @throws InvalidParamException
     */
    private function storeFile()
    {
        file_put_contents(\Yii::getAlias('@runtime/data/' . $this->filename),  $this->data. "\n");
    }
}
