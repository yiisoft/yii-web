<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web\filters;

use yii\base\ActionFilter;
use yii\helpers\Yii;
use yii\web\BadRequestHttpException;
use yii\web\Request;

/**
 * AjaxFilter allow to limit access only for ajax requests.
 *
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         [
 *             '__class' => 'yii\web\filters\AjaxFilter',
 *             'only' => ['index']
 *         ],
 *     ];
 * }
 * ```
 *
 * @author Dmitry Dorogin <dmirogin@ya.ru>
 *
 * @since 2.0.13
 */
class AjaxFilter extends ActionFilter
{
    /**
     * @var string the message to be displayed when request isn't ajax
     */
    public $errorMessage = 'Request must be XMLHttpRequest.';
    /**
     * @var Request the current request. If not set, the `request` application component will be used.
     */
    public $request;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if ($this->request === null) {
            $this->request = Yii::getApp()->getRequest();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        if ($this->request->getIsAjax()) {
            return true;
        }

        throw new BadRequestHttpException($this->errorMessage);
    }
}
