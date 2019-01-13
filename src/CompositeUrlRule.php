<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use yii\base\BaseObject;
use yii\di\Initiable;
use yii\helpers\Yii;
use yii\di\AbstractContainer;

/**
 * CompositeUrlRule is the base class for URL rule classes that consist of multiple simpler rules.
 *
 * @property null|int $createUrlStatus Status of the URL creation after the last [[createUrl()]] call. `null`
 * if rule does not provide info about create status. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
abstract class CompositeUrlRule extends BaseObject implements UrlRuleInterface, Initiable
{
    /**
     * @var UrlRuleInterface[] the URL rules contained in this composite rule.
     * This property is set in [[init()]] by the return value of [[createRules()]].
     */
    protected $rules = [];
    /**
     * @var int|null status of the URL creation after the last [[createUrl()]] call.
     * @since 2.0.12
     */
    protected $createStatus;


    /**
     * Creates the URL rules that should be contained within this composite rule.
     * @return UrlRuleInterface[] the URL rules
     */
    abstract protected function createRules(): array;


    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            AbstractContainer::configure($this, $config);
        }
        $this->init();
    }

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        $this->rules = $this->createRules();
    }

    /**
     * {@inheritdoc}
     */
    public function parseRequest(UrlManager $manager, Request $request)
    {
        foreach ($this->rules as $rule) {
            /* @var $rule UrlRule */
            $result = $rule->parseRequest($manager, $request);
            if (YII_DEBUG) {
                Yii::debug([
                    'rule' => method_exists($rule, '__toString') ? $rule->__toString() : get_class($rule),
                    'match' => $result !== false,
                    'parent' => self::class
                ], __METHOD__);
            }
            if ($result !== false) {
                return $result;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function createUrl(UrlManager $manager, string $route, array $params)
    {
        $this->createStatus = UrlRule::CREATE_STATUS_SUCCESS;
        $url = $this->iterateRules($this->rules, $manager, $route, $params);
        if ($url !== false) {
            return $url;
        }

        if ($this->createStatus === UrlRule::CREATE_STATUS_SUCCESS) {
            // create status was not changed - there is no rules configured
            $this->createStatus = UrlRule::CREATE_STATUS_PARSING_ONLY;
        }

        return false;
    }

    /**
     * Iterates through specified rules and calls [[createUrl()]] for each of them.
     *
     * @param UrlRuleInterface[] $rules rules to iterate.
     * @param UrlManager $manager the URL manager
     * @param string $route the route. It should not have slashes at the beginning or the end.
     * @param array $params the parameters
     * @return bool|string the created URL, or `false` if none of specified rules cannot be used for creating this URL.
     * @see createUrl()
     * @since 2.0.12
     */
    protected function iterateRules(array $rules, UrlManager $manager, string $route, array $params)
    {
        /* @var $rule UrlRule */
        foreach ($rules as $rule) {
            $url = $rule->createUrl($manager, $route, $params);
            if ($url !== false) {
                $this->createStatus = UrlRule::CREATE_STATUS_SUCCESS;
                return $url;
            }
            if (
                $this->createStatus === null
                || !method_exists($rule, 'getCreateUrlStatus')
                || $rule->getCreateUrlStatus() === null
            ) {
                $this->createStatus = null;
            } else {
                $this->createStatus |= $rule->getCreateUrlStatus();
            }
        }

        return false;
    }

    /**
     * Returns status of the URL creation after the last [[createUrl()]] call.
     *
     * For multiple rules statuses will be combined by bitwise `or` operator
     * (e.g. `UrlRule::CREATE_STATUS_PARSING_ONLY | UrlRule::CREATE_STATUS_PARAMS_MISMATCH`).
     *
     * @return null|int Status of the URL creation after the last [[createUrl()]] call. `null` if rule does not provide
     * info about create status.
     * @see $createStatus
     * @see http://php.net/manual/en/language.operators.bitwise.php
     * @since 2.0.12
     */
    public function getCreateUrlStatus(): ?int
    {
        return $this->createStatus;
    }
}
