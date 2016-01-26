<?php

namespace app\models\glabs\categories;

use app\models\glabs\objects\ObjectException;
use app\models\glabs\ProxyCurl;
use app\models\glabs\objects\Craigslist as Object;
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
    protected static $pageParam = '?s=';

    /**
     * @inheritdoc
     */
    protected function collectObjects($url)
    {
        $host = 'http://' . parse_url($url, PHP_URL_HOST);
        $dom = new Dom();
        $dom->loadFromUrl($url, [], new ProxyCurl());
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
            if (count($this->objects) >= $this->count) {
                break;
            }

            /* @var \PHPHtmlParser\Dom\AbstractNode $link */
            if ($link = $span->find('a')[0]) {
                $url = $host . $link->getAttribute('href');
                if (in_array($url, $this->collected, true)) {
                    continue;
                }
                $object = new Object($url, $link->text(), $this->categoryId, $this->type);
                try {
                    $object->setPrice($span);
                } catch (ObjectException $e) {
                    continue;
                }

                $this->collected[] = $url;
                $this->objects[] = $object;
                BaseSite::$doneObjects++;
                BaseSite::progress();
            }
        }

        $collected_count = count($this->objects);
        if ($collected_count && $collected_count < $this->count) {
            $url = str_replace(self::$pageParam . self::$page, '', $url);
            self::$page += 100;
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
            /* @var \PHPHtmlParser\Dom\AbstractNode $total_count */
            $total_count = $dom->find('.totalcount')[0];
            $this->count = $this->needCount = (int) $total_count->text();
        }

        return true;
    }
}
