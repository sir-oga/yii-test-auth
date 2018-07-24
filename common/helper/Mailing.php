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
}