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
class Weather extends BaseObject
{
    const URL = 'api.openweathermap.org/data/2.5/weather?q=';
    const API_KEY = '&APPID=';
    const KC = -273.15;

    /**
     * @param string $location
     * @return array
     * @throws Exception
     */
    public static function getWeather($location){
        if (!$location) return null;
        $apiId = self::API_KEY . Yii::$app->params['weatherApiKey'];
        $url = self::URL . $location . $apiId;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $errmsg   = curl_error($ch);
        curl_close($ch);
        if ($errno != 0) {
            throw new Exception($errmsg, $errno);
        }

        return self::handleResponse($response);
    }

    public static function handleResponse($responseJson){
        $response = json_decode($responseJson, true);
        if (!$response) return null;
        $code = $response['cod'] ?? null;
        if ($code != 200) return null;
        $main = $response['main'] ?? [];
        $wind = $response['wind'] ?? [];
        $clouds = $response['clouds'] ?? [];
        $temp = $main['temp'] ?? null;
        $humidity = $main['humidity'] ?? null;
        $pressure = $main['pressure'] ?? null;
        $speed = $wind['speed'] ?? null;
        $cloudsAll = $clouds['all'] ?? null;
        $weather = [
            $temp ? 'Temperature: ' . ( $temp + self::KC ) . ' C' : '',
            $humidity ? "Humidity: $humidity%" : '',
            $pressure ? "Pressure: $pressure mmHg" : '',
            $speed ? "Wind Speed: $speed m/s" : '',
            $cloudsAll ? "Clouds: $cloudsAll%" : '',
        ];
        $weather = array_diff($weather, ['']);

        return implode('; ', $weather);
    }

}