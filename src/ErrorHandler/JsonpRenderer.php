<?php


namespace Yiisoft\Yii\Web\ErrorHandler;

/**
 * Formats exception into JSONP string
 */
final class JsonpRenderer extends JsonRenderer
{

    /**
     * @var string
     */
    private $callbackParameter = 'callback';
    /**
     * @var string|null
     */
    private $callback = null;

    public function render(\Throwable $t): string
    {
        $result = parent::render($t);
        $callback = $this->callback ?? $this->request->getQueryParams()[$this->callbackParameter] ?? 'console.log';
        return sprintf('%s(%s)', $callback, $result);
    }

    /**
     * @param string the name of the GET parameter that specifies the callback.
     * @return static
     */
    public function setCallbackParameter(string $name)
    {
        $this->callbackParameter = $name;
        return $this;
    }

    /**
     * @param string|null callback used. If NULL, callback will use from GET.
     * @return static
     */
    public function setCallback(?string $name)
    {
        $this->callback = $name;
        return $this;
    }
}
