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
    private static $url = 'http://gis-iss.nr/index.php';//'http://211.233.159.68/bear1030/api/addproduct';

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
        $params = array_merge(
            ['loginemail' => self::$loginemail, 'password' => self::$password],
            $this->object->toArray()
        );

        $ch = curl_init(self::$url);

        if (!ini_get('open_basedir')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $content = curl_exec($ch);
        if ($content === false) {
            // there was a problem
            $error = curl_error($ch);
            throw new TransportException('Error retrieving ' . $error);
        }

        return true;
    }
}
