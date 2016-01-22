<?php

namespace app\models\glabs\categories;

use app\commands\GlabsController;
use app\models\glabs\ProxyCurl;
use app\models\glabs\objects\Object;
use app\models\glabs\sites\BaseSite;
use app\models\glabs\TransportException;
use PHPHtmlParser\Dom;

/**
 * Base class of categories.
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class Category
{
    /**
     * URL.
     *
     * @var array
     */
    private $url = [];

    /**
     * Title.
     *
     * @var string
     */
    private $title;

    /**
     * Category ID.
     *
     * @var integer
     */
    private $categoryId;

    /**
     * Count objects.
     *
     * @var integer
     */
    private $count;

    /**
     * Objects.
     *
     * @var Object[]
     */
    private $objects = [];

    /**
     * Category constructor.
     *
     * @param array   $url        Link.
     * @param string  $title      Title.
     * @param integer $categoryId Category ID.
     * @param integer $count      Count objects;
     */
    public function __construct($url, $title, $categoryId, $count)
    {
        $this->url        = $url;
        $this->title      = $title;
        $this->categoryId = $categoryId;
        $this->count      = $count;
        $this->getObjectsLinks($count);
    }

    /**
     * Fill object by name and URL.
     *
     * @param integer $count Objects count.
     */
    protected function getObjectsLinks($count)
    {
        $dom = new Dom();

        foreach ($this->url as $url) {
            $dom->loadFromUrl($url, [], new ProxyCurl());

            /* @var \PHPHtmlParser\Dom\AbstractNode $span */
            foreach ($dom->find('.txt') as $span) {
                if ($count && count($this->objects) >= $count) {
                    break;
                }

                /* @var \PHPHtmlParser\Dom\AbstractNode $link */
                if ($link = $span->find('a')[0]) {
                    $title = $link->text();
                    /* @var \PHPHtmlParser\Dom\AbstractNode $price */
                    if ($price = $span->find('.price')[0]) {
                        $price = $price->text();
                    } else {
                        $price = 0;

                        if (preg_match('/\$(\d+)/', $title, $matches)) {
                            $price = $matches[1];
                        } else if (preg_match('/(\d+)\$/', $title, $matches)) {
                            $price = $matches[1];
                        }
                    }

                    if (!$price) {
                        continue;
                    }

                    $object = new Object(
                        'http://' . parse_url($url, PHP_URL_HOST) . $link->getAttribute('href'),
                        $title,
                        $this->categoryId,
                        $price
                    );
                    $this->objects[] = $object;
                    BaseSite::$doneObjects++;
                    BaseSite::progress();
                }
            }
        }
    }

    /**
     * Parse category page.
     */
    public function parse()
    {
        GlabsController::showMessage('Parsing category "' . $this->title . '"');
        for ($i = 0, $count = count($this->objects); $i < $count; $i++) {
            /* @var \app\models\glabs\objects\Object $object */
            $object = $this->objects[$i];
            GlabsController::showMessage("\t" . ($i + 1) . ') Parsing object "' . $object->getTitle() .
                '" (' . $object->getUrl() . ')');
            $object->parse();
            if (!$object->getThumbnail()) {
                GlabsController::showMessage("\t\t" . 'Object skipped because it has no files.');
                continue;
            }
            GlabsController::showMessage("\t\t" . 'Sending object... ', false);
            try {
                $object->send();
                GlabsController::showMessage('Success.');
            } catch (TransportException $e) {
                GlabsController::showMessage('Fail with message: "' . $e->getMessage() . '"');
            }
        }
    }
}
