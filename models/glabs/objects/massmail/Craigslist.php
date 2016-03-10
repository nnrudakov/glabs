<?php

namespace app\models\glabs\objects\massmail;

use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\CurlException;
use PHPHtmlParser\Exceptions\EmptyCollectionException;
use app\commands\GlabsController;
use app\models\glabs\objects\BaseObject;
use app\models\glabs\objects\ObjectException;

/**
 * Class of users of craigslist.org.
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class Craigslist extends BaseObject
{
    /**
     * @var string
     */
    protected $email;

    /**
     * Parse object page.
     *
     * @return bool
     *
     * @throws CurlException
     * @throws ObjectException
     */
    public function parse()
    {
        $this->setEmail();

        return true;
    }

    /**
     * Returns email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email.
     *
     * @return true
     *
     * @throws ObjectException
     * @throws EmptyCollectionException
     */
    protected function setEmail()
    {
        static $reply_url = '/reply/lax/cto/';
        preg_match('/(\d+)\.html/', $this->url, $matches);
        if (!isset($matches[1])) {
            throw new ObjectException('Has no email.');
        }
        $id = $matches[1];
        $host = 'http://' . parse_url($this->url, PHP_URL_HOST);

        try {
            $curl = GlabsController::$curl;
            $curl::$referer = $this->categoryUrl;
            self::$dom->loadFromUrl($host . $reply_url . $id, [], GlabsController::$curl);
        } catch (CurlException $e) {
            if (false !== strpos($e->getMessage(), 'timed out')) {
                GlabsController::showMessage(' ...trying again', false);
                return $this->setEmail();
            }

            throw new ObjectException($e->getMessage());
        } catch (EmptyCollectionException $e) {
            throw new ObjectException($e->getMessage());
        }

        /* @var \PHPHtmlParser\Dom\AbstractNode $postingbody */
        $postingbody = self::$dom->find('.anonemail', 0);
        if (!$postingbody) {
            throw new ObjectException('No email in content');
        }

        $this->email = $postingbody->text();

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new ObjectException('Has no email.');
        }

        return true;
    }
}
