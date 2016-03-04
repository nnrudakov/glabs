<?php

namespace app\models\glabs\objects\chatapp;

use app\commands\GlabsController;
use app\models\glabs\faker\PhoneNumber;
use app\models\glabs\objects\ImageException;
use app\models\glabs\TransportChatapp;
use app\models\glabs\TransportException;
use app\models\glabs\objects\BaseObject as Base;
use app\models\glabs\objects\ObjectException;
use Faker\Factory;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\CurlException;
use PHPHtmlParser\Exceptions\EmptyCollectionException;
use yii\base\InvalidParamException;

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
     * Uncensored words.
     *
     * @var array
     */
    protected static $uncensored = [
        'sex', 'fuck', 'pussy', 'pusssy', 'escort service', 'servitude', 'licked', 'gfe', 'adult fun', 'empty house',
        'babe', 'doggy style', 'deep throat', 'condom', 'get love', 'cock', 'intimacy', 'curvy', 'anal', 'penis',
        'role playing', 'oral', 'bdsm', 'bbw', 'dick', 'chub', 'mwm', 'porn'
    ];

    /**
     * @inheritdoc
     */
    public function __construct($categoryUrl, $url, $title, $categoryId, $type)
    {
        self::$faker = Factory::create();
        self::$faker->addProvider(new PhoneNumber(self::$faker));
        parent::__construct($categoryUrl, $url, $title, $categoryId, $type);
        $this->isUncensored();
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
        $this->loadDom();

        $this->setName();
        $this->setUsername();
        $this->setPassword();
        $this->setEmail();
        $this->setPhone();
        $this->setGender();
        $this->setBirthday();
        $this->setAboutme();
        //$this->setImages();
        //print_r($this->toArray());die;
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
     * @throws InvalidParamException
     * @throws ImageException
     */
    public function send($isTest = false)
    {
        try {
            (new TransportChatapp($this))->send($isTest);
        } catch (TransportException $e) {
            $message = $e->getMessage();
            if ($message === 'Error retrieving: Username ' . $this->username . ' already taken') {
                GlabsController::showMessage('re-generate username and trying again... ', false);
                $this->setUsername();
                return $this->send($isTest);
            } elseif ($message === 'Error retrieving: Email "' . $this->email . '" already exist.') {
                GlabsController::showMessage('re-generate email and trying again... ', false);
                $this->setEmail();
                return $this->send($isTest);
            } else {
                throw new TransportException($message);
            }
        }

        return true;
    }

    /**
     * Set name.
     */
    protected function setName()
    {
        $this->name = self::$faker->name;
    }

    /**
     * Return username.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set username.
     */
    protected function setUsername()
    {
        $this->username = $this->generateUsername();
    }

    /**
     * Generate username.
     *
     * @return string
     */
    protected function generateUsername()
    {
        $username = self::$faker->unique()->userName;

        if (strlen($username) > 15) {
            return $this->generateUsername();
        }

        return $username;
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

    /**
     * Check unsencored words.
     *
     * @return bool
     *
     * @throws ObjectException
     */
    protected function isUncensored()
    {
        $text = strtolower($this->title);
        foreach (self::$uncensored as $word) {
            if (false !== strpos($text, $word)) {
                throw new ObjectException('Title contents uncensored words.');
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function setUploadedLink($id = null)
    {
        $this->uploadedLink = 'http://chatapp.mobi/app/profile/' . $this->username;
    }
}
