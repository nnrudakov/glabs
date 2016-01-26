<?php
namespace app\models\glabs;

use app\commands\GlabsController;
use PHPHtmlParser\CurlInterface;
use PHPHtmlParser\Exceptions\CurlException;

/**
 * Class Curl
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class TorCurl implements CurlInterface
{
    /**
     * Connected URL.
     *
     * @var string
     */
    public static $connectedURL;

    /**
     * Localhost is a hostname that means this computer or this host.
     *
     * @var string
     */
    private $ip = '127.0.0.1';

    /**
     * SOCKS connections on port 9050.
     * Tor Browser listens on port 9150
     *
     * @var string
     *
     * @see https://www.torproject.org/docs/faq.html.en#TBBSocksPort
     */
    private $port = '9050';

    /**
     * Connect to the TOR server using password authentication.
     * <code>>tor --hash-password PASSWORD</code>
     *
     * @var string
     */
    private $authPass = '16:872860B76453A77D60CA2BB8C1A7042072093276A3D701AD684053EC4C';

    /**
     * Renew identity.
     *
     * @var string
     */
    private $command = 'signal NEWNYM';

    /**
     * Limits the maximum execution time.
     *
     * @var integer
     */
    private $timeout = 30;

    /**
     * A tor curl implementation to get the content of the url.
     *
     * @param string $url
     *
     * @return string
     * @throws CurlException
     */
    public function get($url)
    {
        $this->switchIdentity();

        $ch = curl_init($url);

        if (!ini_get('open_basedir')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }

        curl_setopt($ch, CURLOPT_PROXY, $this->ip . ':' . $this->port);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        sleep(mt_rand(3, 5));
        $content = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        self::$connectedURL = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        if (404 === $code){
            throw new CurlException('Content not found.');
        }

        if ($content === false) {
            // there was a problem
            $error = curl_error($ch);
            throw new CurlException('Error retrieving "' . $url . '" (' . $error . ')');
        }

        if (false !== strpos($content, 'This IP has been automatically blocked.')) {
            throw new CurlException('IP ' . GlabsController::$ip . ' has been blocked.');
        }

        return $content;
    }

    /**
     * @return bool
     */
    private function switchIdentity()
    {
        $fp = fsockopen($this->ip, $this->port, $error_num, $error_str, 10);
        if (!$fp) {
            echo "ERROR: $error_num : $error_str";
            return false;
        } else {
            fwrite($fp, "AUTHENTICATE \"" . $this->authPass . "\"\n");
            fread($fp, 512);
            fwrite($fp, $this->command . "\n");
            fread($fp, 512);
        }
    }

    /**
     * @return string
     */
    private function userAgent()
    {
        //list of browsers
        $TorAgentBrowser = [
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
        $TorAgentOS = [
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
        return $TorAgentBrowser[mt_rand(0, 7)] . '/' . mt_rand(1, 8) . '.' . mt_rand(0, 9) . ' (' .
            $TorAgentOS[mt_rand(0, 11)] . ' ' . mt_rand(1, 7) . '.' . mt_rand(0, 9) . '; en-US;)';
    }
}
