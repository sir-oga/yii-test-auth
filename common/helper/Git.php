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
class Git extends BaseObject
{
    const URL = 'https://api.github.com/users/';
    const AUTH = 'Authorization:token ';

    /**
     * @param string $username
     * @return array
     * @throws Exception
     */
    public static function getUser($username){
        $auth = self::AUTH . Yii::$app->params['gitAccessToken'];
        $headers = [
            $auth,
            'User-Agent: Awesome-Octocat-App'
        ];
        $url = self::URL . $username;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $errmsg   = curl_error($ch);
        curl_close($ch);
        if ($errno != 0) {
            throw new Exception($errmsg, $errno);
        }

        return self::handleResponse($response);
    }

    /**
     * @param $responseJson
     * @return array|null
     * @throws Exception
     */
    public static function handleResponse($responseJson){
        $response = json_decode($responseJson, true);
        if (!$response) return null;
        $email = $response['email'] ?? null;
        $location = $response['location'] ?? null;
        if (!$email) return null;
        $weather = Weather::getWeather($location);
        if (!$weather) {
            $locationArray = explode(',', $location);
            $locationCity = $locationArray[0] ?? null;
            $weather = Weather::getWeather($locationCity);
        }
        return [
            'email' => $email,
            'location' => $location,
            'weather' => $weather,
        ];
    }

}