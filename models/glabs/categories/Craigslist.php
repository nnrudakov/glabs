<?php

namespace app\models\glabs\categories;

use app\commands\GlabsController;
use app\models\glabs\objects\ObjectException;
use app\models\glabs\objects\Craigslist as AdsObject;
use app\models\glabs\objects\chatapp\Craigslist as ChatObject;
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
class Craigslist extends BaseCategory
{
    /**
     * @inheritdoc
     */
    public function __construct(array $url, $title, $categoryId, $type, $count)
    {
        self::$pageParam = '?s=';
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
        $host = 'http://' . parse_url($url, PHP_URL_HOST);
        $dom = new Dom();
        try {
            $dom->loadFromUrl($url, [], GlabsController::$curl);
        } catch (CurlException $e) {
            if (false === strpos($e->getMessage(), 'timed out') ) {
                throw new CurlException($e->getMessage());
            }
            GlabsController::showMessage(' ...trying again', false);
            return $this->collectObjects($url);
        }

        if (false !== strpos($dom, 'This IP has been automatically blocked.')) {
            throw new CurlException('IP has been blocked.');
        }

        // end collect. no results
        if ($dom->find('#moon')[0]) {
            return true;
        }

        $this->checkTotalObjects($dom);

        /* @var \PHPHtmlParser\Dom\AbstractNode $span */
        foreach ($dom->find('.txt') as $span) {
            if ($this->isEnoughCollect()) {
                break;
            }

            /* @var \PHPHtmlParser\Dom\AbstractNode $link */
            if ($link = $span->find('a')[0]) {
                $href = $this->checkObjectLink($host, $link->getAttribute('href'));
                if (false === $href) {
                    continue;
                }
                $title = $link->text() ?: strip_tags($link->innerHtml());
                try {
                    $object = $this->getObjectModel($url, $href, $title, $this->categoryId, $this->type);
                    $object->setPrice($span);
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

        if (!$this->isEnoughCollect()) {
            $curl = GlabsController::$curl;
            $curl::$referer = $url;
            $url = str_replace([self::$pageParam . self::$page, '#list'], '', $url);
            self::$page += 100;
            return $this->collectObjects($this->getPagedUrl($url));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function getObjectModel($categoryUrl, $url, $title, $categoryId, $categoryType)
    {
        return $this->isUsersTitle()
            ? new ChatObject($categoryUrl, $url, $title, $categoryId, $categoryType)
            : new AdsObject($categoryUrl, $url, $title, $categoryId, $categoryType);
    }

    /**
     * @inheritdoc
     */
    protected function checkTotalObjects($dom)
    {
        if (!$this->count) {
            $this->count = 2500;
            /* @var \PHPHtmlParser\Dom\AbstractNode $total_count */
            if ($total_count = $dom->find('.totalcount', 0)) {
                $this->count = $this->needCount = (int) $total_count->text();
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function getPagedUrl($url)
    {
        if (self::$page) {
            $url .= self::$pageParam . self::$page;
        }

        $url .= '#list';

        return $url;
    }

    /**
     * Check object link.
     *
     * @param string $host Host.
     * @param string $link URL.
     *
     * @return string|bool
     */
    protected function checkObjectLink($host, $link)
    {
        if ($this->isUsersTitle() && (0 === strpos($link, '//') || false !== strpos($link, 'http:'))) {
            return false;
        }

        $link = $host . $link;

        if (in_array($link, $this->collected, true)) {
            return false;
        }

        return $link;
    }
}
