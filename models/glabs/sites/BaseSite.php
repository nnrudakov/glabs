<?php

namespace app\models\glabs\sites;

use app\commands\GlabsController;
use app\models\glabs\categories\BaseCategory;
use app\models\glabs\objects\ObjectException;
use PHPHtmlParser\Exceptions\CurlException;
use yii\base\InvalidParamException;

/**
 * Sites interface.
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
abstract class BaseSite
{
    /**
     * URL.
     *
     * @var string
     */
    protected $url;

    /**
     * Categories.
     *
     * @var array
     */
    protected $categoriesList = [];

    /**
     * Categories.
     *
     * @var BaseCategory[]
     */
    protected $categories = [];

    /**
     * Income categories.
     *
     * @var array
     */
    protected $inCategories = [];

    /**
     * Progress bar.
     *
     * @var string
     */
    public static $progressFormat = "\rCollect categories: %d. Collect objects: %d.";

    /**
     * @var integer
     */
    public static $doneCategories = 0;

    /**
     * @var integer
     */
    public static $doneObjects = 0;

    /**
     * Init.
     *
     * @param array   $categories Categories.
     * @param integer $count      Count object per category.
     * @param string  $url        Site Url.
     *
     * @throws CurlException
     */
    public function __construct(array $categories, $count, $url = '') {
        $this->inCategories = $categories;
        $this->getCategoriesLinks($count);
        if (false === strpos($this->url, 'http')) {
            $this->url = 'http://' . $this->url;
        }
        $this->url = rtrim($this->url, '/');
    }

    /**
     * Parse action.
     *
     * @throws CurlException
     * @throws InvalidParamException
     * @throws ObjectException
     */
    public function parse()
    {
        foreach ($this->categories as $category) {
            $category->parse();
            GlabsController::showMessage('Successful sent objects: ' . GlabsController::$sentObjects);
        }
    }

    /**
     * Fill categories by name and URL.
     *
     * @param integer $count How many category objects to parse.
     *
     * @return bool
     *
     * @throws CurlException
     */
    protected function getCategoriesLinks($count)
    {
        $host = $this->url;
        $categories = $this->inCategories;

        if (!$categories || (isset($categories[0]) && $categories[0] === '')) {
            $categories = array_keys($this->categoriesList);
        }

        foreach ($categories as $title) {
            if (!array_key_exists($title, $this->categoriesList)) {
                continue;
            }
            self::$doneCategories++;
            self::progress();
            $category = $this->categoriesList[$title];
            $url = array_map(function ($item) use ($host) { return $host . $item; }, $category['url']);
            $this->setCategory($url, $title, $category['category_id'], $category['type'], $count);
        }

        GlabsController::showMessage("\n");

        return true;
    }

    /**
     * Set category.
     *
     * @param $url          string
     * @param $title        string
     * @param $categoryId   integer
     * @param $categoryType string
     * @param $count        integer
     *
     * @throws CurlException
     */
    protected function setCategory($url, $title, $categoryId, $categoryType, $count)
    {

    }

    /**
     * Show progress bar.
     */
    public static function progress()
    {
        GlabsController::showMessage(sprintf(self::$progressFormat, self::$doneCategories, self::$doneObjects), false);
    }
}
