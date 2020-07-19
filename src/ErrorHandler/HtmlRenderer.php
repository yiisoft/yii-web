<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Web\ErrorHandler;

use Alexkart\CurlBuilder\Command;
use Yiisoft\Yii\Web\Info;

final class HtmlRenderer extends ThrowableRenderer
{
    private int $maxSourceLines = 19;
    private int $maxTraceLines = 13;

    private string $traceLine = '{html}';

    private string $defaultTemplatePath;

    private string $errorTemplate;
    private string $exceptionTemplate;

    public function __construct(array $templates = [])
    {
        $this->defaultTemplatePath = $templates['path'];
        $this->errorTemplate = $templates['error'] ?? $this->defaultTemplatePath . '/error.php';
        $this->exceptionTemplate = $templates['exception'] ?? $this->defaultTemplatePath . '/exception.php';
    }

    public function withMaxSourceLines(int $maxSourceLines): self
    {
        $new = clone $this;
        $new->maxSourceLines = $maxSourceLines;
        return $new;
    }

    public function withMaxTraceLines(int $maxTraceLines): self
    {
        $new = clone $this;
        $new->maxTraceLines = $maxTraceLines;
        return $new;
    }

    public function withTraceLine(string $traceLine): self
    {
        $new = clone $this;
        $new->traceLine = $traceLine;
        return $new;
    }

    public function render(\Throwable $t): string
    {
        return $this->renderTemplate($this->errorTemplate, [
            'throwable' => $t,
        ]);
    }

    public function renderVerbose(\Throwable $t): string
    {
        return $this->renderTemplate($this->exceptionTemplate, [
            'throwable' => $t,
        ]);
    }

    private function htmlEncode(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    private function renderTemplate(string $path, array $params): string
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Template not found at $path");
        }

        $renderer = function (): void {
            extract(func_get_arg(1), EXTR_OVERWRITE);
            require func_get_arg(0);
        };

        $obInitialLevel = ob_get_level();
        ob_start();
        ob_implicit_flush(0);
        try {
            $renderer->bindTo($this)($path, $params);
            return ob_get_clean();
        } catch (\Throwable $e) {
            while (ob_get_level() > $obInitialLevel) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
        }
    }

    /**
     * Renders the previous exception stack for a given Exception.
     * @param \Throwable $t the exception whose precursors should be rendered.
     * @return string HTML content of the rendered previous exceptions.
     * Empty string if there are none.
     * @throws \Throwable
     */
    private function renderPreviousExceptions(\Throwable $t): string
    {
        if (($previous = $t->getPrevious()) !== null) {
            $templatePath = $this->defaultTemplatePath . '/previousException.php';
            return $this->renderTemplate($templatePath, ['throwable' => $previous]);
        }
        return '';
    }

    /**
     * Renders a single call stack element.
     * @param string|null $file name where call has happened.
     * @param int|null $line number on which call has happened.
     * @param string|null $class called class name.
     * @param string|null $method called function/method name.
     * @param array $args array of method arguments.
     * @param int $index number of the call stack element.
     * @return string HTML content of the rendered call stack element.
     * @throws \Throwable
     */
    private function renderCallStackItem(?string $file, ?int $line, ?string $class, ?string $method, array $args, int $index): string
    {
        $lines = [];
        $begin = $end = 0;
        if ($file !== null && $line !== null) {
            $line--; // adjust line number from one-based to zero-based
            $lines = @file($file);
            if ($line < 0 || $lines === false || ($lineCount = count($lines)) < $line) {
                return '';
            }
            $half = (int)(($index === 1 ? $this->maxSourceLines : $this->maxTraceLines) / 2);
            $begin = $line - $half > 0 ? $line - $half : 0;
            $end = $line + $half < $lineCount ? $line + $half : $lineCount - 1;
        }
        $templatePath = $this->defaultTemplatePath . '/callStackItem.php';
        return $this->renderTemplate($templatePath, [
            'file' => $file,
            'line' => $line,
            'class' => $class,
            'method' => $method,
            'index' => $index,
            'lines' => $lines,
            'begin' => $begin,
            'end' => $end,
            'args' => $args,
        ]);
    }

    /**
     * Renders call stack.
     * @param \Throwable $t exception to get call stack from
     * @return string HTML content of the rendered call stack.
     * @throws \Throwable
     */
    private function renderCallStack(\Throwable $t): string
    {
        $out = '<ul>';
        $out .= $this->renderCallStackItem($t->getFile(), $t->getLine(), null, null, [], 1);
        for ($i = 0, $trace = $t->getTrace(), $length = count($trace); $i < $length; ++$i) {
            $file = !empty($trace[$i]['file']) ? $trace[$i]['file'] : null;
            $line = !empty($trace[$i]['line']) ? $trace[$i]['line'] : null;
            $class = !empty($trace[$i]['class']) ? $trace[$i]['class'] : null;
            $function = null;
            if (!empty($trace[$i]['function']) && $trace[$i]['function'] !== 'unknown') {
                $function = $trace[$i]['function'];
            }
            $args = !empty($trace[$i]['args']) ? $trace[$i]['args'] : [];
            $out .= $this->renderCallStackItem($file, $line, $class, $function, $args, $i + 2);
        }
        $out .= '</ul>';
        return $out;
    }

    /**
     * Determines whether given name of the file belongs to the framework.
     * @param string $file name to be checked.
     * @return bool whether given name of the file belongs to the framework.
     */
    private function isCoreFile(?string $file): bool
    {
        return $file === null || strpos(realpath($file), Info::frameworkPath() . DIRECTORY_SEPARATOR) === 0;
    }

    /**
     * Adds informational links to the given PHP type/class.
     * @param string $code type/class name to be linkified.
     * @param string $title custom title to use
     * @return string linkified with HTML type/class name.
     * @throws \ReflectionException
     */
    private function addTypeLinks(string $code, string $title = null): string
    {
        if (preg_match('/(.*?)::([^(]+)/', $code, $matches)) {
            [, $class, $method] = $matches;
            $text = $title ? $this->htmlEncode($title) : $this->htmlEncode($class) . '::' . $this->htmlEncode($method);
        } else {
            $class = $code;
            $method = null;
            $text = $title ? $this->htmlEncode($title) : $this->htmlEncode($class);
        }
        $url = null;
        $shouldGenerateLink = true;
        if ($method !== null && substr_compare($method, '{closure}', -9) !== 0) {
            $reflection = new \ReflectionClass($class);
            if ($reflection->hasMethod($method)) {
                $reflectionMethod = $reflection->getMethod($method);
                $shouldGenerateLink = $reflectionMethod->isPublic() || $reflectionMethod->isProtected();
            } else {
                $shouldGenerateLink = false;
            }
        }
        if ($shouldGenerateLink) {
            $url = $this->getTypeUrl($class, $method);
        }
        if ($url === null) {
            return $text;
        }
        return '<a href="' . $url . '" target="_blank">' . $text . '</a>';
    }

    /**
     * Returns the informational link URL for a given PHP type/class.
     * @param string $class the type or class name.
     * @param string|null $method the method name.
     * @return string|null the informational link URL.
     * @see addTypeLinks()
     */
    private function getTypeUrl(?string $class, ?string $method): ?string
    {
        if (strncmp($class, 'Yiisoft\\', 8) !== 0) {
            return null;
        }
        $page = $this->htmlEncode(strtolower(str_replace('\\', '-', $class)));
        $url = "http://www.yiiframework.com/doc-3.0/$page.html";
        if ($method) {
            $url .= "#$method()-detail";
        }
        return $url;
    }

    /**
     * Converts arguments array to its string representation.
     *
     * @param array $args arguments array to be converted
     * @return string string representation of the arguments array
     */
    private function argumentsToString(array $args): string
    {
        $count = 0;
        $isAssoc = $args !== array_values($args);
        foreach ($args as $key => $value) {
            $count++;
            if ($count >= 5) {
                if ($count > 5) {
                    unset($args[$key]);
                } else {
                    $args[$key] = '...';
                }
                continue;
            }
            if (is_object($value)) {
                $args[$key] = '<span class="title">' . $this->htmlEncode(get_class($value)) . '</span>';
            } elseif (is_bool($value)) {
                $args[$key] = '<span class="keyword">' . ($value ? 'true' : 'false') . '</span>';
            } elseif (is_string($value)) {
                $fullValue = $this->htmlEncode($value);
                if (mb_strlen($value, 'UTF-8') > 32) {
                    $displayValue = $this->htmlEncode(mb_substr($value, 0, 32, 'UTF-8')) . '...';
                    $args[$key] = "<span class=\"string\" title=\"$fullValue\">'$displayValue'</span>";
                } else {
                    $args[$key] = "<span class=\"string\">'$fullValue'</span>";
                }
            } elseif (is_array($value)) {
                unset($args[$key]);
                $args[$key] = '[' . $this->argumentsToString($value) . ']';
            } elseif ($value === null) {
                $args[$key] = '<span class="keyword">null</span>';
            } elseif (is_resource($value)) {
                $args[$key] = '<span class="keyword">resource</span>';
            } else {
                $args[$key] = '<span class="number">' . $value . '</span>';
            }
            if (is_string($key)) {
                $args[$key] = '<span class="string">\'' . $this->htmlEncode($key) . "'</span> => $args[$key]";
            } elseif ($isAssoc) {
                $args[$key] = "<span class=\"number\">$key</span> => $args[$key]";
            }
        }
        return implode(', ', $args);
    }

    /**
     * Renders the information about request.
     * @return string the rendering result
     */
    private function renderRequest(): string
    {
        if ($this->request === null) {
            return '';
        }

        $request = $this->request;
        $output = $request->getMethod() . ' ' . $request->getUri() . "\n";

        foreach ($request->getHeaders() as $name => $values) {
            if ($name === 'Host') {
                continue;
            }

            foreach ($values as $value) {
                $output .= "$name: $value\n";
            }
        }

        $output .= "\n" . $request->getBody() . "\n\n";

        return '<pre>' . $this->htmlEncode(rtrim($output, "\n")) . '</pre>';
    }

    private function renderCurl(): string
    {
        try {
            $output = (new Command())->setRequest($this->request)->build();
        } catch (\Throwable $e) {
            $output = 'Error generating curl command: ' . $e->getMessage();
        }

        return $this->htmlEncode($output);
    }


    /**
     * Creates string containing HTML link which refers to the home page of determined web-server software
     * and its full name.
     * @return string server software information hyperlink.
     */
    private function createServerInformationLink(): string
    {
        if (isset($_SERVER['SERVER_SOFTWARE'])) {
            $serverUrls = [
                'http://httpd.apache.org/' => ['apache'],
                'http://nginx.org/' => ['nginx'],
                'http://lighttpd.net/' => ['lighttpd'],
                'http://gwan.com/' => ['g-wan', 'gwan'],
                'http://iis.net/' => ['iis', 'services'],
                'https://secure.php.net/manual/en/features.commandline.webserver.php' => ['development'],
            ];

            foreach ($serverUrls as $url => $keywords) {
                foreach ($keywords as $keyword) {
                    if (stripos($_SERVER['SERVER_SOFTWARE'], $keyword) !== false) {
                        return '<a href="' . $url . '" target="_blank">' . $this->htmlEncode($_SERVER['SERVER_SOFTWARE']) . '</a>';
                    }
                }
            }
        }
        return '';
    }

    /**
     * Creates string containing HTML link which refers to the page with the current version
     * of the framework and version number text.
     * @return string framework version information hyperlink.
     */
    public function createFrameworkVersionLink(): string
    {
        return '<a href="http://github.com/yiisoft/yii-web/" target="_blank">' . $this->htmlEncode(Info::frameworkVersion()) . '</a>';
    }
}
