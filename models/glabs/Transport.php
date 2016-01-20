<?php

namespace app\models\glabs;

use app\models\glabs\objects\Object as Article;

/**
 * Send data to Zohney.com.
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class Transport
{
    /**
     * API URL.
     *
     * @var string
     */
    private static $url = 'http://211.233.159.68/bear1030/api/addproduct';

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
     * Transport constructor.
     *
     * @param Article $object
     */
    public function __construct(Article $object)
    {
        $this->object = $object;
    }

    /**
     * Send object.
     *
     * @return bool
     *
     * @throws TransportException
     */
    public function send()
    {
        $ch = curl_init(self::$url);

        if (!ini_get('open_basedir')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->prepareParams());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);

        $content = curl_exec($ch);
        if ($content === false) {
            // there was a problem
            $error = curl_error($ch);
            throw new TransportException('Error retrieving ' . $error);
        }

        echo $content;
        /*$content = json_decode($content, true);

        if (!$content['success']) {
            throw new TransportException('Error retrieving ' . $content['msg']);
        }*/

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
                $params['thumbnail1[]";filename="' . $image->getFilename()] = $image->getData();
            }
        }

        return $params;
    }
}
