<?php

namespace app\models\glabs;

use app\models\glabs\objects\BaseObject;
use app\models\glabs\objects\ImageException;
use yii\base\InvalidParamException;

/**
 * Send data to Chatapp.mobi.
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class TransportChatapp
{
    /**
     * API URL.
     *
     * @var string
     */
    private static $url = 'http://chatapp.mobi/api';

    /**
     * Register API.
     *
     * @var string
     */
    private static $registerApi = '/auth/register';

    /**
     * Upload file.
     *
     * @var string
     */
    private static $uploadApi = '/api/upload_file';

    /**
     * Photo API.
     *
     * @var string
     */
    private static $photoApi = '/profile/upload_photo';

    /**
     * "About me" API.
     *
     * @var string
     */
    private static $aboutmeApi = '/profile/set_property';

    /**
     * Object to send.
     *
     * @var Object
     */
    private $object;

    /**
     * TransportChatapp constructor.
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
     * @throws InvalidParamException
     * @throws ImageException
     */
    public function send($isTest = false)
    {
        $params = $this->object->toArray();
        $aboutme = $params['aboutme'];
        unset($params['aboutme']);

        if ($isTest) {
            return true;
        }

        $response = $this->request(self::$url . self::$registerApi, $params);
        $params = ['token' => $response['data']['token']];
        $params['property'] = 'aboutme';
        $params['value']    = $aboutme;
        $this->request(self::$url . self::$aboutmeApi, $params);

        /*if ($this->object->getThumbnail()) {
            $photo = $this->object->getThumbnail();
            $response = $this->request(self::$url . self::$uploadApi, ['file' => new \CURLFile($photo->getLocalFile())]);
            $this->request(self::$url . self::$photoApi, ['token' => $response['data']['token'], 'photo' => $response]);
        }*/

        $this->object->setUploadedLink();

        return true;
    }

    /**
     * Do request.
     *
     * @param string $url    URL.
     * @param array  $params Params.
     *
     * @return array
     *
     * @throws TransportException
     */
    private function request($url, $params)
    {
        $ch = curl_init($url);

        if (!ini_get('open_basedir')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);

        $content = curl_exec($ch);
        if ($content === false) {
            // there was a problem
            $error = curl_error($ch);
            throw new TransportException('Error retrieving: ' . ($error ?: 'Connection error'));
        }

        $content = json_decode($content, true);

        if (isset($content['error'])) {
            throw new TransportException('Error retrieving: ' . $content['data']['message']);
        }

        return $content;
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
}
