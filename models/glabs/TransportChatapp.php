<?php

namespace app\models\glabs;

use app\models\glabs\objects\BaseObject;

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
     * Photo API.
     *
     * @var string
     */
    private static $photoApi = '/api/profile/upload_photo';

    /**
     * "About me" API.
     *
     * @var string
     */
    private static $aboutmeApi = '/api/profile/set_property';

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
     */
    public function send($isTest = false)
    {
        // @todo loop for api urls and requests. if fail field must be regenerated

        if ($isTest) {
            return true;
        }

        return true;
    }

    /**
     * Do request.
     *
     * @param string $url    URL.
     * @param array  $params Params.
     *
     * @return bool
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
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);

        $content = curl_exec($ch);
        if ($content === false) {
            // there was a problem
            $error = curl_error($ch);
            throw new TransportException('Error retrieving ' . $error);
        }

        $content = json_decode($content, true);

        if ($content['error']) {
            throw new TransportException('Error retrieving ' . $content['data']['message']);
        }

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
            $this->object->toArray(),
            [
                //'thumbnail";filename="' . $this->object->getThumbnail()->getFilename() => $this->object->getThumbnail()->getData()
            ]
        );

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
}
