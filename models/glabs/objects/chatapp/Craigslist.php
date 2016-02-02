<?php

namespace app\models\glabs\objects\chatapp;

use app\models\glabs\objects\Image;
use app\models\glabs\objects\ObjectException;
use PHPHtmlParser\Dom;

/**
 * Class of users of craigslist.org.
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
    protected function setAboutme()
    {
        if (self::$dom->find('.removed', 0)) {
            throw new ObjectException('This posting has been flagged for removal.');
        }

        /* @var \PHPHtmlParser\Dom\AbstractNode $postingbody */
        $postingbody = self::$dom->find('#postingbody');
        if (!$postingbody) {
            throw new ObjectException('Content is empty');
        }

        $this->aboutme = $postingbody->innerHtml();

        parent::setAboutme();
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

        $this->thumbnail = new Image(['url' => $figure->find('img', 0)->getAttribute('src')]);

        return true;
    }
}
