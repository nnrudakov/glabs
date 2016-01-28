<?php

namespace app\models\glabs\objects;

use app\commands\GlabsController;
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
class BaseObject
{
    /**
     * Dom.
     *
     * @var \PHPHtmlParser\Dom
     */
    protected static $dom;

    /**
     * URL.
     *
     * @var string
     */
    protected $url;

    /**
     * Title.
     *
     * @var string
     */
    protected $title;

    /**
     * Category.
     *
     * @var integer
     */
    protected $category = 0;

    /**
     * Description.
     *
     * @var string
     */
    protected $description;

    /**
     * Price.
     *
     * @var string
     */
    protected $price = 0;

    /**
     * Main image.
     *
     * @var Image
     */
    protected $thumbnail;

    /**
     * Type.
     *
     * @var string
     */
    protected $productSellType = 'Sell';

    /**
     * Images.
     *
     * @var Image[]
     */
    protected $subimage = [];

    /**
     * @var array
     */
    protected $emails = [];

    /**
     * @var bool
     */
    protected $phone = false;

    /**
     * Category constructor.
     *
     * @param string  $url        Link.
     * @param string  $title      Title.
     * @param integer $categoryId Category ID.
     * @param string  $type       Type.
     */
    public function __construct($url, $title, $categoryId, $type)
    {
        $this->url             = $url;
        $this->title           = $title;
        $this->category        = $categoryId;
        $this->productSellType = $type;
        self::$dom = new Dom();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'category'          => $this->getCategory(),
            'title'             => $this->getTitle(),
            'description'       => $this->getDescription(),
            'product_sell_type' => $this->getProductSellType(),
            'mrp'               => $this->getPrice(),
            'short_description' => 'New',
            'delivery_time'     => 'pickup',
            'no_of_items'       => 1
        ];
    }

    /**
     * Parse object page.
     *
     * @throws CurlException
     * @throws ObjectException
     */
    public function parse()
    {
        try {
            self::$dom->loadFromUrl($this->url, [], GlabsController::$curl);
        } catch (CurlException $e) {
            if (false === strpos($e->getMessage(), 'timed out') ) {
                GlabsController::showMessage(' ...trying again', false);
                throw new CurlException($e->getMessage());
            }
            $this->parse();
        }
        $this->setTitle();
        $this->setDescription();
        $this->setPhone();
        $this->setEmails();
        $this->setImages();

        if (!$this->phone && !$this->emails) {
            throw new ObjectException('Has no phone and email.');
        }

        /*print_r($this->toArray());
        die;*/
    }

    /**
     * Send object to Zoheny.com
     *
     * @param bool $isTest
     *
     * @return bool
     *
     * @throws TransportException
     */
    public function send($isTest = false)
    {
        return (new Transport($this))->send($isTest);
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
     * Return title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title.
     */
    protected function setTitle()
    {

    }

    /**
     * Return category.
     *
     * @return integer
     */
    public function getCategory()
    {
        return $this->category;
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
     * @throws ObjectException
     */
    protected function setDescription()
    {

    }

    /**
     * Return price.
     *
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set price.
     *
     * @param \PHPHtmlParser\Dom\AbstractNode $node Node.
     *
     * @throws ObjectException
     */
    public function setPrice($node = null)
    {

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

    /**
     * @return string
     */
    public function getProductSellType()
    {
        return $this->productSellType;
    }

    /**
     * Set images.
     *
     * @return bool
     *
     * @throws ObjectException
     */
    protected function setImages()
    {

    }

    /**
     * @return bool
     */
    protected function setPhone()
    {
        $patterns = [
            // 111-111-1111 | 1111111111 | 111 1111111     |   (111) 111-1111     | 111......... 111..........11.......11....
            '/\d+-\d+-\d+/', '/\d{10}/', '/\d{3}\s+\d{7}/', '/\(\d+\)\s?[\d+-]+/', '/\d+\.+\s?\d+\.+\d+\.+\d+\.+/',
            // 111 111 1111        |    1 -111- 111        |  111--111--1111
            '/\d{3}\s\d{3}\s\d{4}/', '/\d+\s+-\d+-\s+\d+/', '/\d+--\d+--\d+/'
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $this->description)) {
                $this->phone = true;
                return true;
            }
        }

        /* @var \PHPHtmlParser\Dom\AbstractNode[] $contacts */
        if ($contacts = self::$dom->find('.metaInfoDisplay', 0)) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $contacts)) {
                    $this->phone = true;
                    return true;
                }
            }
        }
    }

    /**
     * Return object emails.
     *
     * @return array
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * Set emails.
     */
    protected function setEmails()
    {
        preg_match_all('/[a-zA-Z0-9.!#$%&’*+\/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*/', $this->description, $matches);
        $this->emails = $matches[0];
    }
}
