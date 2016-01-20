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
     * @param string  $url   Link.
     * @param string  $title Name.
     * @param integer $count Count objects;
     */
    public function __construct($url, $title, $count)
    {
        $this->url   = $url;
        $this->title = $title;
        $this->count = $count;
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
        $dom->loadFromUrl($this->url, [], new ProxyCurl());

        /* @var \PHPHtmlParser\Dom\AbstractNode $link */
        foreach ($dom->find('.pl > a') as $link) {
            if ($count && count($this->objects) >= $count) {
                break;
            }
            $this->objects[] = new Object(
                'http://' . parse_url($this->url, PHP_URL_HOST) . $link->getAttribute('href'),
                $link->text()
            );
            BaseSite::$doneObjects++;
            BaseSite::progress();
        }

        GlabsController::showMessage('');
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
            GlabsController::showMessage("\t" . ($i + 1) . ') Parsing object "' . $object->getTitle() . '"');
            $object->parse();
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
