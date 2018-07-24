<?php
namespace api\modules\v1\models;

use common\models\UserToken as _UserToken;
use Yii;
use yii\db\Expression;
use yii\web\ServerErrorHttpException;

/**
 * Для создания токена НУЖНО использовать метод UserToken::createAccessToken().
 *
 * Важные моменты:
 * 1) Все методы работы с token'ом реализованы в этом классе. В классе User могут быть обертки для этих методов.
 * 2) У объектов типа User свойство byToken должно устанавливатся только в этом класса.
 *    методы getUser() и createAccessToken() усланавливают в объект User с которым работают поле byToken.
 *
 * @property bool      isNeedToReset
 * @property bool      isAlive
 * @property UserToken userActive
 * @property UserToken byToken
 */
class UserToken extends _UserToken
{
    /**
     * @var int Время по прошествию которого токен подлежит обновлению
     */
    public static $timeToReset = 60 * 60 * 24;
    /**
     * @var int Сколько раз можно обратится к токену который подлежит удалению, прежде чем ону удалится
     */
    public static $badTokenTakenLimit = 3;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->on(static::EVENT_BEFORE_DELETE, function ($event) {
            /** @var static $model */
            $model = $event->sender;
            /** START Check Token*/
            /** @var User $user */
            $user = Yii::$app->user->identity;
            $userAttributes = [];
            $byTokenAttributes = [];
            $tokenAttributes = [];
            if ($model)  $tokenAttributes = $model->attributes;
            if ($user) {
                $userAttributes = $user->attributes;
                $byTokenAttributes = $user->byToken->attributes;
            }
            $checkInfo = compact('userAttributes', 'byTokenAttributes', 'tokenAttributes');
            Yii::info('BEFORE DELETE TOKEN: '.json_encode($checkInfo), 'checkToken');
            Yii::info('MODEL ERRORS: '.json_encode($model->hasErrors()), 'checkToken');
            /** END Check Token */
            if ($model->hasErrors()) return;
        });
        parent::init();
    } // end init()

    /**
     * @return User
     */
    public function getUser()
    {
        /** @var User $user */
        $user = parent::getUser()->one();

        $user->byToken = $this;

        return $user;
    }

    /**
     * @param $accessToken
     *
     * @return null|UserToken
     * @throws \Exception
     * @throws \Throwable
     */
    public static function findByAccessToken($accessToken)
    {
        static::clearExpiredTokens();

        $model = static::findOne(['access_token' => $accessToken]);
        if ($model && ( !$model->isAlive || !$model->validateUserAgent())) {
            $model->delete();

            return null;
        }

        return $model;
    }

    /**
     * Создать token для аутентификации по api
     *
     * @param User $user
     * @param $lifeTime
     *
     * @return null|UserToken
     * @throws \Exception
     * @throws \Throwable
     */
    public static function createAccessToken(User $user, $lifeTime)
    {
        /** @var UserToken $token */
        $token = new static();

        $token->generateAccessToken();
        $token->fillUserAgent();
        $token->user_id   = $user->id;
        $token->life_time = $lifeTime;

        if ( !$token->insert()) {
            throw new ServerErrorHttpException('UserToken::createAccessToken can not create token by unknown reasons');
        }

        $user->byToken = $token;

        return $token;
    }
    /**
     * Обновить token для аутентификации по api для существующего user_agent
     *
     * @param integer                          $id
     * @param User $user
     * @param                                  $lifeTime
     *
     * @return null|UserToken
     * @throws \Exception
     */
    public static function updateAccessToken($id, User $user, $lifeTime)
    {
        /** @var UserToken $token */
        $token = static::findOne($id);

        $token->generateAccessToken();
        $token->fillUserAgent();
        $token->user_id   = $user->id;
        $token->life_time = $lifeTime;

        if ( !$token->save()) {
            throw new ServerErrorHttpException('UserToken::createAccessToken can not create token by unknown reasons');
        }

        $user->byToken = $token;

        return $token;
    }

    /**
     * Сгенерировать token для доступа по api
     * @throws \yii\base\Exception
     */
    public function generateAccessToken()
    {
        $this->access_token = Yii::$app->security->generateRandomString();
    }

    /**
     * Заполнить user_agent из заголовка
     */
    public function fillUserAgent()
    {
        $this->user_agent = self::getUserAgentHash();
    }

    /**
     * @return bool
     */
    public function validateUserAgent()
    {
        return $this->user_agent == self::getUserAgentHash();
    }

    /**
     * @return bool
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public static function deleteUserTokenDuplicate()
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $userAgent = self::getUserAgentHash();
        $modelTokens = self::find()
            ->andWhere(['user_agent' => $userAgent])
            ->andWhere(['user_id' => $user->id])
            ->andWhere(['<>','access_token',$user->access_token])
            ->all();
        $success = true;
        foreach ($modelTokens as $modelToken){
            $success = $success && $modelToken->delete();
        }
        return $success;
    }

    /**
     * @return bool нужно сбрасывать?
     */
    public function getIsNeedToReset()
    {
        $changeAt = $this->last_reset_at + static::$timeToReset;

        return $changeAt <= time();
    }

    /**
     * @return bool у токена еще есть время жизни?
     */
    public function getIsAlive()
    {
        $expires = $this->life_time + $this->logged_at;

        return $expires > time();
    }

    /**
     * Очистить устаревшие токены. Запускается зар в 100 вызовов
     */
    protected static function clearExpiredTokens()
    {
        if (mt_rand(0, 100) == 100) {
            static::deleteAll(new Expression("`life_time` + `logged_at` <= " . time()));
        }
    }
}
