<?php

namespace app\models\glabs\objects;

use app\models\glabs\TorCurl;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\CurlException;

/**
 * Class of objects of craigslist.org.
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class Craigslist extends BaseObject
{
    /**
     * @inheritdoc
     */
    protected function setDescription()
    {
        /* @var \PHPHtmlParser\Dom\AbstractNode $postingbody */
        $postingbody = self::$dom->find('#postingbody');
        /* @var \PHPHtmlParser\Dom\AbstractNode $contact */
        // "click" to show contact
        if ($contact = $postingbody->find('.showcontact', 0)) {
            try {
                $description = (new TorCurl())->get(
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
     * @inheritdoc
     */
    public function setPrice($node)
    {
        /* @var \PHPHtmlParser\Dom\AbstractNode $price */
        if ($price = $node->find('.price', 0)) {
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
     * @inheritdoc
     */
    protected function setImages()
    {
        /* @var \PHPHtmlParser\Dom\AbstractNode $figure */
        $figure = self::$dom->find('figure', 0);
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

                try {
                    $image = new Image(['url' => $link->getAttribute('href')]);
                } catch (ImageException $e) {
                    continue;
                }

                if (!$this->thumbnail) {
                    $this->thumbnail = $image;
                } else {
                    $this->subimage[] = $image;
                }
            }

            if (!$this->thumbnail) {
                throw new ObjectException('Has no files.');
            }
        } else {
            try {
                $this->thumbnail = new Image(['url' => $figure->find('img', 0)->getAttribute('src')]);
            } catch (ImageException $e) {
                throw new ObjectException('Has no files.');
            }
        }

        return true;
    }
}
