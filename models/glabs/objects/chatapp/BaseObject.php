<?php

namespace app\models\glabs\objects\chatapp;

use app\commands\GlabsController;
use app\models\glabs\TransportZoheny;
use app\models\glabs\TransportException;
use app\models\glabs\objects\BaseObject as Base;
use app\models\glabs\objects\ObjectException;
use Faker\Factory;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\CurlException;

/**
 * Base class of chat objects.
 *
 * @package    glabs
 * @author     Nikolaj Rudakov <nnrudakov@gmail.com>
 * @copyright  2016
 */
class BaseObject extends Base
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var integer
     */
    protected $phonenumber;

    /**
     * @var string
     */
    protected $gender;

    /**
     * @var string
     */
    protected $birthday;

    /**
     * @var string
     */
    protected $promocode = '';

    /**
     * @var string
     */
    protected $aboutme;

    /**
     * @var Factory
     */
    protected static $faker;

    /**
     * @inheritdoc
     */
    public function __construct($url, $title, $categoryId, $type)
    {
        self::$faker = Factory::create();
        parent::__construct($url, $title, $categoryId, $type);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'name'        => $this->name,
            'username'    => $this->username,
            'password'    => $this->password,
            'email'       => $this->email,
            'phonenumber' => $this->phonenumber,
            'gender'      => $this->gender,
            'birthday'    => $this->birthday,
            'promocode'   => $this->promocode
        ];
    }

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
        try {
            self::$dom->loadFromUrl($this->url, [], GlabsController::$curl);
        } catch (CurlException $e) {
            if (false === strpos($e->getMessage(), 'timed out') ) {
                GlabsController::showMessage(' ...trying again', false);
                throw new CurlException($e->getMessage());
            }

            return $this->parse();
        }
        $this->setName();
        $this->setUsername();
        $this->setPrice();
        $this->setEmail();
        $this->setPhone();
        $this->setGender();
        $this->setBirthday();
        $this->setAboutme();
        $this->setImages();

        return true;
    }

    /**
     * Send object to Chatapp.mobi
     *
     * @param bool $isTest
     *
     * @return bool
     *
     * @throws TransportException
     */
    public function send($isTest = false)
    {
        return (new TransportZoheny($this))->send($isTest);
    }

    /**
     * Set name.
     */
    protected function setName()
    {
        $this->name = '';
    }

    /**
     * Set username.
     */
    protected function setUsername()
    {
        $this->username = '';
    }

    /**
     * Set password.
     */
    protected function setPassword()
    {
        $this->password = '';
    }

    /**
     * Set email.
     */
    protected function setEmail()
    {
        $this->email = '';
    }

    /**
     * Set gender.
     */
    protected function setGender()
    {
        $this->gender = '';
    }

    /**
     * Set birthday.
     */
    protected function setBirthday()
    {
        $this->birthday = '';
    }

    /**
     * Set aboutme.
     *
     * @throws ObjectException
     */
    protected function setAboutme()
    {
        $this->aboutme = $this->title . "\n\n" . $this->aboutme;
    }
}
