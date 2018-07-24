<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "user_token".
 *
 * @property int $id
 * @property int $user_id
 * @property string $access_token
 * @property string $user_agent
 * @property int $life_time
 * @property int $logged_at
 * @property int $last_activity_at
 * @property int $last_reset_at
 * @property int $token_updated_at
 *
 * @property User $user
 */
class UserToken extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_token';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'life_time', 'logged_at', 'last_activity_at', 'last_reset_at', 'token_updated_at'], 'integer'],
            [['access_token', 'user_agent'], 'string', 'max' => 32],
            [['access_token'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class'      => TimestampBehavior::class,
                'attributes' => [
                    static::EVENT_BEFORE_INSERT => ['logged_at', 'last_reset_at', 'last_activity_at', 'token_updated_at'],
                    static::EVENT_BEFORE_UPDATE => ['last_activity_at'],
                ],
            ],
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'access_token' => 'Access Token',
            'user_agent' => 'User Agent',
            'life_time' => 'Life Time',
            'logged_at' => 'Logged At',
            'last_activity_at' => 'Last Activity At',
            'last_reset_at' => 'Last Reset At',
            'token_updated_at' => 'Token Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * @return string
     */
    public static function getUserAgentHash()
    {
        return md5(Yii::$app->getRequest()->getUserAgent());
    }
}
