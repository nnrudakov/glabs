<?php

namespace app\models\glabs\categories\chatapp;

use app\commands\GlabsController;
use app\models\glabs\categories\BaseCategory;
use app\models\glabs\objects\ObjectException;
use app\models\glabs\objects\chatapp\Backpage as Object;
use app\models\glabs\sites\BaseSite;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\CurlException;

/**
 * Class of categories of craigslist.org.
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class Backpage extends BaseCategory
{
    /**
     * @inheritdoc
     */
    public function __construct($url, $title, $categoryId, $type, $count)
    {
        parent::$pageParam = '&page=';
        parent::__construct($url, $title, $categoryId, $type, $count);
    }

    /**
     * @inheritdoc
     */
    protected function collectObjects($url)
    {
        $dom = new Dom();
        try {
            $dom->loadFromUrl($url, [], GlabsController::$curl);
        } catch (CurlException $e) {
            if (false === strpos($e->getMessage(), 'timed out') ) {
                throw new CurlException($e->getMessage());
            }
            if (false === strpos($e->getMessage(), '525') ) {
                throw new CurlException($e->getMessage());
            }
            GlabsController::showMessage(' ...trying again', false);
            return $this->collectObjects($url);
        }

        // end collect. no results
        if (false !== strpos($dom, 'No matches found.')) {
            return true;
        }

        $this->checkTotalObjects($dom);

        /* @var \PHPHtmlParser\Dom\AbstractNode $span */
        foreach ($dom->find('.cat') as $span) {
            if (count($this->objects) >= $this->count) {
                break;
            }

            /* @var \PHPHtmlParser\Dom\AbstractNode $link */
            if ($link = $span->find('a', 0)) {
                $href = $link->getAttribute('href');
                if (in_array($href, $this->collected, true)) {
                    continue;
                }
                $object = new Object($href, $link->text(), $this->categoryId, $this->type);
                $this->collected[] = $href;
                $this->objects[] = $object;
                BaseSite::$doneObjects++;
                BaseSite::progress();
            }
        }

        $collected_count = count($this->objects);
        if ($collected_count && $collected_count < $this->count) {
            $url = str_replace(self::$pageParam . self::$page, '', $url);
            self::$page += self::$page ? 1 : 2;
            return $this->collectObjects($this->getPagedUrl($url));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function checkTotalObjects($dom)
    {
        if (!$this->count) {
            $this->count = $this->needCount = 2500;
        }

        return true;
    }
}
