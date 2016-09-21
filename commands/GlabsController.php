<?php
/**
 * @copyright Copyright (c) 2016 Nikolaj Rudakov
 * @license https://opensource.org/licenses/MIT
 */

namespace app\commands;

use yii;
use yii\base\InvalidParamException;
use yii\console\Controller;
use app\models\glabs\objects\ObjectException;
use app\models\glabs\ProxyCurl;
use app\models\glabs\TorCurl;
use app\models\glabs\TransportException;
use app\models\glabs\sites\Craigslist;
use app\models\glabs\sites\Backpage;
use app\models\glabs\objects\massmail\SimpleObject;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\CurlException;
use PHPHtmlParser\Exceptions\EmptyCollectionException;

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
     * Upload litings from file to zoheny.
     *
     * @param string $site        Site to parse. Possible values:
     *                            <ul>
     *                            <li><code>craigslist</code> will parse http://losangeles.craigslist.org/ </li>
     *                            <li><code>backpage</code> will parse http://la.backpage.com/ </li>
     *                            </ul>
     * @param string $category    Category.
     * @param string $categoryUrl Category URL.
     *
     * @return bool
     *
     * @throws InvalidParamException
     * @throws ObjectException
     * @throws CurlException
     * @throws TransportException
     * @throws EmptyCollectionException
     */
    public function actionObjects($site, $category, $categoryUrl = '')
    {
        if (!in_array($site, self::$sites, true)) {
            throw new InvalidParamException('Wrong site "' . $site . '".');
        }

        self::$curl = new ProxyCurl();

        $categories = 'craigslist' === $site ? Craigslist::CATEGORIES : Backpage::CATEGORIES;
        if (!array_key_exists($category, $categories)) {
            throw new InvalidParamException('Wrong category "' . $category . '".');
        }
        $category = $categories[$category];

        $urls = $wrong_urls = [];
        $fh = fopen(Yii::getAlias('@runtime/zoheny_urls.csv'), 'r');
        while (($line = fgets($fh)) !== false) {
            $url = str_replace(['/', '.', ':'], ['slash', 'dot', 'colon'], $line);
            $url = preg_replace('/\W/', '', $url);
            $url = str_replace(['slash', 'dot', 'colon'], ['/', '.', ':'], $url);
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $wrong_urls[] = $url;
            }
            preg_match('/(\d+)\.html/', $url, $matches);
            /** @noinspection UnSafeIsSetOverArrayInspection */
            if (!isset($matches[1])) {
                echo $line;
                return Controller::EXIT_CODE_ERROR;
            }
            $urls[$matches[1]] = ['url' => $url, 'email' => ''];
        }
        fclose($fh);

        if ($wrong_urls) {
            /** @noinspection ForgottenDebugOutputInspection */
            print_r($wrong_urls);
            return Controller::EXIT_CODE_ERROR;
        }

        $fh = fopen(Yii::getAlias('@runtime/zoheny_emails.csv'), 'r');
        while (($line = fgets($fh)) !== false) {
            preg_match('/-(\d+)@/', $line, $matches);
            /** @noinspection UnSafeIsSetOverArrayInspection */
            if (!isset($matches[1])) {
                echo $line;
                return Controller::EXIT_CODE_ERROR;
            }
            if (array_key_exists($matches[1], $urls)) {
                $urls[$matches[1]]['email'] = trim($line);
            }
        }
        fclose($fh);
        //return Controller::EXIT_CODE_NORMAL;

        $i = 0;
        $success_count = 0;
        foreach ($urls as $id => $line) {
            list($url, $email) = [trim($line['url']), trim($line['email'])];
            if (!$email) {
                continue;
            }
            $i++;
            self::showMessage($i . ') Parsing object "' . $url . '"');

            $object = 'craigslist' === $site
                ? new \app\models\glabs\objects\Craigslist($categoryUrl, $url, 'none', $category['category_id'], $category['type'])
                : new \app\models\glabs\objects\Backpage($categoryUrl, $url, 'none', $category['category_id'], $category['type']);
            $object->parseDescription = false;
            $object->data['description'] = $email;
            try {
                $object->parse();
                $object->setPrice();
                self::showMessage("\t" . 'Sending object... ', false);
                $object->send();
                self::showMessage('Success.');
                $success_count++;
            } catch (ObjectException $e) {
                self::showMessage("\t" . 'Cannot parse object: ' . $e->getMessage());
                continue;
            } catch (TransportException $e) {
                self::showMessage("\t" . 'Fail with message: "' . $e->getMessage() . '"');
                continue;
            } catch (EmptyCollectionException $e) {
                self::showMessage("\t" . 'Fail with message: "' . $e->getMessage() . '"');
                continue;
            }
            self::saveObjectsEmails($object);
        }

        self::showMessage('Success count: ' . $success_count . '.');

        return Controller::EXIT_CODE_NORMAL;
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
        //$this->collectSites();
        $data = json_decode(file_get_contents(Yii::getAlias('@runtime/data/chatapp.json')), true);
        $sites = $data['sites'];
        $exclude = $data['exclude'];
        foreach ($sites as $i => $site) {
            $this->actionChatapp($site);
            unset($sites[$i]);
            $exclude[] = $site;
            $data = json_decode(file_get_contents(Yii::getAlias('@runtime/bst/chatapp.json')), true);
            $data['sites'] = $sites;
            $data['exclude'] = $exclude;
            file_put_contents(Yii::getAlias('@runtime/bst/chatapp.json'),  json_encode($data));
            break;//sleep(mt_rand(3600, 5400));
        }

        return true;
    }

    /**
     * Collect mass mail.
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
    public function actionCollectMails($category, $count = 0, $curl = 'proxy', $proxy = '')
    {
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
     * @throws TransportException
     *
     * @return bool
     */
    public function actionSendMail($curl = 'proxy', $proxy = '')
    {
        self::$curl = 'proxy' === $curl ? new ProxyCurl() : new TorCurl();
        if ($proxy) {
            ProxyCurl::$proxy = $proxy;
        }

        $emails = file(Yii::getAlias('@runtime/massmail/emails.csv'));
        $i = 1;
        $success_count = 0;
        foreach ($emails as $email) {
            list($email, $subject) = explode(';', $email, 2);
            $email = str_replace(' ', '', strtolower(trim($email)));
            $subject = str_replace(';', '', trim($subject));
            $subject = trim(preg_replace(
                ['/"?reply[\s,]?info[\s,]?for[\s,]?posting[\s,]?"?\d+[\s?-]?[-\s]?/i', '/^\?\s/', '/^\-\s/'],
                '',
                $subject
            ));
            self::showMessage($i . ') Sendind to ' . $email . ' mail "'. $subject . '"', true, 'mail');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                self::showMessage("\tEmail $email is invalid. Skip.", true, 'mail');
                continue;
            }
            preg_match('/-(\d+)/', $email, $matches);
            $object_id = substr(md5($email.$subject), 0, 10);
            /** @noinspection UnSafeIsSetOverArrayInspection */
            if ($matches && isset($matches[1])) {
                $object_id = $matches[1];
            }
            //$email = 'nnrudakov@gmail.com';
            $object = new SimpleObject(['object_id' => $object_id, 'title' => $subject, 'email' => $email]);
            try {
                $object->send();
                self::showMessage("\t" . 'Success.', true, 'mail');
                $success_count++;
            } catch (ObjectException $e) {
                self::showMessage("\t" . 'Cannot send email: ' . $e->getMessage(), true, 'mail');
            } catch (TransportException $e) {
                self::showMessage("\t" . 'Fail with message: "' . $e->getMessage() . '"', true, 'mail');
            }
            $i++;
        }

        self::showMessage('Success count: ' . $success_count . '.');

        return Controller::EXIT_CODE_NORMAL;
    }

    public function actionJoinEmails()
    {
        $emails = file(Yii::getAlias('@runtime/emails/all_emails_durty.txt'));
        $emails = array_filter($emails);
        $emails = array_unique($emails);
        $fp = fopen(Yii::getAlias('@runtime/emails/all_emails.txt'), 'a');
        foreach ($emails as $email) {
            $email = trim($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                fwrite($fp, $email . "\n");
            }
        }
        fclose($fp);
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
        self::$currentAction = $this->action->id;
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
     * @param string $message     Message.
     * @param bool   $lf          New line.
     * @param string $logCategory Save in file.
     */
    public static function showMessage($message, $lf = true, $logCategory = '')
    {
        if (!self::$quiet) {
            print $message . ($lf ? "\n" : '');
        }
        if ($logCategory) {
            Yii::info($message, $logCategory);
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
        if (!$object->getUploadedLink()) {
            return false;
        }
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
        $data = json_decode(file_get_contents(Yii::getAlias('@runtime/bst/chatapp.json')), true);
        $data['total_count']++;
        file_put_contents(Yii::getAlias('@runtime/bst/chatapp.json'),  json_encode($data));
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
        fputcsv($fp, [
            $object->getObjectId(),
            $object->getUrl(),
            $object->getReplyUrl(),
            $object->getTitle(),
            $object->getEmail()
        ]);
        fclose($fp);

        return true;
    }
}
