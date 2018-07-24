<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\LoginForm;
use api\modules\v1\models\User;
use common\helper\Git;
use common\helper\Mailing;
use Yii;
use yii\filters\Cors;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;

/**
 * Country Controller API
 *
 * @author Budi Irawan <deerawan@gmail.com>
 */
class UserController extends ActiveController
{
    public $modelClass     = User::class;

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(),
            [
                [
                    'class' => Cors::class,
                ],
            ],
            [
            'authenticator' => [
//                'only'        => [],
                'except' => ['login', 'create', 'git-send'],
            ],
        ]);
    }

    /**
     * @param $action
     * @return bool
     * @throws ForbiddenHttpException
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        $result = parent::beforeAction($action);

        /** @var User $user */
        $user = Yii::$app->user->identity;
//        if (!$user) throw new NotFoundHttpException("USER NOT FOUND");
//        if ($user) $user->refreshActivityTime();
        if ($user && in_array($user->status, [User::STATUS_DELETED])) {

            throw new ForbiddenHttpException(Yii::t('app', 'Restrict access.'));
        }

        return $result;
    }
    /**
     * @inheritdoc
     */
    public function afterAction($action, $result)
    {

        return parent::afterAction($action, $result);
    } // end beforeAction()

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create']);
        unset($actions['index']);
        unset($actions['update']);
        unset($actions['view']);
        unset($actions['delete']);

    } // end actions()

    /**
     * Generate the access token
     * @return LoginForm | array
     * @throws ForbiddenHttpException
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     */
    public function actionLogin()
    {
        $model             = new LoginForm();
        $model->attributes = Yii::$app->getRequest()->getBodyParams();

        $this->checkAccess('signin', $model);

        if ($model->login()) {
            return $model->_user;
        }

        return $model;
    }

    /**
     * @inheritdoc
     */
    public function actionCreate()
    {
        $model = new User(['scenario' => User::SCENARIO_CREATE]);
        $model->load(Yii::$app->request->bodyParams, '');
        $model->avatar_image = $model->avatar_image ?: UploadedFile::getInstanceByName('avatar_image');

        $this->checkAccess('create', $model);
        if ($model->save()) {
            return [
                'token' => Yii::$app->user->identity->access_token ?? null,
                'avatar' => [
                    'url'  => $model->getImagePath($model->avatar),
                    'prev' => $model->getImagePath($model->avatar, true, true),
                ],
            ];

        } elseif ( !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }

        return $model;
    }

    /**
     * @inheritdoc
     */
    public function actionGitSend()
    {
        /** @var User $model */
        $model = new $this->modelClass;
//        $userNamesRaw = Yii::$app->request->post('user_names');
//        $text = Yii::$app->request->post('text');
//        if (!$userNamesRaw) $model->addErrors(['user_names' => 'user_names can not be empty!']);
//        if (!$text) $model->addErrors(['text' => 'text can not be empty!']);
//        if ($model->hasErrors()) return $model;
//        $userNames = json_decode($userNamesRaw, true);
//        if (!$userNames) $userNames = explode(',' , $userNamesRaw);
//        $userData = [];
//        foreach ($userNames as $username){
//            $user = Git::getUser($username);
//            if (!$user) continue;
//            $userData[] = $user;
//        }
//        $mailingResult = Mailing::sendEmail($userData, $text);
        $mailingResult = Mailing::sendEmail([], 'asdfasdf');
        $result = [
//            'total_accounts' => count($userNames),
//            'accounts_with_email' => count($userData),
            'send_success' => $mailingResult,
//            'send_error' => count($userData) - $mailingResult,
        ];
        $result = array_merge($result, $mailingResult);

        return $result;
    }

    /**
     * @param $model User
     * @inheritdoc
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        switch ($action) {
            case 'create':
                $checkEmail = User::findByEmail($model->email);

                if ($checkEmail) {

                    throw new ForbiddenHttpException(Yii::t('app', 'Restrict access. This email already taken.'));
                }
                break;
            case 'login':

                break;
            case 'git-send':

                break;

            default:

                break;
        }
    } // end checkAccess()

}
