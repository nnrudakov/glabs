<?php

namespace app\models\glabs\categories;

use app\commands\GlabsController;
use app\models\glabs\ProxyCurl;
use app\models\glabs\objects\Object;
use app\models\glabs\sites\BaseSite;
use app\models\glabs\TransportException;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\CurlException;
use yii\base\InvalidParamException;

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
     * Category page.
     *
     * @var integer
     */
    private static $page = 0;

    /**
     * Type.
     *
     * @var string
     */
    private $type;

    /**
     * Category constructor.
     *
     * @param array   $url        Link.
     * @param string  $title      Title.
     * @param integer $categoryId Category ID.
     * @param string  $type       Type.
     * @param integer $count      Count objects;
     */
    public function __construct($url, $title, $categoryId, $type, $count)
    {
        $this->url        = $url;
        $this->title      = $title;
        $this->categoryId = $categoryId;
        $this->type       = $type;
        $this->count      = $count;
        $this->getObjectsLinks();
    }

    /**
     * Fill objects by name and URL.
     */
    protected function getObjectsLinks()
    {
        foreach ($this->url as $url) {
            self::$page = 0;
            if (!self::$page) {
                $url = '?s='. self::$page;
            }
            $this->collectObjects($url);
        }
    }

    /**
     * Collect objects.
     *
     * @param string $url URL.
     *
     * @return bool
     */
    private function collectObjects($url)
    {
        $host = 'http://' . parse_url($url, PHP_URL_HOST);
        $dom = new Dom();
        $dom->loadFromUrl($url, [], new ProxyCurl());

        // end collect. no results
        if ($dom->find('#moon')[0]) {
            return true;
        }

        $this->checkTotalObjects($dom);

        /* @var \PHPHtmlParser\Dom\AbstractNode $span */
        foreach ($dom->find('.txt') as $span) {
            if (count($this->objects) >= $this->count) {
                break;
            }

            /* @var \PHPHtmlParser\Dom\AbstractNode $link */
            if ($link = $span->find('a')[0]) {
                $object = new Object($host . $link->getAttribute('href'), $link->text(), $this->categoryId, $this->type);
                $object->setPrice($span);

                if (!$object->getPrice()) {
                    continue;
                }

                $this->objects[] = $object;
                BaseSite::$doneObjects++;
                BaseSite::progress();
            }
        }

        if (count($this->objects) < $this->count) {
            $url = str_replace('?s=' . self::$page, '', $url);
            self::$page += 100;
            return $this->collectObjects($url . '?s=' . self::$page);
        }

        return true;
    }

    /**
     * Parse category page.
     *
     * @throws CurlException
     * @throws InvalidParamException
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
            GlabsController::saveObjectsEmails($object);
        }
    }

    /**
     * Check total object. If not, set it.
     *
     * @param Dom $dom Dom.
     *
     * @return bool
     */
    private function checkTotalObjects($dom)
    {
        if (!$this->count) {
            /* @var \PHPHtmlParser\Dom\AbstractNode $total_count */
            $total_count = $dom->find('.totalcount')[0];
            $this->count = (int) $total_count->text();
        }

        return true;
    }
}
