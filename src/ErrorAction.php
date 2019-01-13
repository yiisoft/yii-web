<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use yii\base\Action;
use yii\exceptions\Exception;
use yii\exceptions\UserException;
use yii\helpers\Yii;

/**
 * ErrorAction displays application errors using a specified view.
 *
 * To use ErrorAction, you need to do the following steps:
 *
 * First, declare an action of ErrorAction type in the `actions()` method of your `SiteController`
 * class (or whatever controller you prefer), like the following:
 *
 * ```php
 * public function actions()
 * {
 *     return [
 *         'error' => ['__class' => \yii\web\ErrorAction::class,
 *     ];
 * }
 * ```
 *
 * Then, create a view file for this action. If the route of your error action is `site/error`, then
 * the view file should be `views/site/error.php`. In this view file, the following variables are available:
 *
 * - `$name`: the error name
 * - `$message`: the error message
 * - `$exception`: the exception being handled
 *
 * Finally, configure the "errorHandler" application component as follows,
 *
 * ```php
 * 'errorHandler' => [
 *     'errorAction' => 'site/error',
 * ]
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Dmitry Naumenko <d.naumenko.a@gmail.com>
 * @since 2.0
 */
class ErrorAction extends Action
{
    /**
     * @var string the view file to be rendered. If not set, it will take the value of [[id]].
     * That means, if you name the action as "error" in "SiteController", then the view name
     * would be "error", and the corresponding view file would be "views/site/error.php".
     */
    public $view;
    /**
     * @var string the name of the error when the exception name cannot be determined.
     * Defaults to "Error".
     */
    protected $defaultName;
    /**
     * @var string the message to be displayed when the exception message contains sensitive information.
     * Defaults to "An internal server error occurred.".
     */
    protected $defaultMessage;
    /**
     * @var string|false|null the name of the layout to be applied to this error action view.
     * If not set, the layout configured in the controller will be used.
     * @see \yii\base\Controller::$layout
     * @since 2.0.14
     */
    public $layout;

    /**
     * @var \Throwable the exception object, normally is filled on [[init()]] method call.
     * @see [[findException()]] to know default way of obtaining exception.
     * @since 2.0.11
     */
    protected $_exception;


    /**
     * @return string
     */
    public function getDefaultName(): string
    {
        return $this->defaultName ?? Yii::t('yii', 'Error');
    }

    /**
     * @param string $defaultName
     * @return ErrorAction
     */
    public function setDefaultName(string $defaultName): self
    {
        $this->defaultName = $defaultName;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultMessage(): string
    {
        return $this->defaultMessage ?? Yii::t('yii', 'An internal server error occurred.');
    }

    /**
     * @param string $defaultMessage
     * @return ErrorAction
     */
    public function setDefaultMessage(string $defaultMessage): self
    {
        $this->defaultMessage = $defaultMessage;

        return $this;
    }

    /**
     * @return \Throwable
     */
    public function getException(): \Throwable
    {
        if ($this->_exception === null) {
            $this->_exception = $this->findException();
        }

        return $this->_exception;
    }

    /**
     * Runs the action.
     *
     * @return string result content
     */
    public function run(): string
    {
        if ($this->layout !== null) {
            $this->controller->layout = $this->layout;
        }

        $this->app->getResponse()->setStatusCodeByException($this->getException());

        if ($this->app->getRequest()->getIsAjax()) {
            return $this->renderAjaxResponse();
        }

        return $this->renderHtmlResponse();
    }

    /**
     * Builds string that represents the exception.
     * Normally used to generate a response to AJAX request.
     * @return string
     * @since 2.0.11
     */
    protected function renderAjaxResponse(): string
    {
        return $this->getExceptionName() . ': ' . $this->getExceptionMessage();
    }

    /**
     * Renders a view that represents the exception.
     * @return string
     * @since 2.0.11
     */
    protected function renderHtmlResponse(): string
    {
        return $this->controller->render($this->view ?: $this->id, $this->getViewRenderParams());
    }

    /**
     * Builds array of parameters that will be passed to the view.
     * @return array
     * @since 2.0.11
     */
    protected function getViewRenderParams(): array
    {
        return [
            'name' => $this->getExceptionName(),
            'message' => $this->getExceptionMessage(),
            'exception' => $this->getException(),
        ];
    }

    /**
     * Gets exception from the [[yii\web\ErrorHandler|ErrorHandler]] component.
     * In case there is no exception in the component, treat as the action has been invoked
     * not from error handler, but by direct route, so '404 Not Found' error will be displayed.
     * @return \Throwable
     * @since 2.0.11
     */
    protected function findException(): \Throwable
    {
        if (($exception = $this->app->getErrorHandler()->exception) === null) {
            $exception = new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }

        return $exception;
    }

    /**
     * Gets the code from the [[exception]].
     * @return int
     * @since 2.0.11
     */
    protected function getExceptionCode(): int
    {
        $exception = $this->getException();

        if ($exception instanceof HttpException) {
            return $exception->statusCode;
        }

        return $exception->getCode();
    }

    /**
     * Returns the exception name, followed by the code (if present).
     *
     * @return string
     * @since 2.0.11
     */
    protected function getExceptionName(): string
    {
        $exception = $this->getException();

        if ($exception instanceof Exception) {
            $name = $exception->getName();
        } else {
            $name = $this->getDefaultName();
        }

        if ($code = $this->getExceptionCode()) {
            $name .= " (#$code)";
        }

        return $name;
    }

    /**
     * Returns the [[exception]] message for [[yii\exceptions\UserException]] only.
     * For other cases [[defaultMessage]] will be returned.
     * @return string
     * @since 2.0.11
     */
    protected function getExceptionMessage(): string
    {
        $exception = $this->getException();

        if ($exception instanceof UserException) {
            return $exception->getMessage();
        }

        return $this->getDefaultMessage();
    }
}
