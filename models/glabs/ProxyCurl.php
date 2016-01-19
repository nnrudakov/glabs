<?php
namespace app\models\glabs;

use PHPHtmlParser\CurlInterface;
use PHPHtmlParser\Exceptions\CurlException;

/**
 * Class Curl
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class ProxyCurl implements CurlInterface
{
    /**
     * A proxy curl implementation to get the content of the url.
     *
     * @param string $url
     *
     * @return string
     * @throws CurlException
     */
    public function get($url)
    {
        $ch = curl_init($url);

        if (!ini_get('open_basedir')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        sleep(2);
        $content = curl_exec($ch);
        if ($content === false) {
            // there was a problem
            $error = curl_error($ch);
            throw new CurlException('Error retrieving "' . $url . '" (' . $error . ')');
        }

        return $content;
    }
}
