<?php

namespace app\models\glabs\objects;

use app\commands\GlabsController;
use app\models\glabs\ProxyCurl;
use app\models\glabs\TorCurl;
use PHPHtmlParser\Exceptions\CurlException;
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
     */
    protected function setData()
    {
        /* @var ProxyCurl | TorCurl $curl */
        $curl = GlabsController::$curl;
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
    }
}
