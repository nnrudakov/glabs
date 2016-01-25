<?php

namespace app\models\glabs\objects;

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
     * Category.
     *
     * @var integer
     */
    private $category = 0;

    /**
     * Description.
     *
     * @var string
     */
    private $description;

    /**
     * Price.
     *
     * @var string
     */
    private $price = 0;

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
     * @var array
     */
    private $emails = [];

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
        self::$dom->loadFromUrl($this->url, [], new ProxyCurl());
        $this->setDescription();
        $this->setPhone();
        $this->setEmails();
        $this->setImages();
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
    private function setDescription()
    {
        /* @var \PHPHtmlParser\Dom\AbstractNode $postingbody */
        $postingbody = self::$dom->find('#postingbody');
        /* @var \PHPHtmlParser\Dom\AbstractNode $contact */
        // "click" to show contact
        if ($contact = $postingbody->find('.showcontact')[0]) {
            try {
                $description = (new ProxyCurl())->get(
                    'http://' . parse_url($this->url, PHP_URL_HOST) . $contact->getAttribute('href')
                );
                if (false !== strpos($description, 'g-recaptcha')) {
                    throw new ObjectException('Showed Google captcha.');
                }
                $this->description = $description;
            } catch (CurlException $e) {
                throw new ObjectException('Could not get contacts (' . $e->getMessage() . ').');
            }
        } else {
            $this->description = $postingbody->innerHtml();
        }

        return true;
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
    public function setPrice($node)
    {
        /* @var \PHPHtmlParser\Dom\AbstractNode $price */
        if ($price = $node->find('.price')[0]) {
            $this->price = $price->text();
        } else {
            if (preg_match('/\$(\d+)/', $this->title, $matches)) {
                $this->price = $matches[1];
            } else if (preg_match('/(\d+)\$/', $this->title, $matches)) {
                $this->price = $matches[1];
            }
        }

        $this->price = str_replace('$', '', $this->price);

        if (!$this->price) {
            throw new ObjectException('There is no price in object.');
        }
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
            /** @noinspection PhpUndefinedMethodInspection */
            $this->thumbnail = new Image(['url' => $figure->find('img')[0]->getAttribute('src')]);
        }

        if (!$this->thumbnail) {
            throw new ObjectException('Has no files.');
        }

        return true;
    }

    /**
     * @return bool
     *
     * @throws ObjectException
     */
    private function setPhone()
    {
        $patterns = [
            // 111-111-1111 | 1111111111 | 111 1111111     |   (111) 111-1111     | 111......... 111..........11.......11....
            '/\d+-\d+-\d+/', '/\d{10}/', '/\d{3}\s+\d{7}/', '/\(\d+\)\s?[\d+-]+/', '/\d+\.+\s?\d+\.+\d+\.+\d+\.+/',
            // 111 111 1111
            '/\d+\s\d+\s\d+/'
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $this->description)) {
                return true;
            }
        }

        throw new ObjectException('Has no phone.');
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
    private function setEmails()
    {
        preg_match_all('/[a-zA-Z0-9.!#$%&’*+\/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*/', $this->description, $matches);
        $this->emails = $matches[0];
    }
}
