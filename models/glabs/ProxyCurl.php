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
     * @var string
     */
    public static $proxy = '';

    /**
     * Referer.
     *
     * @var string
     */
    public static $referer;

    /**
     * Connected URL.
     *
     * @var string
     */
    public $connectedURL;

    /**
     * Search engines.
     *
     * @var array
     */
    protected static $searchEgines = [
        'https://www.google.com/', 'https://www.google.co.uk/', 'http://www.daum.net/', 'http://www.eniro.se/',
        'http://www.naver.com/', 'http://www.yahoo.com/', 'http://www.msn.com/', 'http://www.bing.com/',
        'http://www.aol.com/', 'http://www.lycos.com/', 'http://www.ask.com/', 'http://www.altavista.com/',
        'http://search.netscape.com/', 'http://www.cnn.com/SEARCH/', 'http://www.about.com/', 'http://www.mamma.com/',
        'http://www.alltheweb.com/', 'http://www.voila.fr/', 'http://search.virgilio.it/', 'http://www.bing.com/',
        'http://www.baidu.com/', 'http://www.alice.com/', 'http://www.yandex.com/', '	http://www.najdi.org.mk/',
        'http://www.seznam.cz/', 'http://www.search.com/', 'http://www.wp.pl/', 'http://online.onetcenter.org/',
        'http://www.szukacz.pl/', 'http://www.yam.com/', 'http://www.pchome.com/', 'http://www.kvasir.no/',
        'http://sesam.no/', 'http://www.ozu.es/', 'http://www.terra.com/', 'http://www.mynet.com/',
        'http://www.ekolay.net/', 'http://www.rambler.ru/'
    ];

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

        if (self::$proxy) {
            curl_setopt($ch, CURLOPT_PROXY, self::$proxy);
        }

        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 40);
        curl_setopt($ch, CURLOPT_REFERER, $this->getReferrer());
        /*curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);*/

        sleep(mt_rand(10, 20));
        $content = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->connectedURL = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        if (404 === $code){
            throw new CurlException('Content not found.');
        }

        if ($content === false) {
            // there was a problem
            $error = curl_error($ch);
            throw new CurlException('Error retrieving "' . $url . '" (' . $error . ')');
        }

        if (false !== strpos($content, 'Error 525')) {
            throw new CurlException('Error in a source site.');
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

    /**
     * @return string
     */
    private function getReferrer()
    {
        if (self::$referer) {
            return self::$referer;
        }

        return self::$searchEgines[mt_rand(0, count(self::$searchEgines) - 1)];
    }
}
