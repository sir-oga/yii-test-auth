<?php
namespace api\modules\v1\models;

use Yii;
use yii\base\Model;

/**
 * Login form
 */
class LoginForm extends Model
{
    public $email;
    public $pwd;
    public $rememberMe = true;

    public $_user;


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['email', 'pwd'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['pwd', 'validatePassword'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->pwd)) {
                $this->addError($attribute, 'Incorrect email or password.');
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @param User|null $user
     * @return bool whether the user is logged in successfully
     * @throws \Exception
     * @throws \Throwable
     */
    public function login(User $user = null)
    {
        if ( !$this->validate()) return false;

        if (!$user) $user  = $this->getUser();
        /** @var UserToken $token */
        $token = $user->createToken($this->rememberMe ? 3600 * 24 * 30 * 3 : 60 * 20);
        $token->save();

        $successful = Yii::$app->user->login($user);
        if ( !$successful) return false;

        return true;
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = User::findByEmail($this->email);
        }

        return $this->_user;
    }
}
