<?php

namespace app\models\glabs\sites;

use app\models\glabs\categories\Category;
use app\models\glabs\ProxyCurl;
use PHPHtmlParser\Dom;

/**
 * Craigslist site.
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class Craigslist extends BaseSite
{
    /**
     * URL.
     *
     * @var string
     */
    private $url = 'http://losangeles.craigslist.org';

    protected function getCategoriesLinks($count)
    {
        $dom = new Dom();
        $dom->loadFromUrl($this->url, [], new ProxyCurl());
        $links = $dom->find('#center')[0]->find('a');

        /* @var \PHPHtmlParser\Dom\AbstractNode $link */
        foreach ($links as $link) {
            if ($count && count($this->categories) >= $count) {
                break;
            }
            $href = $link->getAttribute('href');
            if ('/forums' === $href || false !== strpos($href, 'https://forums.')) {
                continue;
            }
            parent::$doneCategories++;
            parent::progress();
            $this->categories[] = new Category($this->url . $href, $link->find('span')->text(), $count);
        }

        parent::getCategoriesLinks($count);

        return true;
    }
}
