<?php

namespace app\models\glabs;

use yii;
use app\models\glabs\objects\BaseObject;

/**
 * Send data to Zoheny.com.
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class TransportZoheny
{
    /**
     * API URL.
     *
     * @var string
     */
    private static $url = 'http://zoheny.com/api/addproduct';
    //private static $url = 'http://211.233.159.68/bear1030/api/addproduct';

    /**
     * Login.
     *
     * @var string
     */
    private static $loginemail = 'nbfbce@ymail.com';

    /**
     * Password.
     *
     * @var string
     */
    private static $password = '123456';

    /**
     * Object to send.
     *
     * @var Object
     */
    private $object;

    /**
     * TransportZoheny constructor.
     *
     * @param BaseObject $object
     */
    public function __construct(BaseObject $object)
    {
        $this->object = $object;
    }

    /**
     * Send object.
     *
     * @param bool $isTest
     *
     * @return bool
     *
     * @throws TransportException
     */
    public function send($isTest = false)
    {
        $ch = curl_init(static::$url);

        if (!ini_get('open_basedir')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }

        $params = $this->prepareParams();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);

        if ($isTest) {
            return true;
        }

        $response = curl_exec($ch);
        if ($response === false) {
            // there was a problem
            $error = curl_error($ch);
            curl_close($ch);
            $this->log($params, $error);
            throw new TransportException('Error retrieving: ' . $error);
        }

        curl_close($ch);
        $content = json_decode($response, true);

        if (!$content['success']) {
            $this->log($params, $content['msg'], $response);
            throw new TransportException('Error retrieving: ' . $content['msg']);
        }

        $this->object->setUploadedLink((int) $content['product_id']);

        return true;
    }

    /**
     * Prepare params.
     *
     * @return array $params
     */
    private function prepareParams()
    {
        $params = array_merge(
            ['loginemail' => self::$loginemail, 'password' => self::$password],
            $this->object->toArray(),
            [
                'thumbnail";filename="' . $this->object->getThumbnail()->getFilename() => $this->object->getThumbnail()->getData()
            ]
        );
        if ($this->object->getSubimage()) {
            foreach ($this->object->getSubimage() as $image) {
                $params['subimage[]";filename="' . $image->getFilename()] = $image->getData();
            }
        }

        return $params;
    }

    /**
     * @return string
     */
    private function userAgent()
    {
        //list of browsers
        $browser = [
            'Firefox',
            'Safari',
            'Opera',
            'Flock',
            'Internet Explorer',
            'Seamonkey',
            'Konqueror',
            'GoogleBot'
        ];
        //list of operating systems
        $os = [
            'Windows 3.1',
            'Windows 95',
            'Windows 98',
            'Windows 2000',
            'Windows NT',
            'Windows XP',
            'Windows Vista',
            'Redhat Linux',
            'Ubuntu',
            'Fedora',
            'AmigaOS',
            'OS 10.5'
        ];

        // randomly generate UserAgent
        return $browser[mt_rand(0, 7)] . '/' . mt_rand(1, 8) . '.' . mt_rand(0, 9) . ' (' .
            $os[mt_rand(0, 11)] . ' ' . mt_rand(1, 7) . '.' . mt_rand(0, 9) . '; en-US;)';
    }

    /**
     * Log request.
     *
     * @param array  $params
     * @param string $error
     * @param string $response
     */
    private function log(array $params, $error, $response = null)
    {
        /** @noinspection AlterInForeachInspection */
        foreach ($params as $k => &$v) {
            if (false !== strpos($k, 'thumbnail') || false !== strpos($k, 'subimage')) {
                $v = substr($v, 0, 30) . '...';
            }
        }

        Yii::error('==================== ' . time(), 'transport');
        Yii::error('Object URL: ' . $this->object->getUrl(), 'transport');
        Yii::error('API URL: ' . static::$url, 'transport');
        Yii::error('Parameters: ' . var_export($params, true), 'transport');
        if ($response) {
            Yii::error('Full response: ' . $response, 'transport');
        }
        Yii::error('Error: ' . $error, 'transport');
    }
}
