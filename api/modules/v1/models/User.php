<?php
namespace api\modules\v1\models;

use common\helper\Imager;
use common\models\User as _User;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;


/**
 * User model
 */
class User extends _User
{

    public $avatar_image;
    public $loginModel;

    /**
     * Токен по которому был получен пользователь, если он был получен по токену
     * @var UserToken
     */
    public $byToken = null;

    const MAX_AVATAR_SIZE = 100 * 1024 * 1024; // 100 MB

    const SCENARIO_CREATE        = 'create';
    const LOGIN_STATUSES = [
        self::STATUS_ACTIVE,
    ];

    const PATH = '/images/avatar/';
    const PATH_SMALL = self::PATH . 'prev/';
    const WIDTH_SMALL = 750;
    const HEIGHT_SMALL = 750;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            ['password', 'string', 'min' => 6],
            [
                'avatar_image',
                'image',
                'maxSize'    => static::MAX_AVATAR_SIZE,
                // 'maxFiles'    => 1,
                'mimeTypes'  => ['image/jpg', 'image/jpeg', 'image/gif', 'image/png'],
                'extensions' => ['jpg', 'jpeg', 'gif', 'png'],
                /*'minWidth'    => 500, */
                'maxWidth'   => 15000,
                /*'minHeight'   => 500, */
                'maxHeight'  => 15000,
            ],
            ['birthday_str', 'date', 'format' => 'php:d.m.Y', 'timestampAttribute' => 'birthday'],
        ]);
    } // end rules()

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return ArrayHelper::merge(parent::scenarios(), [
            static::SCENARIO_CREATE => [
                'email',
                'pwd',
                'avatar_image',
            ],
        ]);
    } // end scenarios()

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->on(static::EVENT_AFTER_VALIDATE, function ($event) {
            /** @var User $model */
            $model = $event->sender;
            if ($model->hasErrors()) return;

            if ($model->pwd) // set password
            {
                $model->password_hash = Yii::$app->security->generatePasswordHash($model->pwd);
            }

           if ($model->avatar_image) // avatar loading
            {
                if ($model->avatar) {
                    $path = $this->getImagePath($model->avatar, false);

                }
                $model->avatar = $this->generateName($this->avatar_image);
                if ( !$this->avatar_image->saveAs($this->getImagePath($model->avatar, false), false ) )
                {
                    throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
                }
                $successResize = Imager::imageResize(
                    $this->getImagePath($model->avatar, false),
                    $this->getImagePath($model->avatar, false, true),
                    self::WIDTH_SMALL,
                    self::HEIGHT_SMALL
                );
                if (!$successResize){
                    Yii::error('ERROR RESIZE IMAGE!', 'serverInfo');
                }
                if (isset($path) && file_exists($path)) {
                    if( !unlink($path) ){
                        throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
                    }
                }
            }
        });
        $this->on(static::EVENT_AFTER_INSERT, function ($event) {
            $model = new LoginForm();

            $model->load(array_merge($this->toArray(), ['pwd' => $this->pwd]), '');

            if (!$model->login($this)) {
                Yii::warning('Can not login registered user. id:'.$this->id, 'z');
            }
            $this->loginModel = $model;
        });
        parent::init();
    } // end init()

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = array_diff(array_merge(parent::fields(),
            [
            ]),
            [
                'avatar',
                'auth_key',
                'password_hash',
                'password_reset_token',
                'type',
                'status',
                'created_at',
                'updated_at',
            ]
        );

        $fields['avatar'] = function ($model) {
            return [
                'url'  => $this->getImagePath($this->avatar),
                'prev' => $this->getImagePath($this->avatar, true, true),
            ];
        };
        $fields['token'] = function ($model) {
            return Yii::$app->user->identity->access_token ?? null;
        };

        return $fields;
    } // end fields()

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [

        ];
    } // end extraFields()

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'avatar_image' => 'Avatar Image',
        ]);
    } // end attributeLabels()

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $userToken = UserToken::findByAccessToken($token);
        if ( !$userToken) return null;

        $user = $userToken->user;
//        in_array($user->status, User::LOGIN_STATUSES)
        if (!in_array($user->status, User::LOGIN_STATUSES)) return null;

//        $user->userStat->add('access_activity_counter');
        return $user;
    }

    /**
     * @param $lifeTime
     *
     * @return UserToken|null|static
     * @throws \Exception
     * @throws \Throwable
     */
    public function createToken($lifeTime)
    {
        if ($this->isNewRecord) return null;
        $userAgent = UserToken::getUserAgentHash();
        $modelTokens = $this->getUserTokens()
            ->andWhere(['user_agent' => $userAgent]);
        $tokensCount = $modelTokens->count();
        $tokensAll = $modelTokens->all();
        if ($tokensCount) {
            foreach ($tokensAll as $key => $tokenOne) {
                /** @var $tokenOne UserToken */
                if ($key == 0) continue;
                $tokenOne->delete();
            }
            $token = UserToken::updateAccessToken($tokensAll[0]->id,$this, $lifeTime);
        } else {
            $token = UserToken::createAccessToken($this, $lifeTime);
        }

        return $token;
    }

    /**
     * @return null|string Токен по котором был идентифицирован пользователь
     */
    public function getAccess_token()
    {
        return $this->byToken ? $this->byToken->access_token : null;
    }

    /**
     * return full path
     *
     * @param $image
     * @param bool $isUrl
     * @param bool $small
     * @return string url path to image
     */
    public function getImagePath($image, $isUrl = true, $small = false)
    {
        if ( !$image) return null;
        $path = $small ? static::PATH_SMALL : static::PATH;
        if ($isUrl) {
            return Yii::getAlias('@webPath' . $path . $image);
        } else {
            return Yii::getAlias('@webRootImage' . $path . $image);
        }
    } // end getPath()

    /**
     * @param $image
     * @return string
     * @throws \yii\base\Exception
     */
    protected function generateName($image)
    {
        $ext = $image->extension;

        do // generate unique name
        {
            $name = Yii::$app->getSecurity()->generateRandomString(30) . ".$ext";
        } while (self::find()->andWhere(['avatar' => $name])->one()); // if exists - regenerate

        return $name;
    } // end generateName()
}
