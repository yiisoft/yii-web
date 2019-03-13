<?php
namespace yii\router;

// TODO: is there any benefit having two interfaces except conforming to single responsibility principle?
interface RouterInterface extends UrlGeneratorInterface, UrlMatcherInterface
{
}
