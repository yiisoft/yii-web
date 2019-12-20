<?php

namespace Yiisoft\Yii\Web\Helper;

use Psr\Http\Message\RequestInterface;

final class HeaderHelper
{
    /**
     * @link https://www.rfc-editor.org/rfc/rfc2616.html#section-2.2
     * token  = 1*<any CHAR except CTLs or separators>
     */
    private const PATTERN_TOKEN = '(?:(?:[^()<>@,;:\\"\/[\\]?={} \t\x7f]|[\x00-\x1f])+)';

    /**
     * @link https://www.rfc-editor.org/rfc/rfc2616.html#section-3.6
     * attribute = token
     */
    private const PATTERN_ATTRIBUTE = self::PATTERN_TOKEN;

    /**
     * @link https://www.rfc-editor.org/rfc/rfc2616.html#section-2.2
     * quoted-string  = ( <"> *(qdtext | quoted-pair ) <"> )
     * qdtext         = <any TEXT except <">>
     * quoted-pair    = "\" CHAR
     */
    private const PATTERN_QUOTED_STRING = '(?:"(?:(?:\\\\.)+|[^\\"]+)*")';

    /**
     * @link https://www.rfc-editor.org/rfc/rfc2616.html#section-3.6
     * value = token | quoted-string
     */
    private const PATTERN_VALUE = '(?:' . self::PATTERN_QUOTED_STRING . '|' . self::PATTERN_TOKEN . ')';

    /**
     * Explode header value to value and parameters (eg. text/html;q=2;version=6)
     *
     * @link https://www.rfc-editor.org/rfc/rfc2616.html#section-3.6
     * transfer-extension      = token *( ";" parameter )
     * @param string $headerValue
     * @return array first element is the value, and key-value are the parameters
     */
    public static function getValueAndParameters(string $headerValue, bool $lowerCaseValue = true, bool $lowerCaseParameter = true, bool $lowerCaseParameterValue = true): array
    {
        $headerValue = trim($headerValue);
        if ($headerValue === '') {
            return [];
        }
        $parts = explode(';', $headerValue, 2);
        $output = [$lowerCaseValue ? strtolower($parts[0]) : $parts[0]];
        if (count($parts) === 1) {
            return $output;
        }
        return $output + self::getParameters($parts[1], $lowerCaseParameter, $lowerCaseParameterValue);
    }

    /**
     * Explode header value to parameters (eg. q=2;version=6)
     *
     * @link https://tools.ietf.org/html/rfc7230#section-3.2.6
     */
    public static function getParameters(string $headerValue, bool $lowerCaseParameter = true, $lowerCaseValue = true): array
    {
        $headerValue = trim($headerValue);
        if ($headerValue === '') {
            return [];
        }
        if (rtrim($headerValue, ';') !== $headerValue) {
            throw new \InvalidArgumentException('Cannot end with a semicolon.');
        }
        $output = [];
        do {
            $headerValue = preg_replace_callback(
                '/^(?<parameter>' . self::PATTERN_ATTRIBUTE . ')=(?<value>' . self::PATTERN_VALUE . ')(?:;|$)/',
                static function ($matches) use (&$output, $lowerCaseParameter, $lowerCaseValue) {
                    $value = $matches['value'];
                    if (substr($matches['value'], 0, 1) === '"') {
                        // unescape + remove first and last quote
                        $value = preg_replace('/\\\\(.)/', '$1', substr($value, 1, -1));
                    }
                    $output[$lowerCaseParameter ? strtolower($matches['parameter']) : $matches['parameter']] = $lowerCaseValue ? strtolower($value) : $value;
                }, $headerValue, 1, $count);
            if ($count !== 1) {
                throw new \InvalidArgumentException('Invalid input: ' . $headerValue);
            }
        } while ($headerValue !== '');
        return $output;
    }

    /**
     * Getting header value as q factor sorted list
     * @param string|string[] $values Header value as a comma-separated string or already exploded string array.
     * @see getValueAndParameters
     * @link https://developer.mozilla.org/en-US/docs/Glossary/Quality_values
     * @link https://www.ietf.org/rfc/rfc2045.html#section-2
     */
    public static function getSortedValueAndParameters($values, bool $lowerCaseValue = true, bool $loweCaseParameter = true, bool $lowerCaseParameterValue = true): array
    {
        if (is_string($values)) {
            $values = preg_split('/\s*,\s*/', trim($values), -1, PREG_SPLIT_NO_EMPTY);
        }
        if (!is_array($values)) {
            throw new \InvalidArgumentException('Values ​​are neither array nor string');
        }
        if (count($values) === 0) {
            return [];
        }
        $output = [];
        foreach ($values as $value) {
            $parse = self::getValueAndParameters($value, $lowerCaseValue, $loweCaseParameter, $lowerCaseParameterValue);
            // case-insensitive "q" parameter
            $q = $parse['q'] ?? $parse['Q'] ?? 1.0;

            // min 0.000 max 1.000, max 3 digits, without digits allowed
            if (is_string($q) && preg_match('/^(?:0(?:\.\d{1,3})?|1(?:\.0{1,3})?)$/', $q) === 0) {
                throw new \InvalidArgumentException('Invalid q factor');
            }
            $parse['q'] = (float)$q;
            unset($parse['Q']);
            $output[] = $parse;
        }
        usort($output, static function ($a, $b) {
            $a = $a['q'];
            $b = $b['q'];
            if ($a === $b) {
                return 0;
            }
            return $a > $b ? -1 : 1;
        });
        return $output;
    }

    /**
     * @see getSortedAcceptTypes
     */
    public static function getSortedAcceptTypesFromRequest(RequestInterface $request): array
    {
        return static::getSortedAcceptTypes($request->getHeader('accept'));
    }

    /**
     * @param $values string|string[] $values Header value as a comma-separated string or already exploded string array
     * @return string[] sorted accept types. Note: According to RFC 7231, special parameters (except the q factor) are
     *                  added to the type, which are always appended by a semicolon and sorted by string.
     * @link https://tools.ietf.org/html/rfc7231#section-5.3.2
     * @link https://www.ietf.org/rfc/rfc2045.html#section-2
     */
    public static function getSortedAcceptTypes($values): array
    {
        $output = self::getSortedValueAndParameters($values);
        usort($output, static function ($a, $b) {
            if ($a['q'] !== $b['q']) {
                // The higher q value wins
                return $a['q'] > $b['q'] ? -1 : 1;
            }
            $typeA = reset($a);
            $typeB = reset($b);
            if (strpos($typeA, '*') === false && strpos($typeB, '*') === false) {
                $countA = count($a);
                $countB = count($b);
                if ($countA === $countB) {
                    // They are equivalent for the same parameter number
                    return 0;
                }
                // No wildcard character, higher parameter number wins
                return $countA > $countB ? -1 : 1;
            }
            $endWildcardA = substr($typeA, -1, 1) === '*';
            $endWildcardB = substr($typeB, -1, 1) === '*';
            if (($endWildcardA && !$endWildcardB) || (!$endWildcardA && $endWildcardB)) {
                // The wildcard ends is the loser.
                return $endWildcardA ? 1 : -1;
            }
            // The wildcard starts is the loser.
            return strpos($typeA, '*') === 0 ? 1 : -1;
        });
        foreach ($output as $key => $value) {
            $type = array_shift($value);
            unset($value['q']);
            if (count($value) === 0) {
                $output[$key] = $type;
                continue;
            }
            foreach ($value as $k => $v) {
                $value[$k] = $k . '=' . $v;
            }
            // Parameters are sorted for easier use of parameter variations.
            asort($value, SORT_STRING);
            $output[$key] = $type . ';' . join(';', $value);
        }
        return $output;
    }
}
