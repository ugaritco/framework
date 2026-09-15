<?php

namespace Heritage\Tests\Validation;

use Heritage\Translation\ArrayLoader;
use Heritage\Translation\Translator;
use Heritage\Validation\Rule;
use Heritage\Validation\Rules\ProhibitedUnless;
use Heritage\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationProhibitedUnlessRuleTest extends TestCase
{
    protected Translator $translator;

    protected function setUp(): void
    {
        $this->translator = new Translator(new ArrayLoader, 'en');
    }

    public function testInstanceOf()
    {
        $this->assertInstanceOf(ProhibitedUnless::class, Rule::prohibitedUnless(true));
    }

    public function testBooleanConditionTrue()
    {
        $rule = Rule::prohibitedUnless(true);
        $this->assertSame('', (string) $rule);
    }

    public function testBooleanConditionFalse()
    {
        $rule = Rule::prohibitedUnless(false);
        $this->assertSame('prohibited', (string) $rule);
    }

    public function testClosureConditionTrue()
    {
        $rule = Rule::prohibitedUnless(fn () => true);
        $this->assertSame('', (string) $rule);
    }

    public function testClosureConditionFalse()
    {
        $rule = Rule::prohibitedUnless(fn () => false);
        $this->assertSame('prohibited', (string) $rule);
    }

    public function testFieldIsProhibitedWhenConditionFalse()
    {
        $validator = new Validator(
            $this->translator,
            ['name' => 'Taylor', 'secret' => 'value'],
            [
                'name' => 'required|string',
                'secret' => [Rule::prohibitedUnless(false)],
            ],
        );

        $this->assertTrue($validator->fails());
    }

    public function testFieldIsAllowedWhenConditionTrue()
    {
        $validator = new Validator(
            $this->translator,
            ['name' => 'Taylor', 'secret' => 'value'],
            [
                'name' => 'required|string',
                'secret' => [Rule::prohibitedUnless(true)],
            ],
        );

        $this->assertTrue($validator->passes());
    }

    public function testInvalidConditionThrows()
    {
        $this->expectException(\InvalidArgumentException::class);

        Rule::prohibitedUnless('invalid');
    }
}
