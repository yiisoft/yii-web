<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use yii\base\Action;
use yii\base\InlineAction;
use yii\helpers\Url;
use yii\helpers\Yii;
use yii\exceptions\InvalidArgumentException;
use yii\view\ViewContextInterface;

/**
 * Controller is the base class of web controllers.
 *
 * For more details and usage information on Controller, see the [guide article on controllers](guide:structure-controllers).
 *
 * read-only.
 * @property View|\yii\web\View $view The view object that can be used to render views or view files.
 * @property string $viewPath The directory containing the view files for this controller.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Controller extends \yii\base\Controller implements ViewContextInterface
{
    /**
     * @var bool whether to enable CSRF validation for the actions in this controller.
     * CSRF validation is enabled only when both this property and [[\yii\web\Request::enableCsrfValidation]] are true.
     */
    public $enableCsrfValidation = true;
    /**
     * @var array the parameters bound to the current action.
     */
    public $actionParams = [];
    /**
     * @var null|string|false the name of the layout to be applied to this controller's views.
     * This property mainly affects the behavior of [[render()]].
     * Defaults to null, meaning the actual layout value should inherit that from [[module]]'s layout value.
     * If false, no layout will be applied.
     */
    public $layout;
    /**
     * @var View the view object that can be used to render views or view files.
     */
    private $_view;
    /**
     * @var string the root directory that contains view files for this controller.
     */
    private $_viewPath;

    /**
     * Renders a view and applies layout if available.
     *
     * The view to be rendered can be specified in one of the following formats:
     *
     * - [path alias](guide:concept-aliases) (e.g. "@app/views/site/index");
     * - absolute path within application (e.g. "//site/index"): the view name starts with double slashes.
     *   The actual view file will be looked for under the [[Application::viewPath|view path]] of the application.
     * - absolute path within module (e.g. "/site/index"): the view name starts with a single slash.
     *   The actual view file will be looked for under the [[Module::viewPath|view path]] of [[module]].
     * - relative path (e.g. "index"): the actual view file will be looked for under [[viewPath]].
     *
     * To determine which layout should be applied, the following two steps are conducted:
     *
     * 1. In the first step, it determines the layout name and the context module:
     *
     * - If [[layout]] is specified as a string, use it as the layout name and [[module]] as the context module;
     * - If [[layout]] is null, search through all ancestor modules of this controller and find the first
     *   module whose [[Module::layout|layout]] is not null. The layout and the corresponding module
     *   are used as the layout name and the context module, respectively. If such a module is not found
     *   or the corresponding layout is not a string, it will return false, meaning no applicable layout.
     *
     * 2. In the second step, it determines the actual layout file according to the previously found layout name
     *    and context module. The layout name can be:
     *
     * - a [path alias](guide:concept-aliases) (e.g. "@app/views/layouts/main");
     * - an absolute path (e.g. "/main"): the layout name starts with a slash. The actual layout file will be
     *   looked for under the [[Application::layoutPath|layout path]] of the application;
     * - a relative path (e.g. "main"): the actual layout file will be looked for under the
     *   [[Module::layoutPath|layout path]] of the context module.
     *
     * If the layout name does not contain a file extension, it will use the default one `.php`.
     *
     * @param string $view the view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * These parameters will not be available in the layout.
     * @return string the rendering result.
     * @throws InvalidArgumentException if the view file or the layout file does not exist.
     */
    public function render($view, $params = [])
    {
        $content = $this->getView()->render($view, $params, $this);
        return $this->renderContent($content);
    }

    /**
     * Renders a static string by applying a layout.
     * @param string $content the static string being rendered
     * @return string the rendering result of the layout with the given static string as the `$content` variable.
     * If the layout is disabled, the string will be returned back.
     * @since 2.0.1
     */
    public function renderContent($content)
    {
        $layoutFile = $this->findLayoutFile($this->getView());
        if ($layoutFile !== false) {
            return $this->getView()->renderFile($layoutFile, ['content' => $content], $this);
        }

        return $content;
    }

    /**
     * Renders a view without applying layout.
     * This method differs from [[render()]] in that it does not apply any layout.
     * @param string $view the view name. Please refer to [[render()]] on how to specify a view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     * @throws InvalidArgumentException if the view file does not exist.
     */
    public function renderPartial($view, $params = [])
    {
        return $this->getView()->render($view, $params, $this);
    }

    /**
     * Renders a view file.
     * @param string $file the view file to be rendered. This can be either a file path or a [path alias](guide:concept-aliases).
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     * @throws InvalidArgumentException if the view file does not exist.
     */
    public function renderFile($file, $params = [])
    {
        return $this->getView()->renderFile($file, $params, $this);
    }

    /**
     * Renders a view in response to an AJAX request.
     *
     * This method is similar to [[renderPartial()]] except that it will inject into
     * the rendering result with JS/CSS scripts and files which are registered with the view.
     * For this reason, you should use this method instead of [[renderPartial()]] to render
     * a view to respond to an AJAX request.
     *
     * @param string $view the view name. Please refer to [[render()]] on how to specify a view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     */
    public function renderAjax($view, $params = [])
    {
        return $this->getView()->renderAjax($view, $params, $this);
    }

    /**
     * Returns the view object that can be used to render views or view files.
     * The [[render()]], [[renderPartial()]] and [[renderFile()]] methods will use
     * this view object to implement the actual view rendering.
     * If not set, it will default to the "view" application component.
     * @return View|\yii\web\View the view object that can be used to render views or view files.
     */
    public function getView()
    {
        if ($this->_view === null) {
            $this->_view = $this->app->getView();
        }

        return $this->_view;
    }

    /**
     * Sets the view object to be used by this controller.
     * @param View|\yii\web\View $view the view object that can be used to render views or view files.
     */
    public function setView($view)
    {
        $this->_view = $view;
    }

    /**
     * Returns the directory containing view files for this controller.
     * The default implementation returns the directory named as controller [[id]] under the [[module]]'s
     * [[viewPath]] directory.
     * @return string the directory containing the view files for this controller.
     */
    public function getViewPath()
    {
        if ($this->_viewPath === null) {
            $this->_viewPath = $this->module->getViewPath() . DIRECTORY_SEPARATOR . $this->id;
        }

        return $this->_viewPath;
    }

    /**
     * Sets the directory that contains the view files.
     * @param string $path the root directory of view files.
     * @throws InvalidArgumentException if the directory is invalid
     * @since 2.0.7
     */
    public function setViewPath($path)
    {
        $this->_viewPath = $this->app->getAlias($path);
    }

    /**
     * Finds the applicable layout file.
     * @param View $view the view object to render the layout file.
     * @return string|bool the layout file path, or false if layout is not needed.
     * Please refer to [[render()]] on how to specify this parameter.
     * @throws InvalidArgumentException if an invalid path alias is used to specify the layout.
     */
    public function findLayoutFile($view)
    {
        $module = $this->module;
        if (is_string($this->layout)) {
            $layout = $this->layout;
        } elseif ($this->layout === null) {
            while ($module !== null && $module->layout === null) {
                $module = $module->module;
            }
            if ($module !== null && is_string($module->layout)) {
                $layout = $module->layout;
            }
        }

        if (!isset($layout)) {
            return false;
        }

        if (strncmp($layout, '@', 1) === 0) {
            $file = $this->app->getAlias($layout);
        } elseif (strncmp($layout, '/', 1) === 0) {
            $file = $this->app->getLayoutPath() . DIRECTORY_SEPARATOR . substr($layout, 1);
        } else {
            $file = $module->getLayoutPath() . DIRECTORY_SEPARATOR . $layout;
        }

        if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
            return $file;
        }
        $path = $file . '.' . $view->defaultExtension;
        if ($view->defaultExtension !== 'php' && !is_file($path)) {
            $path = $file . '.php';
        }

        return $path;
    }

    /**
     * Send data formatted as JSON.
     *
     * This method is a shortcut for sending data formatted as JSON. It will return
     * the [[Application::getResponse()|response]] application component after configuring
     * the [[Response::$format|format]] and setting the [[Response::$data|data]] that should
     * be formatted. A common usage will be:
     *
     * ```php
     * return $this->asJson($data);
     * ```
     *
     * @param mixed $data the data that should be formatted.
     * @return Response a response that is configured to send `$data` formatted as JSON.
     * @since 2.0.11
     * @see Response::$format
     * @see Response::FORMAT_JSON
     * @see JsonResponseFormatter
     */
    public function asJson($data)
    {
        $response = $this->app->getResponse();
        $response->format = Response::FORMAT_JSON;
        $response->data = $data;
        return $response;
    }

    /**
     * Send data formatted as XML.
     *
     * This method is a shortcut for sending data formatted as XML. It will return
     * the [[Application::getResponse()|response]] application component after configuring
     * the [[Response::$format|format]] and setting the [[Response::$data|data]] that should
     * be formatted. A common usage will be:
     *
     * ```php
     * return $this->asXml($data);
     * ```
     *
     * @param mixed $data the data that should be formatted.
     * @return Response a response that is configured to send `$data` formatted as XML.
     * @since 2.0.11
     * @see Response::$format
     * @see Response::FORMAT_XML
     * @see XmlResponseFormatter
     */
    public function asXml($data)
    {
        $response = $this->app->getResponse();
        $response->format = Response::FORMAT_XML;
        $response->data = $data;
        return $response;
    }

    /**
     * Binds the parameters to the action.
     * This method is invoked by [[\yii\base\Action]] when it begins to run with the given parameters.
     * This method will check the parameter names that the action requires and return
     * the provided parameters according to the requirement. If there is any missing parameter,
     * an exception will be thrown.
     * @param Action $action the action to be bound with parameters
     * @param array $params the parameters to be bound to the action
     * @return array the valid parameters that the action can run with.
     * @throws BadRequestHttpException if there are missing or invalid parameters.
     */
    public function bindActionParams($action, $params)
    {
        if ($action instanceof InlineAction) {
            $method = new \ReflectionMethod($this, $action->actionMethod);
        } else {
            $method = new \ReflectionMethod($action, 'run');
        }

        $args = [];
        $missing = [];
        $actionParams = [];
        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $params)) {
                if ($param->isArray()) {
                    $args[] = $actionParams[$name] = (array) $params[$name];
                } elseif ($param->getType() && $param->getType()->getName() === 'int') {
                    try {
                        $args[] = $actionParams[$name] = static::filterParamInt($params[$name]);
                    } catch (\TypeError $e) {
                        throw new BadRequestHttpException(Yii::t('yii', 'Invalid data received for parameter "{param}".', [
                            'param' => $name,
                        ]));
                    } catch (\Exception $e) {
                        throw new BadRequestHttpException(Yii::t('yii', 'Invalid data received for parameter "{param}".', [
                            'param' => $name,
                        ]));
                    }
                } elseif (!is_array($params[$name])) {
                    $args[] = $actionParams[$name] = $params[$name];
                } else {
                    throw new BadRequestHttpException(Yii::t('yii', 'Invalid data received for parameter "{param}".', [
                        'param' => $name,
                    ]));
                }
                unset($params[$name]);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $actionParams[$name] = $param->getDefaultValue();
            } else {
                $missing[] = $name;
            }
        }

        if (!empty($missing)) {
            throw new BadRequestHttpException(Yii::t('yii', 'Missing required parameters: {params}', [
                'params' => implode(', ', $missing),
            ]));
        }

        $this->actionParams = $actionParams;

        return $args;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeAction(Action $action): bool
    {
        if (parent::beforeAction($action)) {
            if ($this->enableCsrfValidation && $this->app->getErrorHandler()->exception === null && !$this->app->getRequest()->validateCsrfToken()) {
                throw new BadRequestHttpException(Yii::t('yii', 'Unable to verify your data submission.'));
            }

            return true;
        }

        return false;
    }

    /**
     * Redirects the browser to the specified URL.
     * This method is a shortcut to [[Response::redirect()]].
     *
     * You can use it in an action by returning the [[Response]] directly:
     *
     * ```php
     * // stop executing this action and redirect to login page
     * return $this->redirect(['login']);
     * ```
     *
     * @param string|array $url the URL to be redirected to. This can be in one of the following formats:
     *
     * - a string representing a URL (e.g. "http://example.com")
     * - a string representing a URL alias (e.g. "@example.com")
     * - an array in the format of `[$route, ...name-value pairs...]` (e.g. `['site/index', 'ref' => 1]`)
     *   [[Url::to()]] will be used to convert the array into a URL.
     *
     * Any relative URL that starts with a single forward slash "/" will be converted
     * into an absolute one by prepending it with the host info of the current request.
     *
     * @param int $statusCode the HTTP status code. Defaults to 302.
     * See <https://tools.ietf.org/html/rfc2616#section-10>
     * for details about HTTP status code
     *
     * @param boolean $checkAjax whether to specially handle AJAX requests. Defaults to false,
     * meaning a `Location` header will be sent, which when received as an AJAX response, may NOT cause
     * browser redirection. If this is true, the current request is an AJAX request, then calling this method
     * will cause the browser to redirect to the given URL.
     * Takes effect only when request header `X-Ie-Redirect-Compatibility` is absent.
     * @return Response the current response object
     */
    public function redirect($url, $statusCode = 302, $checkAjax = true)
    {
        return $this->app->getResponse()->redirect(Url::to($url), $statusCode, $checkAjax);
    }

    /**
     * Redirects the browser to the home page.
     *
     * You can use this method in an action by returning the [[Response]] directly:
     *
     * ```php
     * // stop executing this action and redirect to home page
     * return $this->goHome();
     * ```
     *
     * @return Response the current response object
     */
    public function goHome()
    {
        return $this->app->getResponse()->redirect($this->app->getHomeUrl());
    }

    /**
     * Redirects the browser to the last visited page.
     *
     * You can use this method in an action by returning the [[Response]] directly:
     *
     * ```php
     * // stop executing this action and redirect to last visited page
     * return $this->goBack();
     * ```
     *
     * For this function to work you have to [[User::setReturnUrl()|set the return URL]] in appropriate places before.
     *
     * @param string|array $defaultUrl the default return URL in case it was not set previously.
     * If this is null and the return URL was not set previously, [[Application::homeUrl]] will be redirected to.
     * Please refer to [[User::setReturnUrl()]] on accepted format of the URL.
     * @return Response the current response object
     * @see User::getReturnUrl()
     */
    public function goBack($defaultUrl = null)
    {
        return $this->app->getResponse()->redirect($this->app->getUser()->getReturnUrl($defaultUrl));
    }

    /**
     * Refreshes the current page.
     * This method is a shortcut to [[Response::refresh()]].
     *
     * You can use it in an action by returning the [[Response]] directly:
     *
     * ```php
     * // stop executing this action and refresh the current page
     * return $this->refresh();
     * ```
     *
     * @param string $anchor the anchor that should be appended to the redirection URL.
     * Defaults to empty. Make sure the anchor starts with '#' if you want to specify it.
     * @return Response the response object itself
     */
    public function refresh($anchor = '')
    {
        return $this->app->getResponse()->redirect($this->app->getRequest()->getUrl() . $anchor);
    }
    
    /**
     * Filtering request parameters for int type
     *
     * @param int $value Request parameter value
     * @return int Processing result
     */
    private static function filterParamInt(int $value): int
    {
        return $value;
    }
}
