<?php

namespace app\models\glabs\objects;

use app\commands\GlabsController;
use app\models\glabs\ProxyCurl;
use app\models\glabs\Transport;
use app\models\glabs\TransportException;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\CurlException;

/**
 * Base class of objects.
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class Object
{
    /**
     * Dom.
     *
     * @var \PHPHtmlParser\Dom
     */
    private static $dom;

    /**
     * URL.
     *
     * @var string
     */
    private $url;

    /**
     * Title.
     *
     * @var string
     */
    private $title;

    /**
     * Description.
     *
     * @var string
     */
    private $description;

    /**
     * Main image.
     *
     * @var Image
     */
    private $thumbnail;

    /**
     * Type.
     *
     * @var string
     */
    private $productSellType = 'Sell';

    /**
     * Images.
     *
     * @var Image[]
     */
    private $subimage = [];

    /**
     * Category constructor.
     *
     * @param string $url   Link.
     * @param string $title Name.
     */
    public function __construct($url, $title)
    {
        $this->url   = $url;
        $this->title = $title;
        self::$dom = new Dom();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'title'             => $this->getTitle(),
            'description'       => $this->getDescription(),
            'product_sell_type' => $this->getProductSellType()
        ];
    }

    /**
     * Parse object page.
     */
    public function parse()
    {
        self::$dom->loadFromUrl($this->url, [], new ProxyCurl());
        $this->setDescription();
        $this->setImages();
        //print_r($this); //die;
    }

    /**
     * Send object to Zohney.com
     *
     * @return bool
     *
     * @throws TransportException
     */
    public function send()
    {
        return (new Transport($this))->send();
    }

    /**
     * Return title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Return description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set description.
     *
     * @return bool
     *
     * @throws \PHPHtmlParser\Exceptions\CurlException
     */
    private function setDescription()
    {
        /* @var \PHPHtmlParser\Dom\AbstractNode $postingbody */
        $postingbody = self::$dom->find('#postingbody');
        /* @var \PHPHtmlParser\Dom\AbstractNode $contact */
        // "click" to show contact
        if ($contact = $postingbody->find('.showcontact')[0]) {
            try {
                $this->description = (new ProxyCurl())->get(
                    'http://' . parse_url($this->url, PHP_URL_HOST) . $contact->getAttribute('href')
                );
            } catch (CurlException $e) {
                GlabsController::showMessage("\t\t" . 'Object missed because od error: ' . $e->getMessage());
            }
        } else {
            $this->description = $postingbody->innerHtml();
        }

        return true;
    }

    /**
     * Return main image.
     *
     * @return Image
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * Other images.
     *
     * @return Image[]
     */
    public function getSubimage()
    {
        return $this->subimage;
    }

    public function getProductSellType()
    {
        return $this->productSellType;
    }

    /**
     * Set images.
     *
     * @return bool
     */
    private function setImages()
    {
        /* @var \PHPHtmlParser\Dom\AbstractNode $figure */
        $figure = self::$dom->find('figure')[0];
        if (!$figure) {
            return false;
        }

        $is_multiimage = false !== strpos($figure->getAttribute('class'), 'multiimage');

        if ($is_multiimage) {
            /* @var \PHPHtmlParser\Dom\AbstractNode $link */
            foreach ($figure->find('a') as $link) {
                if (count($this->subimage) >= 4) {
                    break;
                }

                $href = $link->getAttribute('href');
                if (!$this->thumbnail) {
                    $this->thumbnail = new Image(['url' => $href]);
                } else {
                    $this->subimage[] = new Image(['url' => $href]);
                }
            }
        } else {
            $this->thumbnail = new Image(['url' => $figure->find('img')[0]->getAttribute('src')]);
        }

        return true;
    }
}
