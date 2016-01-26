<?php

namespace app\models\glabs\categories;

use app\commands\GlabsController;
use app\models\glabs\objects\ObjectException;
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
abstract class BaseCategory
{
    /**
     * URL.
     *
     * @var array
     */
    protected $url = [];

    /**
     * Title.
     *
     * @var string
     */
    protected $title;

    /**
     * Category ID.
     *
     * @var integer
     */
    protected $categoryId;

    /**
     * Count objects.
     *
     * @var integer
     */
    protected $count = 0;

    /**
     * Count objects.
     *
     * @var integer
     */
    protected $needCount = 0;

    /**
     * Objects.
     *
     * @var Object[]
     */
    protected $objects = [];

    /**
     * @var Object[]
     */
    protected $doneObjects = [];

    /**
     * Category page.
     *
     * @var integer
     */
    protected static $page = 0;

    /**
     * Page parameter.
     *
     * @var string
     */
    protected static $pageParam = '';

    /**
     * Type.
     *
     * @var string
     */
    protected $type;

    /**
     * @var integer
     */
    protected $i = 0;

    /**
     * @var array
     */
    protected $collected = [];

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
        $this->count      = $this->needCount = $count;
        $this->getObjectsLinks();
    }

    /**
     * Fill objects by name and URL.
     *
     * @throws CurlException
     */
    protected function getObjectsLinks()
    {
        foreach ($this->url as $url) {
            self::$page = 0;
            $this->collectObjects($this->getPagedUrl($url));
        }
    }

    /**
     * Collect objects.
     *
     * @param string $url URL.
     *
     * @return bool
     *
     * @throws CurlException
     */
    protected function collectObjects($url)
    {

    }

    /**
     * Parse category page.
     *
     * @throws CurlException
     * @throws InvalidParamException
     * @throws ObjectException
     */
    public function parse()
    {
        GlabsController::showMessage('Parsing category "' . $this->title . '"');
        /** @var \app\models\glabs\objects\BaseObject $object */
        foreach ($this->objects as $object) {
            if (in_array($object->getUrl(), $this->doneObjects, true)) {
                continue;
            }
            $this->i++;
            GlabsController::showMessage("\t" . $this->i . ') Parsing object "' . $object->getTitle() .
                '" (' . $object->getUrl() . ')');
            try {
                $object->parse();
                $this->doneObjects[] = $object->getUrl();
            } catch (ObjectException $e) {
                GlabsController::showMessage("\t\t" . 'Object skipped because of reason: ' . $e->getMessage());
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

        GlabsController::showMessage('');

        $done_count = count($this->doneObjects);
        if ($done_count < $this->needCount) {
            $this->count = $this->needCount - $done_count;
            $this->objects = [];
            $this->collectObjects($this->getPagedUrl(reset($this->url)));
            $this->parse();
        }
    }

    /**
     * Get paged URL.
     *
     * @param $url string URL.
     *
     * @return string
     */
    protected function getPagedUrl($url)
    {
        if (self::$page) {
            $url .= self::$pageParam . self::$page;
        }

        return $url;
    }

    /**
     * Check total object. If not, set it.
     *
     * @param Dom $dom Dom.
     *
     * @return bool
     */
    protected function checkTotalObjects($dom)
    {

    }
}