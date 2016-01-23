<?php

namespace app\models\glabs\sites;

use app\commands\GlabsController;
use app\models\glabs\categories\Category;
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
     * Categories.
     *
     * @var Category[]
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
     */
    public function __construct($categories, $count) {
        $this->inCategories = $categories;
        $this->getCategoriesLinks($count);
    }

    /**
     * Parse action.
     *
     * @throws CurlException
     * @throws InvalidParamException
     */
    public function parse()
    {
        foreach ($this->categories as $category) {
            $category->parse();
        }
    }

    /**
     * Fill categories by name and URL.
     *
     * @param integer $count How many category objects to parse.
     */
    protected function getCategoriesLinks($count)
    {
        GlabsController::showMessage('');
    }

    /**
     * Show progress bar.
     */
    public static function progress()
    {
        GlabsController::showMessage(sprintf(self::$progressFormat, self::$doneCategories, self::$doneObjects), false);
    }
}
