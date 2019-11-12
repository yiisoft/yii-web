<?php

namespace Yiisoft\Yii\Web\Helper;

use Psr\Http\Message\RequestInterface;

final class HeaderHelper
{
    /**
     * Explode header value to value and parameters (eg. text/html;q=2;version=6)
     * @param string $headerValue
     * @return array first element is the value, and key-value are the parameters
     */
    public static function getValueAndParameters(string $headerValue): array
    {
        $headerValue = trim($headerValue);
        if ($headerValue === '') {
            return [];
        }
        $parts = preg_split('/\s*;\s*/', $headerValue, -1, PREG_SPLIT_NO_EMPTY);
        $output = [array_shift($parts)];
        foreach ($parts as $part) {
            [$key, $headerValue] = explode('=', $part, 2);
            $output[$key] = $headerValue;
        }
        return $output;
    }

    /**
     * Getting header value as q factor sorted list
     * @param string|string[] $values Header value as a comma-separated string or already exploded string array.
     * @see getValueAndParameters
     * @link https://developer.mozilla.org/en-US/docs/Glossary/Quality_values
     */
    public static function getByQFactorSortedList($values): array
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
            $parse = self::getValueAndParameters($value);
            $q = $parse['q'] ?? 1.0;
            if (is_string($q) && preg_match('/^(?:0(?:\.\d{1,3})?|1(?:\.0{1,3})?)$/', $q) === 0) {
                throw new \InvalidArgumentException('Invalid q factor');
            }
            $parse['q'] = floatval($q);
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
     * @param $values string|string[]|RequestInterface $values Header value as a comma-separated string
     *                                                         or already exploded string array
     *                                                         or request interface with 'accept' header.
     * @return string[] sorted accept types. Note: According to RFC 7231, special parameters (except the q factor) are
     *                  added to the type, which are always appended by a semicolon and sorted by string.
     * @link https://tools.ietf.org/html/rfc7231#section-5.3.2
     */
    public static function getSortedAcceptTypes($values): array
    {
        if ($values instanceof RequestInterface) {
            $values = $values->getHeader('accept');
        }
        $output = self::getByQFactorSortedList($values);
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
            asort($value, SORT_STRING);
            $output[$key] = $type . ';' . join(';', $value);
        }
        return $output;
    }
}
