<div class="previous">
    <span class="arrow">&crarr;</span>
    <h2>
        <span>Caused by:</span>
        <?php $name = $this->getExceptionName($exception) ?>
        <?php if ($name !== null): ?>
            <span><?= $this->htmlEncode($name) ?></span> &ndash;
            <?= $this->addTypeLinks(get_class($exception)) ?>
        <?php else: ?>
            <span><?= $this->htmlEncode(get_class($exception)) ?></span>
        <?php endif; ?>
    </h2>
    <h3><?= nl2br($this->htmlEncode($exception->getMessage())) ?></h3>
    <p>in <span class="file"><?= $exception->getFile() ?></span> at line <span class="line"><?= $exception->getLine() ?></span></p>
    <?= $this->renderPreviousExceptions($exception) ?>
</div>
