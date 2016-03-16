<?php
/**
 * @copyright Copyright (c) 2016 Nikolaj Rudakov
 * @license https://opensource.org/licenses/MIT
 */

namespace app\commands;

use PHPHtmlParser\Exceptions\EmptyCollectionException;
use Yii;
use yii\base\InvalidParamException;
use yii\console\Controller;
use app\models\glabs\objects\ObjectException;
use app\models\glabs\ProxyCurl;
use app\models\glabs\TorCurl;
use app\models\glabs\TransportException;
use app\models\glabs\sites\Craigslist;
use app\models\glabs\sites\Backpage;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\CurlException;

/**
 * Glabs catalog parser.
 *
 * @author Nikolaj Rudakov <nnrudakov@gmail.com>
 */
class GlabsController extends Controller
{
    /**
     * Sites.
     *
     * @var array
     */
    public static $sites = ['craigslist', 'backpage'];

    /**
     * Curl class.
     *
     * @var ProxyCurl | TorCurl
     */
    public static $curl;

    /**
     * IP connection.
     *
     * @var string
     */
    public static $ip;

    /**
     * @var integer
     */
    public static $sentObjects = 0;

    /**
     * @var string
     */
    public static $currentAction;

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
     * Parse categories from sites.
     * @param string  $site       Site to parse. Possible values:
     *                            <ul>
     *                              <li><code>craigslist</code> will parse http://losangeles.craigslist.org/ </li>
     *                              <li><code>backpage</code> will parse http://la.backpage.com/ </li>
     *                            </ul>
     * @param array   $categories Categories comma separated.
     * @param integer $count      Count objects to parse.
     * @param string  $curl       cURL type. Possible values:
     *                            <ul>
     *                              <li><code>proxy</code> (default)</li>
     *                              <li><code>tor</code></li>
     *                            </ul>
     * @param string  $proxy      Proxy IP and port.
     * @param bool    $quiet      No messages in stdout.
     *
     * @throws InvalidParamException
     * @throws ObjectException
     * @throws CurlException
     */
    public function actionIndex($site, array $categories = [], $count = 0, $curl = 'proxy', $proxy = '', $quiet = false)
    {
        self::$currentAction = 'index';
        if (!in_array($site, self::$sites, true)) {
            throw new InvalidParamException('Wrong site "' . $site . '".');
        }

        self::$curl = 'proxy' === $curl ? new ProxyCurl() : new TorCurl();

        if ($proxy) {
            ProxyCurl::$proxy = $proxy;
        }

        self::$quiet = $quiet;
        self::showMessage('Starting to parse site "' . $site . '"');

        $site_model = 'craigslist' === $site ? new Craigslist($categories, $count) : new Backpage($categories, $count);
        $site_model->parse();
    }

    /**
     * Upload only one object.
     * @param string $site     Site to parse. Possible values:
     *                         <ul>
     *                          <li><code>craigslist</code> will parse http://losangeles.craigslist.org/ </li>
     *                          <li><code>backpage</code> will parse http://la.backpage.com/ </li>
     *                         </ul>
     * @param string $url      URL to object.
     * @param string $category Category.
     * @param string $curl     cURL type. Possible values:
     *                         <ul>
     *                          <li><code>proxy</code> (default)</li>
     *                          <li><code>tor</code></li>
     *                         </ul>
     * @param string $proxy    Proxy IP and port.
     *
     * @return bool
     *
     * @throws InvalidParamException
     * @throws ObjectException
     * @throws CurlException
     * @throws TransportException
     */
    public function actionObject($site, $url, $category, $curl = 'proxy', $proxy = '')
    {
        self::$currentAction = 'object';
        if (!in_array($site, self::$sites, true)) {
            throw new InvalidParamException('Wrong site "' . $site . '".');
        }

        self::$curl = 'proxy' === $curl ? new ProxyCurl() : new TorCurl();

        if ($proxy) {
            ProxyCurl::$proxy = $proxy;
        }

        $categories = 'craigslist' === $site ? Craigslist::CATEGORIES : Backpage::CATEGORIES;
        if (!array_key_exists($category, $categories)) {
            throw new InvalidParamException('Wrong category "' . $category . '".');
        }

        self::showMessage('Parsing object "' . $url . '"');

        $category = $categories[$category];
        $object = 'craigslist' === $site
            ? new \app\models\glabs\objects\Craigslist($url, 'none', $category['category_id'], $category['type'], 1)
            : new \app\models\glabs\objects\Backpage($url, 'none', $category['category_id'], $category['type'], 1);
        try {
            $object->parse();
            $object->setPrice();
            self::showMessage("\t" . 'Sending object... ', false);
            $object->send();
            self::showMessage('Success.');
        } catch (ObjectException $e) {
            self::showMessage("\t" . 'Cannot parse object: ' . $e->getMessage());
            return true;
        } catch (TransportException $e) {
            self::showMessage('Fail with message: "' . $e->getMessage() . '"');
            return true;
        }
        self::saveObjectsEmails($object);

        return true;
    }

    /**
     * Upload users.
     *
     * @param string  $url   Site Url.
     * @param integer $count Count objects to parse.
     * @param string  $curl  cURL type. Possible values:
     *                       <ul>
     *                          <li><code>proxy</code> (default)</li>
     *                          <li><code>tor</code></li>
     *                       </ul>
     * @param string  $proxy Proxy IP and port.
     *
     * @throws InvalidParamException
     * @throws CurlException
     * @throws ObjectException
     */
    public function actionChatapp($url, $count = 0, $curl = 'proxy', $proxy = '')
    {
        self::$currentAction = 'chatapp';
        if (false === strpos($url, 'craigslist') && false === strpos($url, 'backpage')) {
            throw new InvalidParamException('Wrong site.');
        }

        self::$curl = 'proxy' === $curl ? new ProxyCurl() : new TorCurl();

        if ($proxy) {
            ProxyCurl::$proxy = $proxy;
        }

        self::showMessage('Starting to parse "' . $url . '"');

        $site_model = false !== strpos($url, 'craigslist')
            ? new Craigslist(['Users'], $count, $url)
            : new Backpage(['Users'], $count, $url);
        $site_model->parse();
    }

    /**
     * Parse all chatapp sites.
     *
     * @return bool
     *
     * @throws CurlException
     * @throws ObjectException
     * @throws InvalidParamException
     */
    public function actionChatappAll()
    {
        self::$currentAction = 'chatapp-all';
        //$this->collectSites();
        $data = json_decode(file_get_contents(Yii::getAlias('@runtime/data/chatapp.json')), true);
        $sites = $data['sites'];
        $exclude = $data['exclude'];
        foreach ($sites as $i => $site) {
            $this->actionChatapp($site);
            unset($sites[$i]);
            $exclude[] = $site;
            $data = json_decode(file_get_contents(Yii::getAlias('@runtime/data/chatapp.json')), true);
            $data['sites'] = $sites;
            $data['exclude'] = $exclude;
            file_put_contents(Yii::getAlias('@runtime/data/chatapp.json'),  json_encode($data));
            sleep(mt_rand(3600, 5400));
        }

        return true;
    }

    /**
     * Mass mail.
     *
     * @param string  $category Category.
     * @param integer $count    Count objects to parse.
     * @param string  $curl     cURL type. Possible values:
     *                          <ul>
     *                            <li><code>proxy</code> (default)</li>
     *                            <li><code>tor</code></li>
     *                          </ul>
     * @param string  $proxy    Proxy IP and port.
     *
     * @throws InvalidParamException
     * @throws ObjectException
     * @throws CurlException
     */
    public function actionMassMail($category, $count = 0, $curl = 'proxy', $proxy = '')
    {
        self::$currentAction = 'mass-mail';
        self::$curl = 'proxy' === $curl ? new ProxyCurl() : new TorCurl();

        if ($proxy) {
            ProxyCurl::$proxy = $proxy;
        }

        $site_model = new Craigslist([$category], $count);
        $site_model->parse();
    }

    /**
     * Send mass mail.
     *
     * @param string  $curl     cURL type. Possible values:
     *                          <ul>
     *                            <li><code>proxy</code> (default)</li>
     *                            <li><code>tor</code></li>
     *                          </ul>
     * @param string  $proxy    Proxy IP and port.
     *
     * @throws InvalidParamException
     * @throws ObjectException
     * @throws CurlException
     * @throws EmptyCollectionException
     *
     * @return bool
     */
    public function actionSendMail($curl = 'proxy', $proxy = '')
    {
        self::$currentAction = 'sendmail';
        self::$curl = 'proxy' === $curl ? new ProxyCurl() : new TorCurl();
        if ($proxy) {
            ProxyCurl::$proxy = $proxy;
        }

        $links = [];
        $lines = file(Yii::getAlias('@runtime/email.csv'));
        foreach ($lines as $line) {
            list($link, $email) = explode(',', $line);
            $links[trim($email)] = $link;
        }

        $emails = file(Yii::getAlias('@runtime/uemail.csv'));
        $i = 1;
        foreach ($emails as $email) {
            $email = trim($email);
            if (!isset($links[$email])) {
                continue;
            }

            $link = $links[$email];
            $email = 'nnrudakov@gmail.com';
            // aguiang@gmail.com
            // arnel@guiang.com
            // arnel@glabs.la
            self::showMessage($i . ') Parsing object "' . $link . '"');
            if (false !== strpos($link, 'craigslist')) {
                $object = new \app\models\glabs\objects\massmail\Craigslist(
                    \app\models\glabs\sites\Craigslist::URL,
                    $link,
                    'none',
                    1,
                    ''
                );

                try {
                    $object->parse();
                } catch (ObjectException $e) {
                    self::showMessage("\t" . 'Cannot send email: ' . $e->getMessage());
                    $i++;
                    continue;
                }
            } else {
                $content = file_get_contents($link);
                if (!preg_match('/<h1>(.+)<\/h1>/', $content, $match)) {
                    $i++;
                    continue;
                }
                $object = new \app\models\glabs\objects\massmail\Craigslist(
                    \app\models\glabs\sites\Backpage::URL,
                    $link,
                    $match[1],
                    1,
                    ''
                );
                $object->setEmail($email);
            }

            try {
                self::showMessage("\t" . 'Sending email to ' . $email . '... ', false);
                $object->send();
                self::showMessage('Success.');
            } catch (ObjectException $e) {
                self::showMessage("\t" . 'Cannot send email: ' . $e->getMessage());
            } catch (TransportException $e) {
                self::showMessage('Fail with message: "' . $e->getMessage() . '"');
            }

            $i++;
            break;
        }
    }

    private function collectSites()
    {
        $old_data = json_decode(file_get_contents(Yii::getAlias('@runtime/data/chatapp.json')), true);
        $dom = new Dom();
        $dom->loadFromFile(Yii::getAlias('@runtime/sites.html'));
        $clinks = $blinks = [];
        $exclude = array_key_exists('exclude', $old_data)
            ? $old_data['exclude']
            : [
                'auburn.craigslist.org', 'bham.craigslist.org', 'dothan.craigslist.org', 'shoals.craigslist.org',
                'gadsden.craigslist.org', 'huntsville.craigslist.org'
            ];

        /* @var Dom\AbstractNode $link */
        foreach ($dom->find('a') as $link) {
            $href = $link->getAttribute('href');
            if (0 !== strpos($href, '//')) {
                continue;
            }
            $href = str_replace('/', '', $href);
            if (in_array($href, $exclude, true)) {
                continue;
            }
            $clinks[] = $href;
        }
        shuffle($clinks);
        $dom->loadFromFile(Yii::getAlias('@runtime/backpage.html'));
        /* @var Dom\AbstractNode $link */
        foreach ($dom->find('a') as $link) {
            $href = $link->getAttribute('href');
            $href = str_replace(['http:', '/'], '', $href);
            if (in_array($href, $exclude, true)) {
                continue;
            }
            $blinks[] = $href;
        }
        shuffle($blinks);
        $data = [
            'total_count' => (int) $old_data['total_count'],
            'current_site' => '',
            'sites' => array_merge($clinks, $blinks),
            'exclude' => $exclude
        ];
        file_put_contents(Yii::getAlias('@runtime/data/chatapp.json'),  json_encode($data));
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        self::$startTime = microtime(true);
        //self::$ip = (new ProxyCurl())->get('https://api.ipify.org');

        return parent::beforeAction($action);
    }

    /**
     * @inheritdoc
     */
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
        fputcsv($fp, array_merge([$object->getUrl()], array_unique($object->getEmails())));
        fclose($fp);

        return true;
    }

    /**
     * Save imported products links.
     *
     * @param \app\models\glabs\objects\BaseObject $object Object.
     *
     * @return bool
     *
     * @throws InvalidParamException
     */
    public static function saveProductsLinks($object)
    {
        $fp = fopen(Yii::getAlias('@runtime/products_' . (int) self::$startTime. '.csv'), 'a');
        fputcsv($fp, [$object->getUrl(), $object->getUploadedLink()]);
        fclose($fp);

        return true;
    }

    /**
     * Save imported profiles links.
     *
     * @param \app\models\glabs\objects\chatapp\BaseObject $object Object.
     *
     * @return bool
     *
     * @throws InvalidParamException
     */
    public static function saveUsersLinks($object)
    {
        $fp = fopen(Yii::getAlias('@runtime/profiles_' . (int) self::$startTime. '.csv'), 'a');
        fputcsv($fp, [$object->getUrl(), $object->getUploadedLink()]);
        fclose($fp);

        return true;
    }

    /**
     * Save zoheny sites status.
     *
     * @throws InvalidParamException
     */
    public static function saveZohenyStatus()
    {
        $data = json_decode(file_get_contents(Yii::getAlias('@runtime/data/zoheny.json')), true);
        $data['total_count']++;
        file_put_contents(Yii::getAlias('@runtime/data/zoheny.json'),  json_encode($data));
    }

    /**
     * Save chatapp sites status.
     *
     * @throws InvalidParamException
     */
    public static function saveChatappStatus()
    {
        $data = json_decode(file_get_contents(Yii::getAlias('@runtime/data/chatapp.json')), true);
        $data['total_count']++;
        file_put_contents(Yii::getAlias('@runtime/data/chatapp.json'),  json_encode($data));
    }

    /**
     * Save mass mail links and email.
     *
     * @param \app\models\glabs\objects\massmail\Craigslist $object Object.
     *
     * @return bool
     *
     * @throws InvalidParamException
     */
    public static function saveMassmailLinks($object)
    {
        $fp = fopen(Yii::getAlias('@runtime/massmail_' . (int) self::$startTime. '.csv'), 'a');
        fputcsv($fp, [$object->getUrl(), $object->getEmail()]);
        fclose($fp);

        return true;
    }
}
