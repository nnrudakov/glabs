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
    protected static $url = 'http://losangeles.craigslist.org';

    /**
     * Categories.
     *
     * @var array
     */
    protected static $categoriesList = [
        'antiques'       => ['url' => ['/search/ata'], 'category_id' => 1],
        'appliances'     => ['url' => ['/search/ppa'], 'category_id' => 2],
        'apts / housing' => ['url' => ['/search/apa'], 'category_id' => 31],
        'arts+crafts'    => ['url' => ['/search/ara'], 'category_id' => 3],
        'atv/utv/sno'    => ['url' => ['/search/sna'], 'category_id' => 4],
        'auto parts'     => ['url' => ['/search/wta', '/search/pta'], 'category_id' => 5],
        'baby+kid'       => ['url' => ['/search/baa'], 'category_id' => 6],
        'barter'         => ['url' => ['/search/bar'], 'category_id' => 7],
        'beauty+hlth'    => ['url' => ['/search/haa'], 'category_id' => 0],
        'bikes'          => ['url' => ['/search/bia', '/search/bip'], 'category_id' => 8],
        'boats'          => ['url' => ['/search/boo', '/search/bpa'], 'category_id' => 9],
        'books'          => ['url' => ['/search/bka'], 'category_id' => 10],
        'business'       => ['url' => ['/search/bfa'], 'category_id' => 11],
        'cars+trucks'    => ['url' => ['/search/cta'], 'category_id' => 12],
        'cds/dvd/vhs'    => ['url' => ['/search/ema'], 'category_id' => 0],
        'cell phones'    => ['url' => ['/search/moa'], 'category_id' => 13],
        'clothes+acc'    => ['url' => ['/search/cla'], 'category_id' => 14],
        'collectibles'   => ['url' => ['/search/cba'], 'category_id' => 15],
        'computers'      => ['url' => ['/search/sya', '/search/syp'], 'category_id' => 0],
        'electronics'    => ['url' => ['/search/ela'], 'category_id' => 16],
        'farm+garden'    => ['url' => ['/search/gra'], 'category_id' => 17],
        'free'           => ['url' => ['/search/zip'], 'category_id' => 0],
        'furniture'      => ['url' => ['/search/fua'], 'category_id' => 18],
        'garage sale'    => ['url' => ['/search/gms'], 'category_id' => 0],
        'general'        => ['url' => ['/search/foa'], 'category_id' => 0],
        'heavy equip'    => ['url' => ['/search/hva'], 'category_id' => 19],
        'household'      => ['url' => ['/search/hsa'], 'category_id' => 20],
        'jewelry'        => ['url' => ['/search/jwa'], 'category_id' => 21],
        'materials'      => ['url' => ['/search/maa'], 'category_id' => 22],
        'motorcycles'    => ['url' => ['/search/mca', '/search/mpa'], 'category_id' => 23],
        'music instr'    => ['url' => ['/search/msa'], 'category_id' => 24],
        'photo+video'    => ['url' => ['/search/pha'], 'category_id' => 25],
        'rvs+camp'       => ['url' => ['/search/rva'], 'category_id' => 37],
        'sporting'       => ['url' => ['/search/sga'], 'category_id' => 26],
        'tickets'        => ['url' => ['/search/tia'], 'category_id' => 27],
        'tools'          => ['url' => ['/search/tla'], 'category_id' => 28],
        'toys+games'     => ['url' => ['/search/taa'], 'category_id' => 29],
        'video gaming'   => ['url' => ['/search/vga'], 'category_id' => 30],
        'wanted'         => ['url' => ['/search/waa'], 'category_id' => 0]
    ];
/*
        <option value="38">Housing</option>
        <option value="32">Office</option>
        <option value="33">Parking / Storage</option>
        <option value="34">Rooms</option>
        <option value="35">Sublets</option>
        <option value="36">Vacation Rentals</option>
    </select>
  */
    protected function getCategoriesLinks($count)
    {
        $host = self::$url;
        $categories = $this->inCategories;

        if (!$categories) {
            $categories = array_keys(self::$categoriesList);
        }

        foreach ($categories as $title) {
            if (!array_key_exists($title, self::$categoriesList)) {
                continue;
            }
            parent::$doneCategories++;
            parent::progress();
            $category = self::$categoriesList[$title];
            $url = array_map(function ($item) use ($host) { return $host . $item; }, $category['url']);
            $this->categories[] = new Category($url, $title, $category['category_id'], $count);
        }

        parent::getCategoriesLinks($count);

        return true;
    }

    /**
     * @param int $count
     *
     * @return bool
     *
     * @deprecated
     */
    private function testGetCategoriesLinks($count)
    {
        /*$dom = new Dom();
        $dom->loadFromUrl(self::$url, [], new ProxyCurl());
        $links = $dom->find('#center')[0]->find('a');

        / @var \PHPHtmlParser\Dom\AbstractNode $link /
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
            $this->categories[] = new Category(self::$url . $href, $link->find('span')->text(), $count);
        }

        parent::getCategoriesLinks($count);*/

        return true;
    }
}
