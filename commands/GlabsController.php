<?php
/**
 * @copyright Copyright (c) 2016 Nikolaj Rudakov
 * @license https://opensource.org/licenses/MIT
 */

namespace app\commands;

use app\models\glabs\ProxyCurl;
use app\models\glabs\sites\Backpage;
use app\models\glabs\sites\Craigslist;
use yii\console\Controller;

/**
 * Glabs catalog parser.
 *
 * @author Nikolaj Rudakov <nnrudakov@gmail.com>
 */

/** @noinspection LongInheritanceChainInspection */
class GlabsController extends Controller
{
    /**
     * IP connection.
     *
     * @var string
     */
    public static $ip;

    /**
     * Begin execution time.
     *
     * @var integer
     */
    private $startTime = 0;

    /**
     * @var bool
     */
    private static $quiet = false;

    /**
     * Entry point in parser.
     *
     * @param string  $site     Site to parse. Possible values:
     *                          <ul>
     *                              <li><code>craigslist</code> will parse http://losangeles.craigslist.org/ </li>
     *                              <li><code>backpage</code> will parse http://la.backpage.com/ </li>
     *                          </ul>
     * @param array   $category Categories comma separated.
     * @param integer $count    Count objects to parse.
     * @param bool    $quiet    No messages.
     *
     * @throws \InvalidArgumentException
     */
    public function actionIndex($site, array $category = ['cars+trucks'], $count = 1, $quiet = false)
    {
        if (!in_array($site, ['craigslist'], true)) {
            throw new \InvalidArgumentException('Wrong site "' . $site . '".');
        }

        self::$quiet = $quiet;
        self::showMessage('Starting parse "' . $site . '"');

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $site_model = 'craigslist' === $site ? new Craigslist($category, $count) : new Backpage($count);
        $site_model->parse();
        /*$o = new \app\models\glabs\objects\Object('url', 'title');
        $o->send();*/
    }

    public function beforeAction($action)
    {
        $this->startTime = microtime(true);
        //self::$ip = (new ProxyCurl())->get('https://api.ipify.org');

        return parent::beforeAction($action);
    }

    public function afterAction($action, $result)
    {
        file_put_contents(\Yii::getAlias('@runtime/logs/last_ip'),  self::$ip. "\n");
        $this->showTime();

        return parent::afterAction($action, $result);
    }

    /**
     * Show message in console.
     *
     * @param string $message Message.
     * @param bool   $lf      New line.
     */
    public static function showMessage($message, $lf = true)
    {
        if (!self::$quiet) {
            print $message . ($lf ? "\n" : '');
        }
    }

    /**
     * Show script time.
     */
    private function showTime()
    {
        self::$quiet = false;
        self::showMessage("\n" . 'Done in ' . sprintf('%f', microtime(true) - $this->startTime));
    }
}
