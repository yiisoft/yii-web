<?php
/* @var $this \Yiisoft\Yii\Web\ErrorHandler\HtmlRenderer */
?>
<div class="previous">
    <span class="arrow">&crarr;</span>
    <h2>
        <span>Caused by:</span>
        <span><?= $this->htmlEncode(get_class($throwable)) ?></span>
    </h2>
    <h3><?= nl2br($this->htmlEncode($throwable->getMessage())) ?></h3>
    <p>in <span class="file"><?= $throwable->getFile() ?></span> at line <span class="line"><?= $throwable->getLine() ?></span></p>
    <?= $this->renderPreviousExceptions($throwable) ?>
</div>
