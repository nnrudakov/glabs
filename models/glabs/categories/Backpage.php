<?php

namespace app\models\glabs\categories;

use app\commands\GlabsController;
use app\models\glabs\objects\ObjectException;
use app\models\glabs\objects\Backpage as Object;
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
        self::$pageParam = '&page=';
        $url = array_map(function ($item) { return $item . '?layout=summary'; }, $url);
        parent::__construct($url, $title, $categoryId, $type, $count);
    }

    /**
     * @inheritdoc
     */
    protected function collectObjects($url)
    {
        if (!array_key_exists($url, $this->collectedCount)) {
            $this->collectedCount[$url] = 0;
        }
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
        foreach ($dom->find('.summaryHeader') as $span) {
            if ($this->collectedCount[$url] >= $this->count) {
                break;
            }

            /* @var \PHPHtmlParser\Dom\AbstractNode $link */
            if ($link = $span->find('a', 0)) {
                $href = $link->getAttribute('href');
                if (in_array($href, $this->collected, true)) {
                    continue;
                }
                $object = new Object($href, $link->text(), $this->categoryId, $this->type);
                try {
                    $object->setPrice();
                } catch (ObjectException $e) {
                    continue;
                }

                $this->collected[] = $href;
                $this->objects[] = $object;
                $this->collectedCount[$url]++;
                BaseSite::$doneObjects++;
                BaseSite::progress();
            }
        }

        if ($this->collectedCount[$url] && $this->collectedCount[$url] < $this->count) {
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
