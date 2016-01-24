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
        /*$proxies = file(\Yii::getAlias('@runtime/proxy.txt'));
        $proxy = $proxies[array_rand($proxies)];*/
        $ch = curl_init($url);

        if (!ini_get('open_basedir')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }

        //curl_setopt($ch, CURLOPT_PROXY, '103.245.197.78:8080');
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        sleep(mt_rand(3,4));
        $content = curl_exec($ch);
        if ($content === false) {
            // there was a problem
            $error = curl_error($ch);
            throw new CurlException('Error retrieving "' . $url . '" (' . $error . ')');
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