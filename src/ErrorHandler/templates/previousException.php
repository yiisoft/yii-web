<?php
/* @var $this \Yiisoft\Yii\Web\ErrorHandler\HtmlRenderer */
?>
<div class="previous">
    <span class="arrow">&crarr;</span>
    <h2>
        <span>Caused by:</span>
        <span><?= $this->htmlEncode(get_class($exception)) ?></span>
    </h2>
    <h3><?= nl2br($this->htmlEncode($exception->getMessage())) ?></h3>
    <p>in <span class="file"><?= $exception->getFile() ?></span> at line <span class="line"><?= $exception->getLine() ?></span></p>
    <?= $this->renderPreviousExceptions($exception) ?>
</div>
