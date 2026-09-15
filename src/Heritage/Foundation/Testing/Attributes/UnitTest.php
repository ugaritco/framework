<?php

namespace Heritage\Foundation\Testing\Attributes;

use Attribute;

/**
 * Run a test without configuring the Ugarit framework.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class UnitTest
{
}
