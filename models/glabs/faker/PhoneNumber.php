<?php

namespace app\models\glabs\faker;

class PhoneNumber extends \Faker\Provider\PhoneNumber
{
    protected static $formats = [
        '##########'
    ];
}
