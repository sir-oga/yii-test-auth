<?php
/**
 * Created by Sergey Estrin
 */
namespace common\helper;
use Exception;
use Yii;
use yii\base\BaseObject;


/**
 * Class DataHelper
 * @package common\components\helpers
 */
class Mailing extends BaseObject
{

    /**
     * @param array $userData
     * @param $text
     * @return int
     */
    public static function sendEmail($userData, $text){
        $from = 'test@mail.ua';
        $subject = 'Test Mail';
        $userData = self::getData();
        $successCount = 0;
        foreach ($userData as $data){
            $weatherBlock = $data['weather'] ? "<br> In {$data['location']} the weather is: {$data['weather']}" : '';
            $body = $text . $weatherBlock;
            $email = $data['email'];
            $email = 'sir-oga@mail.ru';
            $emailSend = Yii::$app->mailer->compose()
                ->setFrom($from)
                ->setTo($email)
                ->setSubject($subject)
                ->setHtmlBody($body)
                ->send();
            $successCount += $emailSend*1;
        }

        return $successCount;
    }

    public static function getData(){
        $data = '[
    {
        "email": "fabien@symfony.com",
        "location": "Lille",
        "weather": "Temperature: 26.58 C; Humidity: 35%; Pressure: 1014 mmHg; Wind Speed: 2.1 m/s"
    },
    {
        "email": "taylor@laravel.com",
        "location": "Little Rock, AR",
        "weather": "Temperature: 20.99 C; Humidity: 94%; Pressure: 1016 mmHg; Wind Speed: 2.1 m/s; Clouds: 20%"
    },
    {
        "email": "0x142857@gmail.com",
        "location": "Chengdu, China",
        "weather": "Temperature: 33 C; Humidity: 59%; Pressure: 1002 mmHg; Wind Speed: 1 m/s; Clouds: 20%"
    },
    {
        "email": "bebraw@gmail.com",
        "location": "Vienna, Austria",
        "weather": "Temperature: 28 C; Humidity: 51%; Pressure: 1014 mmHg; Wind Speed: 6.2 m/s; Clouds: 40%"
    },
    {
        "email": "nelson+github@dwyl.io",
        "location": "London",
        "weather": "Temperature: 26.9 C; Humidity: 47%; Pressure: 1014 mmHg; Wind Speed: 4.1 m/s; Clouds: 56%"
    },
    {
        "email": "alex@alexcrichton.com",
        "location": "San Francisco, CA",
        "weather": "Temperature: 14.5 C; Humidity: 93%; Pressure: 1017 mmHg; Wind Speed: 3.1 m/s; Clouds: 75%"
    },
    {
        "email": "me@jongleberry.com",
        "location": "Los Angeles, CA",
        "weather": "Temperature: 22.79 C; Humidity: 69%; Pressure: 1011 mmHg; Wind Speed: 0.85 m/s; Clouds: 1%"
    }
]';
        return json_decode($data, true);
    }
}