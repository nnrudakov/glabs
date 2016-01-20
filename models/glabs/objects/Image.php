<?php

namespace app\models\glabs\objects;

use yii\base\Object as BaseObject;

/**
 * Object image class.
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class Image extends BaseObject
{
    /**
     * URL.
     *
     * @var string
     */
    public $url;

    /**
     * Binary data.
     *
     * @var string
     */
    public $data;

    public function init()
    {
        $this->setData(file_get_contents($this->getUrl()));
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
     * Return data.
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $value
     */
    protected function setData($value)
    {
        $this->data = $value;
    }
}
