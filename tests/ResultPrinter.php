<?php

namespace Yiisoft\Yii\Web\Tests;

/**
 * Class ResultPrinter overrides \PHPUnit\TextUI\ResultPrinter constructor
 * to change default output to STDOUT and prevent some tests from fail when
 * they can not be executed after headers have been sent.
 */
class ResultPrinter extends \PHPUnit\TextUI\ResultPrinter
{
    public function __construct(
        $out = null,
        $verbose = false,
        $colors = \PHPUnit\TextUI\ResultPrinter::COLOR_DEFAULT,
        $debug = false,
        $numberOfColumns = 80,
        $reverse = false
    ) {
        if ($out === null) {
            $out = STDOUT;
        }

        parent::__construct($out, $verbose, $colors, $debug, $numberOfColumns, $reverse);
    }

    public function flush(): void
    {
        if ($this->out !== STDOUT) {
            parent::flush();
        }
    }
}
