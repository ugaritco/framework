<?php

namespace Heritage\Tests\Validation;

use Heritage\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationAddFailureTest extends TestCase
{
    /**
     * Making Validator using ValidationValidatorTest.
     *
     * @return \Heritage\Validation\Validator
     */
    public function makeValidator()
    {
        $mainTest = new ValidationValidatorTest('foo');
        $trans = $mainTest->getHeritageArrayTranslator();

        return new Validator($trans, ['foo' => ['bar' => ['baz' => '']]], ['foo.bar.baz' => 'sometimes|required']);
    }

    public function testAddFailureExists()
    {
        $validator = $this->makeValidator();
        $method_name = 'addFailure';
        $this->assertTrue(method_exists($validator, $method_name));
        $this->assertIsCallable([$validator, $method_name]);
    }

    public function testAddFailureIsFunctional()
    {
        $attribute = 'Eugene';
        $validator = $this->makeValidator();
        $validator->addFailure($attribute, 'not_in');
        $messages = json_decode($validator->messages());
        $this->assertSame('validation.required', $messages->{'foo.bar.baz'}[0], 'initial data in messages is lost');
        $this->assertSame('validation.not_in', $messages->{$attribute}[0], 'new data in messages was not added');
    }
}
