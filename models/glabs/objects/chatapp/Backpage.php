<?php

namespace app\models\glabs\objects\chatapp;

use app\models\glabs\objects\Image;
use app\models\glabs\objects\ImageException;
use app\models\glabs\objects\ObjectException;
use PHPHtmlParser\Dom;

/**
 * Class of users of backpage.com.
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
    protected function setAboutme()
    {
        /* @var \PHPHtmlParser\Dom\AbstractNode $postingbody */
        $postingbody = self::$dom->find('.postingBody', 0);
        if (!$postingbody) {
            throw new ObjectException('There is no content');
        }
        $this->aboutme = $postingbody->innerHtml();

        parent::setAboutme();
    }

    /**
     * @inheritdoc
     */
    protected function setImages()
    {
        /* @var \PHPHtmlParser\Dom\AbstractNode $imageTag */
        $imageTag = $this->getImageTags();
        try {
            $this->thumbnail = new Image(['url' => $imageTag->getAttribute('src')]);
        } catch (ImageException $e) {
            return false;
        }

        return true;
    }

    /**
     * @return \PHPHtmlParser\Dom\AbstractNode
     */
    private function getImageTags()
    {
        /* @var \PHPHtmlParser\Dom\AbstractNode $photos */
        $photos = self::$dom->getElementById('viewAdPhotoLayout');
        if ($photos) {
            return $photos->find('img', 0);
        }

        /* @var \PHPHtmlParser\Dom\AbstractNode $postingbody */
        $postingbody = self::$dom->find('.postingBody', 0);
        return $postingbody->find('img', 0);
    }
}
