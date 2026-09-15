<?php

namespace Heritage\Tests\Validation;

use Heritage\Tests\Validation\Fixtures\IntegerStatus;
use Heritage\Tests\Validation\Fixtures\PureEnum;
use Heritage\Tests\Validation\Fixtures\StringStatus;
use Heritage\Tests\Validation\Fixtures\Values;
use Heritage\Translation\ArrayLoader;
use Heritage\Translation\Translator;
use Heritage\Validation\Rule;
use Heritage\Validation\Rules\NotIn;
use Heritage\Validation\Validator;
use PHPUnit\Framework\TestCase;

include_once 'Fixtures/Enums.php';

class ValidationNotInRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new NotIn(['Ugarit', 'Framework', 'PHP']);

        $this->assertSame('not_in:"Ugarit","Framework","PHP"', (string) $rule);

        $rule = new NotIn(collect(['Taylor', 'Michael', 'Tim']));

        $this->assertSame('not_in:"Taylor","Michael","Tim"', (string) $rule);

        $rule = Rule::notIn(collect([1, 2, 3, 4]));

        $this->assertSame('not_in:"1","2","3","4"', (string) $rule);

        $rule = Rule::notIn(collect([1, 2, 3, 4]));

        $this->assertSame('not_in:"1","2","3","4"', (string) $rule);

        $rule = Rule::notIn([1, 2, 3, 4]);

        $this->assertSame('not_in:"1","2","3","4"', (string) $rule);

        $rule = Rule::notIn(collect([1, 2, 3, 4]));

        $this->assertSame('not_in:"1","2","3","4"', (string) $rule);

        $rule = Rule::notIn(new Values);

        $this->assertSame('not_in:"1","2","3","4"', (string) $rule);

        $rule = new NotIn(new Values);

        $this->assertSame('not_in:"1","2","3","4"', (string) $rule);

        $rule = Rule::notIn('1', '2', '3', '4');

        $this->assertSame('not_in:"1","2","3","4"', (string) $rule);

        $rule = new NotIn('1', '2', '3', '4');

        $this->assertSame('not_in:"1","2","3","4"', (string) $rule);

        $rule = Rule::notIn([StringStatus::done]);

        $this->assertSame('not_in:"done"', (string) $rule);

        $rule = Rule::notIn([IntegerStatus::done]);

        $this->assertSame('not_in:"2"', (string) $rule);

        $rule = Rule::notIn([PureEnum::one]);

        $this->assertSame('not_in:"one"', (string) $rule);
    }

    public function testNotInRuleValidation()
    {
        $trans = new Translator(new ArrayLoader, 'en');

        $v = new Validator($trans, ['x' => 'foo'], ['x' => Rule::notIn('bar', 'baz')]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'foo'], ['x' => (string) Rule::notIn('bar', 'baz')]);
        $this->assertTrue($v->passes());

        $v = new Validator($trans, ['x' => 'foo'], ['x' => [Rule::notIn('foo', 'bar')]]);
        $this->assertFalse($v->passes());

        $v = new Validator($trans, ['x' => 'foo'], ['x' => ['required', Rule::notIn('bar', 'baz')]]);
        $this->assertTrue($v->passes());
    }
}
