<?php

namespace app\models\glabs\sites;

use app\models\glabs\categories\Craigslist as Category;
use app\models\glabs\categories\chatapp\Craigslist as ChatCategory;
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
    const URL = 'http://losangeles.craigslist.org';

    /**
     * Categories.
     *
     * @var array
     */
    const CATEGORIES = [
        'Antiques'          => ['type' => 'Sell', 'category_id' => 1,  'url' => ['/search/ata']],
        'Appliances'        => ['type' => 'Sell', 'category_id' => 2,  'url' => ['/search/ppa']],
        'Apts / Housing'    => ['type' => 'Rent', 'category_id' => 31, 'url' => ['/search/apa']],
        'Arts & Crafts'     => ['type' => 'Sell', 'category_id' => 3,  'url' => ['/search/ara']],
        'ATV/UTV/Snow'      => ['type' => 'Sell', 'category_id' => 4,  'url' => ['/search/sna']],
        'Auto Parts'        => ['type' => 'Sell', 'category_id' => 5,  'url' => ['/search/wta']],
        'Baby & Kid'        => ['type' => 'Sell', 'category_id' => 6,  'url' => ['/search/baa']],
        'Beauty & Health'   => ['type' => 'Sell', 'category_id' => 7,  'url' => ['/search/haa']],
        'Bikes'             => ['type' => 'Sell', 'category_id' => 8,  'url' => ['/search/bia']],
        'Boats'             => ['type' => 'Sell', 'category_id' => 9,  'url' => ['/search/boo']],
        'Books'             => ['type' => 'Sell', 'category_id' => 10, 'url' => ['/search/bka']],
        'Business'          => ['type' => 'Sell', 'category_id' => 11, 'url' => ['/search/bfa']],
        'Cars & Trucks'     => ['type' => 'Sell', 'category_id' => 12, 'url' => ['/search/cta']],
        'Cell Phones'       => ['type' => 'Sell', 'category_id' => 13, 'url' => ['/search/moa']],
        'Clothes'           => ['type' => 'Sell', 'category_id' => 14, 'url' => ['/search/cla']],
        'Collectibles'      => ['type' => 'Sell', 'category_id' => 15, 'url' => ['/search/cba']],
        'Electronics'       => ['type' => 'Sell', 'category_id' => 16, 'url' => ['/search/ela']],
        'Farm & Garden'     => ['type' => 'Sell', 'category_id' => 17, 'url' => ['/search/gra']],
        'Furniture'         => ['type' => 'Sell', 'category_id' => 18, 'url' => ['/search/fua']],
        'Heavy Equipment'   => ['type' => 'Sell', 'category_id' => 19, 'url' => ['/search/hva']],
        'Household'         => ['type' => 'Sell', 'category_id' => 20, 'url' => ['/search/hsa']],
        'Housing'           => ['type' => 'Rent', 'category_id' => 38, 'url' => ['/search/hhh']],
        'Jewelry'           => ['type' => 'Sell', 'category_id' => 21, 'url' => ['/search/jwa']],
        'Materials'         => ['type' => 'Sell', 'category_id' => 22, 'url' => ['/search/maa']],
        'Motorcycles'       => ['type' => 'Sell', 'category_id' => 23, 'url' => ['/search/mca']],
        'Music Instr'       => ['type' => 'Sell', 'category_id' => 24, 'url' => ['/search/msa']],
        'Office'            => ['type' => 'Rent', 'category_id' => 32, 'url' => ['/search/off']],
        'Parking / Storage' => ['type' => 'Rent', 'category_id' => 33, 'url' => ['/search/prk']],
        'Photo & Video'     => ['type' => 'Sell', 'category_id' => 25, 'url' => ['/search/pha']],
        //'Rooms'             => ['type' => 'Rent', 'category_id' => 34, 'url' => ['']],
        'RVs & Camping'     => ['type' => 'Sell', 'category_id' => 37, 'url' => ['/search/rva']],
        'Sporting'          => ['type' => 'Sell', 'category_id' => 26, 'url' => ['/search/sga']],
        'Sublets'           => ['type' => 'Rent', 'category_id' => 35, 'url' => ['/search/sub']],
        'Tickets'           => ['type' => 'Sell', 'category_id' => 27, 'url' => ['/search/tia']],
        'Tools'             => ['type' => 'Sell', 'category_id' => 28, 'url' => ['/search/tla']],
        'Toys & Games'      => ['type' => 'Sell', 'category_id' => 29, 'url' => ['/search/taa']],
        'Vacation Rentals'  => ['type' => 'Rent', 'category_id' => 36, 'url' => ['/search/vac']],
        'Video Games'       => ['type' => 'Sell', 'category_id' => 30, 'url' => ['/search/vga']],
        // chatapp users
        'Users'             => ['type' => '',     'category_id' => 0,  'url' => ['/search/m4w', '/search/w4m']]
    ];

    /**
     * @inheritdoc
     */
    public function __construct(array $categories, $count, $url = '')
    {
        $this->url = $url ?: self::URL;
        $this->categoriesList = self::CATEGORIES;
        parent::__construct($categories, $count);
    }

    /**
     * @inheritdoc
     */
    protected function setCategory($url, $title, $categoryId, $categoryType, $count)
    {
        $this->categories[] = 'Users' === $title
            ? new ChatCategory($url, $title, $categoryId, $categoryType, $count)
            : new Category($url, $title, $categoryId, $categoryType, $count);
    }
}
