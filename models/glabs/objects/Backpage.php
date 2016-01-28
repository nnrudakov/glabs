<?php

namespace app\models\glabs\objects;

use PHPHtmlParser\Dom;

/**
 * Class of objects of backpage.com.
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class Backpage extends BaseObject
{
    /**
     * @inheritdoc
     */
    protected function setTitle()
    {
        if ('none' === $this->title) {
            $this->title = self::$dom->find('h1', 0)->text();
        }
    }

    /**
     * @inheritdoc
     */
    protected function setDescription()
    {
        /* @var \PHPHtmlParser\Dom\AbstractNode $postingbody */
        $postingbody = self::$dom->find('.postingBody', 0);
        if (!$postingbody) {
            throw new ObjectException('There is no content');
        }
        $this->description = $postingbody->innerHtml();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function setPrice()
    {
        if (preg_match('/\$([\d+,]+)/', $this->title, $matches)) {
            $this->price = $matches[1];
            $this->price = str_replace(',', '', $this->price);
        }

        if (!$this->price) {
            throw new ObjectException('There is no price in object.');
        }
    }

    /**
     * @inheritdoc
     */
    protected function setImages()
    {
        /* @var \PHPHtmlParser\Dom\AbstractNode[] $imageTags */
        list($imageTags, $layout) = $this->getImageTags();

        foreach ($imageTags as $imageTag) {
            if (count($this->subimage) >= 4) {
                break;
            }

            if (!$layout && $imageTag->getAttribute('alt')) {
                continue;
            }

            $parent = $imageTag->getParent();
            $url = $parent->getAttribute('href');
            if (false === strpos($url, '.jpg')) {
                $url = $imageTag->getAttribute('src');
                if (!$layout &&
                    (false === strpos($url, 'GetImage.aspx') || false === strpos($url, 'images.psndealer.com'))) {
                    continue;
                }
            }

            try {
                $image = new Image(['url' => $url]);
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

        return true;
    }

    /**
     * @return array
     */
    private function getImageTags()
    {
        /* @var \PHPHtmlParser\Dom\AbstractNode $photos */
        $photos = self::$dom->getElementById('viewAdPhotoLayout');
        if ($photos) {
            return [$photos->find('img'), true];
        }

        /* @var \PHPHtmlParser\Dom\AbstractNode $postingbody */
        $postingbody = self::$dom->find('.postingBody', 0);
        return [$postingbody->find('img'), false];
    }
}
