<?php

namespace app\models\glabs\sites;

use app\models\glabs\categories\Backpage as Category;
use PHPHtmlParser\Dom;

/**
 * Backpage site.
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class Backpage extends BaseSite
{
    /**
     * URL.
     *
     * @var string
     */
    const URL = 'http://la.backpage.com';

    /**
     * Categories.
     *
     * @var array
     */
    const CATEGORIES = [
        'Antiques'          => ['type' => 'Sell', 'category_id' => 1,  'url' => ['/AntiquesForSale/']],
        'Appliances'        => ['type' => 'Sell', 'category_id' => 2,  'url' => ['/AppliancesForSale/']],
        'Apts / Housing'    => ['type' => 'Rent', 'category_id' => 31, 'url' => ['/ApartmentsForRent/']],
        //'Arts & Crafts'     => ['type' => 'Sell', 'category_id' => 3,  'url' => ['']],
        //'ATV/UTV/Snow'      => ['type' => 'Sell', 'category_id' => 4,  'url' => ['']],
        'Auto Parts'        => ['type' => 'Sell', 'category_id' => 5,  'url' => ['/AutoPartsForSale/']],
        //'Baby & Kid'        => ['type' => 'Sell', 'category_id' => 6,  'url' => ['']],
        'Beauty & Health'   => ['type' => 'Sell', 'category_id' => 7,  'url' => ['/HealthServices/']],
        //'Bikes'             => ['type' => 'Sell', 'category_id' => 8,  'url' => ['']],
        //'Boats'             => ['type' => 'Sell', 'category_id' => 9,  'url' => ['']],
        //'Books'             => ['type' => 'Sell', 'category_id' => 10, 'url' => ['']],
        'Business'          => ['type' => 'Sell', 'category_id' => 11, 'url' => ['/BusinessForSale/']],
        'Cars & Trucks'     => ['type' => 'Sell', 'category_id' => 12, 'url' => ['/AutosForSale/']],
        //'Cell Phones'       => ['type' => 'Sell', 'category_id' => 13, 'url' => ['']],
        'Clothes'           => ['type' => 'Sell', 'category_id' => 14, 'url' => ['/ClothingForSale/']],
        //'Collectibles'      => ['type' => 'Sell', 'category_id' => 15, 'url' => ['']],
        'Electronics'       => ['type' => 'Sell', 'category_id' => 16, 'url' => ['/ElectronicsForSale/']],
        'Farm & Garden'     => ['type' => 'Sell', 'category_id' => 17, 'url' => ['/Farm/']],
        'Furniture'         => ['type' => 'Sell', 'category_id' => 18, 'url' => ['/FurnitureForSale/']],
        //'Heavy Equipment'   => ['type' => 'Sell', 'category_id' => 19, 'url' => ['']],
        'Household'         => ['type' => 'Sell', 'category_id' => 20, 'url' => ['/Household/']],
        //'Housing'           => ['type' => 'Rent', 'category_id' => 38, 'url' => ['']],
        //'Jewelry'           => ['type' => 'Sell', 'category_id' => 21, 'url' => ['']],
        //'Materials'         => ['type' => 'Sell', 'category_id' => 22, 'url' => ['']],
        'Motorcycles'       => ['type' => 'Sell', 'category_id' => 23, 'url' => ['/MotorcyclesForSale/']],
        'Music Instr'       => ['type' => 'Sell', 'category_id' => 24, 'url' => ['/MusicEquipForSale/']],
        'Office'            => ['type' => 'Rent', 'category_id' => 32, 'url' => ['/CommercialForRent/']],
        //'Parking / Storage' => ['type' => 'Rent', 'category_id' => 33, 'url' => ['']],
        //'Photo & Video'     => ['type' => 'Sell', 'category_id' => 25, 'url' => ['']],
        'Rooms'             => ['type' => 'Rent', 'category_id' => 34, 'url' => ['/Roommates/']],
        //'RVs & Camping'     => ['type' => 'Sell', 'category_id' => 37, 'url' => ['']],
        'Sporting'          => ['type' => 'Sell', 'category_id' => 26, 'url' => ['/SportsEquipForSale/']],
        //'Sublets'           => ['type' => 'Rent', 'category_id' => 35, 'url' => ['']],
        'Tickets'           => ['type' => 'Sell', 'category_id' => 27, 'url' => ['/TicketsForSale/']],
        'Tools'             => ['type' => 'Sell', 'category_id' => 28, 'url' => ['/ToolsForSale/']],
        //'Toys & Games'      => ['type' => 'Sell', 'category_id' => 29, 'url' => ['']],
        'Vacation Rentals'  => ['type' => 'Rent', 'category_id' => 36, 'url' => ['/VacationForRent/']],
        //'Video Games'       => ['type' => 'Sell', 'category_id' => 30, 'url' => ['']]
    ];

    /**
     * @inheritdoc
     */
    public function __construct(array $categories, $count)
    {
        $this->url = self::URL;
        $this->categoriesList = self::CATEGORIES;
        parent::__construct($categories, $count);
    }

    /**
     * @inheritdoc
     */
    protected function setCategory($url, $title, $categoryId, $categoryType, $count)
    {
        $this->categories[] = new Category($url, $title, $categoryId, $categoryType, $count);
    }
}
