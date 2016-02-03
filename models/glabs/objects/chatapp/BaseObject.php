<?php

namespace app\models\glabs\objects\chatapp;

use app\commands\GlabsController;
use app\models\glabs\faker\PhoneNumber;
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
        self::$faker->addProvider(new PhoneNumber(self::$faker));
        parent::__construct($url, $title, $categoryId, $type);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $birthday = explode('-', $this->birthday);
        return [
            'name'            => $this->name,
            'username'        => $this->username,
            'password'        => $this->password,
            'email'           => $this->email,
            'phonenumber'     => $this->phonenumber,
            //'gender'          => $this->gender,
            'birthday[year]'  => $birthday[0],
            'birthday[month]' => $birthday[1],
            'birthday[day]'   => $birthday[2],
            //'promocode'       => $this->promocode,
            'aboutme'         => $this->aboutme
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
        $this->setPassword();
        $this->setEmail();
        $this->setPhone();
        $this->setGender();
        $this->setBirthday();
        $this->setAboutme();
        $this->setImages();
        print_r($this->toArray());die;
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
        //return (new TransportZoheny($this))->send($isTest);
    }

    /**
     * Set name.
     */
    protected function setName()
    {
        $this->name = self::$faker->name;
    }

    /**
     * Set username.
     */
    protected function setUsername()
    {
        $this->username = self::$faker->unique()->userName;
    }

    /**
     * Set password.
     */
    protected function setPassword()
    {
        $this->password = self::$faker->password();
    }

    /**
     * Set email.
     */
    protected function setEmail()
    {
        $this->email = self::$faker->unique()->email;
    }

    /**
     * @inheritdoc
     */
    protected function setPhone()
    {
        $this->phonenumber = self::$faker->unique()->phoneNumber;
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
     *
     * @param integer $years Years.
     */
    protected function setBirthday($years = 0)
    {
        $date = new \DateTime();
        $this->birthday = $years
            ? self::$faker->dateTimeBetween('-' . $years . ' years', ((int) $date->format('Y') - $years + 1) . '-01-01')
                ->format('Y-m-d')
            : self::$faker->date('Y-m-d', '2002-01-01');
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
