<?php
/**
 * @copyright Copyright (c) 2016 Nikolaj Rudakov
 * @license https://opensource.org/licenses/MIT
 */

namespace app\commands;

use Yii;
use yii\base\InvalidParamException;
use yii\console\Controller;
use app\models\glabs\objects\ObjectException;
use app\models\glabs\TorCurl;
use app\models\glabs\sites\Craigslist;
use app\models\glabs\sites\Backpage;
use PHPHtmlParser\Exceptions\CurlException;

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
    private static $startTime = 0;

    /**
     * @var bool
     */
    private static $quiet = false;

    /**
     * Entry point in parser.
     *
     * @param string  $site       Site to parse. Possible values:
     *                            <ul>
     *                              <li><code>craigslist</code> will parse http://losangeles.craigslist.org/ </li>
     *                              <li><code>backpage</code> will parse http://la.backpage.com/ </li>
     *                            </ul>
     * @param array   $categories Categories comma separated.
     * @param integer $count      Count objects to parse.
     * @param string  $proxy      Proxy IP and port.
     * @param bool    $quiet      No messages in stdout.
     *
     * @throws InvalidParamException
     * @throws ObjectException
     * @throws CurlException
     */
    public function actionIndex($site, array $categories = [], $count = 0, $proxy = '', $quiet = false)
    {
        if (!in_array($site, ['craigslist', 'backpage'], true)) {
            throw new InvalidParamException('Wrong site "' . $site . '".');
        }

        if ($proxy) {
            //TorCurl::$proxy = $proxy;
        }

        self::$quiet = $quiet;
        self::showMessage('Starting parse site "' . $site . '"');

        $site_model = 'craigslist' === $site ? new Craigslist($categories, $count) : new Backpage($categories, $count);
        $site_model->parse();
    }

    public function beforeAction($action)
    {
        self::$startTime = microtime(true);
        //self::$ip = (new ProxyCurl())->get('https://api.ipify.org');

        return parent::beforeAction($action);
    }

    public function afterAction($action, $result)
    {
        //file_put_contents(\Yii::getAlias('@runtime/logs/last_ip'),  self::$ip. "\n");
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
        self::showMessage("\n" . 'Done in ' . sprintf('%f', microtime(true) - self::$startTime));
    }

    /**
     * Set object's email into CSV file.
     *
     * @param \app\models\glabs\objects\BaseObject $object Object.
     *
     * @return bool
     *
     * @throws InvalidParamException
     */
    public static function saveObjectsEmails($object)
    {
        if (!$object->getEmails()) {
            return false;
        }

        $fp = fopen(Yii::getAlias('@runtime/emails_' . (int) self::$startTime. '.csv'), 'a');
        fputcsv($fp, array_merge([$object->getUrl()], $object->getEmails()));
        fclose($fp);

        return true;
    }
}
