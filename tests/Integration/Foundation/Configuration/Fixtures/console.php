<?php

use Heritage\Foundation\Inspiring;
use Heritage\Support\Facades\Scribe;

Scribe::command('test:inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();
