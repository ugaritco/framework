<?php

use Heritage\Container\Container;
use Heritage\Database\Eloquent\ModelNotFoundException;
use Heritage\Foundation\Configuration\Exceptions;
use Heritage\Foundation\Exceptions\Handler;
use Symfony\Component\HttpKernel\Exception\HttpException;

$exceptions = new Exceptions(
    new Handler(
        new Container,
    ),
);

$exceptions->stopIgnoring(HttpException::class);
$exceptions->stopIgnoring([ModelNotFoundException::class]);
