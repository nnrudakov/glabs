<?php

namespace app\models\glabs\categories;

use app\commands\GlabsController;
use app\models\glabs\objects\BaseObject;
use app\models\glabs\objects\ObjectException;
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
     * @var array
     */
    protected $collectedCount = [];

    /**
     * Category constructor.
     *
     * @param array   $url        Link.
     * @param string  $title      Title.
     * @param integer $categoryId Category ID.
     * @param string  $type       Type.
     * @param integer $count      Count objects;
     *
     * @throws CurlException
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
     * @throws ObjectException
     */
    protected function getObjectsLinks()
    {
        foreach ($this->url as $url) {
            self::$page = $this->collectedCount[$url] = 0;
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
     * @throws ObjectException
     */
    protected function collectObjects($url)
    {

    }

    /**
     * Get object.
     *
     * @param string  $url          Link.
     * @param string  $title        Title.
     * @param integer $categoryId   Category ID.
     * @param string  $categoryType Type.
     *
     * @return BaseObject
     *
     * @throws ObjectException
     */
    protected function getObjectModel($url, $title, $categoryId, $categoryType)
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
        GlabsController::showMessage("\n" . 'Parsing category "' . $this->title . '"');
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
                $object->removeFiles();
                GlabsController::$sentObjects++;
                GlabsController::showMessage('Success.');
            } catch (TransportException $e) {
                $object->removeFiles();
                GlabsController::showMessage('Fail with message: "' . $e->getMessage() . '"');
            }
            GlabsController::saveObjectsEmails($object);
            if ($this->isUsersTitle()) {
                /* @var \app\models\glabs\objects\chatapp\BaseObject $object */
                GlabsController::saveUsersLinks($object);
                GlabsController::saveChatappStatus();
            } else {
                GlabsController::saveProductsLinks($object);
                GlabsController::saveZohenyStatus();
            }
        }

        $done_count = count($this->doneObjects);
        if ($done_count < $this->needCount && count($this->objects)) {
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

    /**
     * Is it chatapp category.
     *
     * @return bool
     */
    protected function isUsersTitle()
    {
        return $this->title === 'Users';
    }
}
