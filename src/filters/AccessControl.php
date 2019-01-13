<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web\filters;

use yii\base\Action;
use yii\base\ActionFilter;
use yii\base\Application;
use yii\di\Instance;
use yii\web\ForbiddenHttpException;
use yii\web\User;

/**
 * AccessControl provides simple access control based on a set of rules.
 *
 * AccessControl is an action filter. It will check its [[rules]] to find
 * the first rule that matches the current context variables (such as user IP address, user role).
 * The matching rule will dictate whether to allow or deny the access to the requested controller
 * action. If no rule matches, the access will be denied.
 *
 * To use AccessControl, declare it in the `behaviors()` method of your controller class.
 * For example, the following declarations will allow authenticated users to access the "create"
 * and "update" actions and deny all other users from accessing these two actions.
 *
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         'access' => [
 *             '__class' => \yii\web\filters\AccessControl::class,
 *             'only' => ['create', 'update'],
 *             'rules' => [
 *                 // deny all POST requests
 *                 [
 *                     'allow' => false,
 *                     'verbs' => ['POST']
 *                 ],
 *                 // allow authenticated users
 *                 [
 *                     'allow' => true,
 *                     'roles' => ['@'],
 *                 ],
 *                 // everything else is denied
 *             ],
 *         ],
 *     ];
 * }
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AccessControl extends ActionFilter
{
    /**
     * @var callable a callback that will be called if the access should be denied
     * to the current user. This is the case when either no rule matches, or a rule with
     * [[AccessRule::$allow|$allow]] set to `false` matches.
     * If not set, [[denyAccess()]] will be called.
     *
     * The signature of the callback should be as follows:
     *
     * ```php
     * function ($rule, $action)
     * ```
     *
     * where `$rule` is the rule that denies the user, and `$action` is the current [[Action|action]] object.
     * `$rule` can be `null` if access is denied because none of the rules matched.
     */
    public $denyCallback;
    /**
     * @var array the default configuration of access rules. Individual rule configurations
     * specified via [[rules]] will take precedence when the same property of the rule is configured.
     */
    public $ruleConfig = ['__class' => AccessRule::class];
    /**
     * @var array a list of access rule objects or configuration arrays for creating the rule objects.
     * If a rule is specified via a configuration array, it will be merged with [[ruleConfig]] first
     * before it is used for creating the rule object.
     * @see ruleConfig
     */
    public $rules = [];


    protected $app;
    protected $user;

    public function __construct(Application $app, User $user)
    {
        $this->app = $app;
        $this->user = $user;
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * You may override this method to do last-minute preparation for the action.
     * @param Action $action the action to be executed.
     * @return bool whether the action should continue to be executed.
     */
    public function beforeAction(Action $action): bool
    {
        $request = $this->app->getRequest();
        /* @var $rule AccessRule */
        foreach ($this->rules as &$rule) {
            $rule = $this->ensureRule($rule);
            $allow = $rule->allows($action, $this->user, $request);
            if ($allow) {
                return true;
            } elseif ($allow === false) {
                return $this->deny($rule, $action);
            }
        }

        return $this->deny(null, $action);
    }

    protected function ensureRule($rule)
    {
        if (\is_object($rule)) {
            return $rule;
        }

        return $this->app->createObject(array_merge($this->ruleConfig, $rule));
    }

    protected function deny($rule, Action $action): bool
    {
        if (isset($rule->denyCallback)) {
            \call_user_func($rule->denyCallback, $rule, $action);
        } elseif ($this->denyCallback !== null) {
            \call_user_func($this->denyCallback, $rule, $action);
        } else {
            $this->denyAccess();
        }

        return false;
    }

    /**
     * Denies the access of the user.
     * The default implementation will redirect the user to the login page if he is a guest;
     * if the user is already logged, a 403 HTTP exception will be thrown.
     * @throws ForbiddenHttpException if the user is already logged in or in case of detached User component.
     */
    protected function denyAccess()
    {
        if ($this->user !== null && $this->user !== false && $this->user->getIsGuest()) {
            $this->user->loginRequired();
        } else {
            throw new ForbiddenHttpException($this->app->t('yii', 'You are not allowed to perform this action.'));
        }
    }
}
