<?php

use Heritage\Contracts\Support\Arrayable;
use Heritage\Support\Collection;

use function PHPStan\Testing\assertType;

/** @implements Arrayable<int, User> */
class Users implements Arrayable
{
    public function toArray(): array
    {
        return [new User];
    }
}

$collection = collect([new User]);
$arrayable = new Users;
/** @var iterable<int, int> $iterable */
$iterable = [1];
/** @var Traversable<int, string> $traversable */
$traversable = new ArrayIterator(['string']);

$associativeCollection = collect(['John' => new User]);

class Invokable
{
    public function __invoke(): string
    {
        return 'Taylor';
    }
}
$invokable = new Invokable;

assertType('Heritage\Support\Collection<int, User>', $collection);

assertType('Heritage\Support\Collection<int, string>', collect(['string']));
assertType('Heritage\Support\Collection<string, User>', collect(['string' => new User]));
assertType('Heritage\Support\Collection<int, User>', collect($arrayable));
assertType('Heritage\Support\Collection<int, User>', collect($collection));
assertType('Heritage\Support\Collection<int, User>', collect($collection));
assertType('Heritage\Support\Collection<int, int>', collect($iterable));
assertType('Heritage\Support\Collection<int, string>', collect($traversable));

assertType('Heritage\Support\Collection<int, string>', $collection::make(['string']));
assertType('Heritage\Support\Collection<string, User>', $collection::make(['string' => new User]));
assertType('Heritage\Support\Collection<int, User>', $collection::make($arrayable));
assertType('Heritage\Support\Collection<int, User>', $collection::make($collection));
assertType('Heritage\Support\Collection<int, User>', $collection::make($collection));
assertType('Heritage\Support\Collection<int, int>', $collection::make($iterable));
assertType('Heritage\Support\Collection<int, string>', $collection::make($traversable));

assertType('Heritage\Support\Collection<int, User>', $collection::times(10, function ($int) {
    // assertType('int', $int);

    return new User;
}));

assertType('Heritage\Support\Collection<int, User>', $collection::times(10, function () {
    return new User;
}));

assertType('Heritage\Support\Collection<int, User>', $collection->each(function ($user) {
    assertType('User', $user);
}));

assertType('Heritage\Support\Collection<int, int>', $collection::range(1, 100));

assertType('Heritage\Support\Collection<(int|string), string>', $collection::wrap('string'));
assertType('Heritage\Support\Collection<(int|string), User>', $collection::wrap(new User));

assertType('Heritage\Support\Collection<(int|string), string>', $collection::wrap(['string']));
assertType('Heritage\Support\Collection<(int|string), User>', $collection::wrap(['string' => new User]));

assertType("array<0, 'string'>", $collection::unwrap(['string']));
assertType('array<int, User>', $collection::unwrap(
    $collection
));

assertType('Heritage\Support\Collection<int, User>', $collection::empty());

assertType('float|int|null', $collection->average());
assertType('float|int|null', $collection->average('string'));
assertType('float|int|null', $collection->average(function ($user) {
    assertType('User', $user);

    return 1;
}));
assertType('float|int|null', $collection->average(function ($user) {
    assertType('User', $user);

    return 0.1;
}));

assertType('float|int|null', $collection->median());
assertType('float|int|null', $collection->median('string'));
assertType('float|int|null', $collection->median(['string']));

assertType('array<int, float|int>|null', $collection->mode());
assertType('array<int, float|int>|null', $collection->mode('string'));
assertType('array<int, float|int>|null', $collection->mode(['string']));

assertType('Heritage\Support\Collection<int, mixed>', $collection->collapse());

assertType('bool', $collection->some(function ($user) {
    assertType('User', $user);

    return true;
}));
assertType('bool', $collection::make(['string'])->some('string', '=', 'string'));

assertType('bool', $collection->containsStrict(function ($user) {
    assertType('User', $user);

    return true;
}));
assertType('bool', $collection::make(['string'])->containsStrict('string', 'string'));
assertType('bool', $collection::make([[1]])->containsStrict(0));

assertType('Heritage\Support\LazyCollection<int, User>', $collection->lazy());

assertType('float|int|null', $collection->avg());
assertType('float|int|null', $collection->avg('string'));
assertType('float|int|null', $collection->avg(function ($user) {
    assertType('User', $user);

    return 1;
}));
assertType('float|int|null', $collection->avg(function ($user) {
    assertType('User', $user);

    return 0.1;
}));

assertType('bool', $collection->contains(function ($user) {
    assertType('User', $user);

    return true;
}));
assertType('bool', $collection->contains(function ($user, $int) {
    assertType('int', $int);
    assertType('User', $user);

    return true;
}));
assertType('bool', $collection::make(['string'])->contains('string', '=', 'string'));

assertType('Heritage\Support\Collection<int, array<int, string|User>>', $collection->crossJoin($collection::make(['string'])));
assertType('Heritage\Support\Collection<int, array<int, int|User>>', $collection->crossJoin([1, 2]));

assertType('Heritage\Support\Collection<int, int>', $collection::make([3, 4])->diff([1, 2]));
assertType('Heritage\Support\Collection<int, string>', $collection::make(['string-1'])->diff(['string-2']));

assertType('Heritage\Support\Collection<int, int>', $collection::make([3, 4])->diffUsing([1, 2], function ($intA, $intB) {
    assertType('int', $intA);
    assertType('int', $intB);

    return -1;
}));
assertType('Heritage\Support\Collection<int, string>', $collection::make(['string-1'])->diffUsing(['string-2'], function ($stringA, $stringB) {
    assertType('string', $stringA);
    assertType('string', $stringB);

    return -1;
}));

assertType('Heritage\Support\Collection<int, int>', $collection::make([3, 4])->diffAssoc([1, 2]));
assertType('Heritage\Support\Collection<string, string>', $collection::make(['string' => 'string'])->diffAssoc(['string' => 'string']));

assertType('Heritage\Support\Collection<int, int>', $collection::make([3, 4])->diffAssocUsing([1, 2], function ($intA, $intB) {
    assertType('int', $intA);
    assertType('int', $intB);

    return -1;
}));
assertType('Heritage\Support\Collection<int, string>', $collection::make(['string-1'])->diffAssocUsing(['string-2'], function ($intA, $intB) {
    assertType('int', $intA);
    assertType('int', $intB);

    return -1;
}));

assertType('Heritage\Support\Collection<int, int>', $collection::make([3, 4])->diffKeys([1, 2]));
assertType('Heritage\Support\Collection<string, string>', $collection::make(['string' => 'string'])->diffKeys(['string' => 'string']));

assertType('Heritage\Support\Collection<int, int>', $collection::make([3, 4])->diffKeysUsing([1, 2], function ($intA, $intB) {
    assertType('int', $intA);
    assertType('int', $intB);

    return -1;
}));
assertType('Heritage\Support\Collection<int, string>', $collection::make(['string-1'])->diffKeysUsing(['string-2'], function ($intA, $intB) {
    assertType('int', $intA);
    assertType('int', $intB);

    return -1;
}));

assertType('Heritage\Support\Collection<string, string>', $collection::make(['string' => 'string'])
    ->duplicates());
assertType('Heritage\Support\Collection<int, User>', $collection->duplicates('name', true));
assertType('Heritage\Support\Collection<int, int|string>', $collection::make([3, 'string'])
    ->duplicates(function ($intOrString) {
        assertType('int|string', $intOrString);

        return true;
    }));

assertType('Heritage\Support\Collection<string, string>', $collection::make(['string' => 'string'])
    ->duplicatesStrict());
assertType('Heritage\Support\Collection<int, User>', $collection->duplicatesStrict('name'));
assertType('Heritage\Support\Collection<int, int|string>', $collection::make([3, 'string'])
    ->duplicatesStrict(function ($intOrString) {
        assertType('int|string', $intOrString);

        return true;
    }));

assertType('Heritage\Support\Collection<int, User>', $collection->each(function ($user) {
    assertType('User', $user);

    return null;
}));
assertType('Heritage\Support\Collection<int, User>', $collection->each(function ($user) {
    assertType('User', $user);
}));
assertType('Heritage\Support\Collection<int, User>', $collection->each(function ($user, $int) {
    assertType('int', $int);
    assertType('User', $user);
}));

assertType('Heritage\Support\Collection<int, array{string}>', $collection::make([['string']])
    ->eachSpread(function ($int, $string) {
        // assertType('int', $int);
        // assertType('int', $string);

        return null;
    }));
assertType('Heritage\Support\Collection<int, array{int, string}>', $collection::make([[1, 'string']])
    ->eachSpread(function ($int, $string) {
        // assertType('int', $int);
        // assertType('int', $string);
    }));

assertType('bool', $collection->every(function ($user, $int) {
    assertType('int', $int);
    assertType('User', $user);

    return true;
}));
assertType('bool', $collection::make(['string'])->every('string', '=', 'string'));

assertType('Heritage\Support\Collection<string, string>', $collection::make(['string' => 'string'])->except(['string']));
assertType('Heritage\Support\Collection<int, User>', $collection->except([1]));
assertType('Heritage\Support\Collection<int, string>', $collection::make(['string'])
    ->except([1]));

assertType('Heritage\Support\Collection<int, User>', $collection->filter());
assertType('Heritage\Support\Collection<int, User>', $collection->filter(function ($user) {
    assertType('User', $user);

    return true;
}));

assertType('Heritage\Support\Collection<int, User>', $collection->filter());
assertType('Heritage\Support\Collection<int, User>', $collection->filter(function ($user) {
    assertType('User', $user);

    return true;
}));

assertType('Heritage\Support\Collection<int, User>|true', $collection->when(true, function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);

    return true;
}));
assertType('Heritage\Support\Collection<int, User>|null', $collection->when(true, function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);
}));
assertType("'string'|Heritage\Support\Collection<int, User>", $collection->when(true, function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);

    return 'string';
}));
assertType('Heritage\Support\Collection<int, User>|null', $collection->when('Taylor', function ($collection, $name) {
    assertType('Heritage\Support\Collection<int, User>', $collection);
    assertType("'Taylor'", $name);
}));
assertType(
    'Heritage\Support\Collection<int, User>|null',
    $collection->when(
        'Taylor',
        function ($collection, $name) {
            assertType('Heritage\Support\Collection<int, User>', $collection);
            assertType("'Taylor'", $name);
        },
        function ($collection, $name) {
            assertType('Heritage\Support\Collection<int, User>', $collection);
            assertType("'Taylor'", $name);
        }
    )
);
assertType('Heritage\Support\Collection<int, User>|null', $collection->when(fn () => 'Taylor', function ($collection, $name) {
    assertType('Heritage\Support\Collection<int, User>', $collection);
    assertType("'Taylor'", $name);
}));
assertType(
    'Heritage\Support\Collection<int, User>|null',
    $collection->when(
        function ($collection) {
            assertType('Heritage\Support\Collection<int, User>', $collection);

            return 14;
        },
        function ($collection, $count) {
            assertType('Heritage\Support\Collection<int, User>', $collection);
            assertType('14', $count);
        },
        function ($collection, $count) {
            assertType('Heritage\Support\Collection<int, User>', $collection);
            assertType('14', $count);
        }
    )
);

assertType('Heritage\Support\Collection<int, User>|null', $collection->when($invokable, function ($collection, $param) {
    assertType('Heritage\Support\Collection<int, User>', $collection);
    assertType('Invokable', $param);
}));

assertType('Heritage\Support\Collection<int, User>|true', $collection->whenEmpty(function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);

    return true;
}));
assertType('Heritage\Support\Collection<int, User>|null', $collection->whenEmpty(function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);
}));
assertType("'string'|Heritage\Support\Collection<int, User>", $collection->whenEmpty(function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);

    return 'string';
}));

assertType('Heritage\Support\Collection<int, User>|true', $collection->whenNotEmpty(function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);

    return true;
}));
assertType('Heritage\Support\Collection<int, User>|null', $collection->whenNotEmpty(function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);
}));
assertType("'string'|Heritage\Support\Collection<int, User>", $collection->whenNotEmpty(function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);

    return 'string';
}));

assertType('Heritage\Support\Collection<int, User>|true', $collection->unless(true, function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);

    return true;
}));
assertType('Heritage\Support\Collection<int, User>|null', $collection->unless(true, function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);
}));
assertType("'string'|Heritage\Support\Collection<int, User>", $collection->unless(true, function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);

    return 'string';
}));
assertType('Heritage\Support\Collection<int, User>|null', $collection->unless('Taylor', function ($collection, $name) {
    assertType('Heritage\Support\Collection<int, User>', $collection);
    assertType("'Taylor'", $name);
}));
assertType(
    'Heritage\Support\Collection<int, User>|null',
    $collection->unless(
        'Taylor',
        function ($collection, $name) {
            assertType('Heritage\Support\Collection<int, User>', $collection);
            assertType("'Taylor'", $name);
        },
        function ($collection, $name) {
            assertType('Heritage\Support\Collection<int, User>', $collection);
            assertType("'Taylor'", $name);
        }
    )
);
assertType('Heritage\Support\Collection<int, User>|null', $collection->unless(fn () => 'Taylor', function ($collection, $name) {
    assertType('Heritage\Support\Collection<int, User>', $collection);
    assertType("'Taylor'", $name);
}));
assertType(
    'Heritage\Support\Collection<int, User>|null',
    $collection->unless(
        function ($collection) {
            assertType('Heritage\Support\Collection<int, User>', $collection);

            return 14;
        },
        function ($collection, $count) {
            assertType('Heritage\Support\Collection<int, User>', $collection);
            assertType('14', $count);
        },
        function ($collection, $count) {
            assertType('Heritage\Support\Collection<int, User>', $collection);
            assertType('14', $count);
        }
    )
);

assertType('Heritage\Support\Collection<int, User>|null', $collection->unless($invokable, function ($collection, $param) {
    assertType('Heritage\Support\Collection<int, User>', $collection);
    assertType('Invokable', $param);
}));

assertType('Heritage\Support\Collection<int, User>|true', $collection->unlessEmpty(function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);

    return true;
}));
assertType('Heritage\Support\Collection<int, User>|null', $collection->unlessEmpty(function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);
}));
assertType("'string'|Heritage\Support\Collection<int, User>", $collection->unlessEmpty(function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);

    return 'string';
}));

assertType('Heritage\Support\Collection<int, User>|true', $collection->unlessNotEmpty(function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);

    return true;
}));
assertType('Heritage\Support\Collection<int, User>|null', $collection->unlessNotEmpty(function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);
}));
assertType("'string'|Heritage\Support\Collection<int, User>", $collection->unlessNotEmpty(function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);

    return 'string';
}));

assertType("Heritage\Support\Collection<int, array{string: string}>", $collection::make([['string' => 'string']])
    ->where('string'));
assertType("Heritage\Support\Collection<int, array{string: string}>", $collection::make([['string' => 'string']])
    ->where('string', '=', 'string'));
assertType("Heritage\Support\Collection<int, array{string: string}>", $collection::make([['string' => 'string']])
    ->where('string', 'string'));

assertType('Heritage\Support\Collection<int, User>', $collection->whereNull());
assertType('Heritage\Support\Collection<int, User>', $collection->whereNull('foo'));

assertType('Heritage\Support\Collection<int, User>', $collection->whereNotNull());
assertType('Heritage\Support\Collection<int, User>', $collection->whereNotNull('foo'));

assertType("Heritage\Support\Collection<int, array{string: int}>", $collection::make([['string' => 2]])
    ->whereStrict('string', 2));

assertType("Heritage\Support\Collection<int, array{string: int}>", $collection::make([['string' => 2]])
    ->whereIn('string', [2]));

assertType("Heritage\Support\Collection<int, array{string: int}>", $collection::make([['string' => 2]])
    ->whereInStrict('string', [2]));

assertType("Heritage\Support\Collection<int, array{string: int}>", $collection::make([['string' => 2]])
    ->whereBetween('string', [1, 3]));

assertType("Heritage\Support\Collection<int, array{string: int}>", $collection::make([['string' => 2]])
    ->whereNotBetween('string', [1, 3]));

assertType("Heritage\Support\Collection<int, array{string: int}>", $collection::make([['string' => 2]])
    ->whereNotIn('string', [2]));

assertType("Heritage\Support\Collection<int, array{string: int}>", $collection::make([['string' => 2]])
    ->whereNotInStrict('string', [2]));

assertType('Heritage\Support\Collection<int, User>', $collection::make([new User, 1])
    ->whereInstanceOf(User::class));

assertType('Heritage\Support\Collection<int, Exception|User>', $collection::make([new User, 1])
    ->whereInstanceOf([User::class, Exception::class]));

assertType('User|null', $collection->first());
assertType('User|null', $collection->first(function ($user) {
    assertType('User', $user);

    return true;
}));
assertType("'string'|User", $collection->first(function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));
assertType("'string'|User", $collection->first(null, function () {
    return 'string';
}));
if ($collection->isNotEmpty()) {
    assertType('User', $collection->first());
    assertType("'foo'|User", $collection->first(null, 'foo'));
} else {
    assertType('null', $collection->first());
    assertType("'foo'|User", $collection->first(null, 'foo'));
}
if ($collection->isEmpty()) {
    assertType('null', $collection->first());
    assertType("'foo'|User", $collection->first(null, 'foo'));
} else {
    assertType('User', $collection->first());
    assertType("'foo'|User", $collection->first(null, 'foo'));
}

assertType('Heritage\Support\Collection<int, mixed>', $collection->flatten());
assertType('Heritage\Support\Collection<int, mixed>', $collection::make(['string' => 'string'])->flatten(4));

assertType('User|null', $collection->firstWhere('string', 'string'));
assertType('User|null', $collection->firstWhere('string', 'string', 'string'));

assertType('User|null', $collection->value('string'));
assertType("'string'|User", $collection->value('string', 'string'));
assertType("'string'|User", $collection->value('string', fn () => 'string'));

assertType('Heritage\Support\Collection<string, int>', $collection::make(['string'])->flip());

assertType('Heritage\Support\Collection<(int|string), Heritage\Support\Collection<int, User>>', $collection->groupBy('name'));
assertType('Heritage\Support\Collection<(int|string), Heritage\Support\Collection<int, User>>', $collection->groupBy('name', true));
assertType('Heritage\Support\Collection<(int|string), Heritage\Support\Collection<int, mixed>>', $collection->groupBy(['name', 'email']));
assertType('Heritage\Support\Collection<string, Heritage\Support\Collection<int, User>>', $collection->groupBy(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return 'foo';
}));
assertType('Heritage\Support\Collection<int, Heritage\Support\Collection<int, User>>', $collection->groupBy(static fn ($user) => 0));
assertType('Heritage\Support\Collection<(int|string), Heritage\Support\Collection<int, User>>', $collection->groupBy(static fn ($user) => Digit::One));
assertType('Heritage\Support\Collection<(int|string), Heritage\Support\Collection<int, User>>', $collection->groupBy(static fn ($user) => NamedDigit::One));
assertType('Heritage\Support\Collection<(int|string), Heritage\Support\Collection<int, User>>', $collection->groupBy(static fn ($user) => NumberedDigit::One));

assertType("Heritage\Support\Collection<string, Heritage\Support\Collection<'bar', User>>", $collection->keyBy(fn ($user) => 'bar')->groupBy(function ($user) {
    return 'foo';
}, preserveKeys: true));

assertType('Heritage\Support\Collection<(int|string), User>', $collection->keyBy('name'));
assertType("Heritage\Support\Collection<'foo', User>", $collection->keyBy(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return 'foo';
}));
assertType("Heritage\Support\Collection<0, User>", $collection->keyBy(static fn ($user): int => 0));
assertType('Heritage\Support\Collection<(int|string), User>', $collection->keyBy(static fn ($user) => Digit::One));
assertType('Heritage\Support\Collection<(int|string), User>', $collection->keyBy(static fn ($user) => NamedDigit::One));
assertType('Heritage\Support\Collection<(int|string), User>', $collection->keyBy(static fn ($user) => NumberedDigit::One));

assertType('bool', $collection->has(0));
assertType('bool', $collection->has([0, 1]));

assertType('string', $collection->implode(function ($user, $index) {
    assertType('User', $user);
    assertType('int', $index);

    return 'string';
}));

assertType('Heritage\Support\Collection<int, User>', $collection->intersect([new User]));

assertType('Heritage\Support\Collection<int, User>', $collection->intersectByKeys([new User]));

assertType('Heritage\Support\Collection<int, int>', $collection->keys());

assertType('User|null', $collection->last());
assertType('User|null', $collection->last(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));
assertType("'string'|User", $collection->last(function () {
    return true;
}, 'string'));
assertType("'string'|User", $collection->last(null, function () {
    return 'string';
}));

assertType('Heritage\Support\Collection<int, int>', $collection->map(function () {
    return 1;
}));
assertType('Heritage\Support\Collection<int, string>', $collection->map(function () {
    return 'string';
}));

assertType('Heritage\Support\Collection<int, string>', $collection::make(['string'])
    ->map(function ($string, $int) {
        assertType('string', $string);
        assertType('int', $int);

        return (string) $string;
    }));

assertType('Heritage\Support\Collection<int, string>', $collection::make(['string'])
    ->mapSpread(function () {
        return 'string';
    }));

assertType('Heritage\Support\Collection<int, int>', $collection::make(['string'])
    ->mapSpread(function () {
        return 1;
    }));

assertType('Heritage\Support\Collection<string, array<int, int>>', $collection::make(['string', 'string'])
    ->mapToDictionary(function ($stringValue, $stringKey) {
        assertType('string', $stringValue);
        assertType('int', $stringKey);

        return ['string' => 1];
    }));

assertType('Heritage\Support\Collection<string, Heritage\Support\Collection<int, int>>', $collection::make(['string', 'string'])
    ->mapToGroups(function ($stringValue, $stringKey) {
        assertType('string', $stringValue);
        assertType('int', $stringKey);

        return ['string' => 1];
    }));

assertType('Heritage\Support\Collection<string, int>', $collection::make(['string'])
    ->mapWithKeys(function ($string, $int) {
        assertType('string', $string);
        assertType('int', $int);

        return ['string' => 1];
    }));

assertType('Heritage\Support\Collection<int, string>', $collection::make(['string'])
    ->flatMap(function ($string, $int) {
        assertType('string', $string);
        assertType('int', $int);

        return [0 => 'string'];
    }));

assertType('Heritage\Support\Collection<int, User>', $collection->mapInto(User::class));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->merge([2]));
assertType('Heritage\Support\Collection<int, string>', $collection::make(['string'])->merge(['string']));

assertType('Heritage\Support\Collection<int, int|string>', $collection::make([1])->merge(['string']));
assertType('Heritage\Support\Collection<int, int|string>', $collection::make(['string'])->merge([1]));

assertType('Heritage\Support\Collection<int, int|string>', $collection::make([1])->mergeRecursive([2 => 'string']));
assertType('Heritage\Support\Collection<int, string>', $collection::make(['string'])->mergeRecursive(['string']));

assertType('Heritage\Support\Collection<string, int>', $collection::make(['string' => 'string'])->combine([2]));
assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->combine([1]));
assertType('Heritage\Support\Collection<string, string>', $collection::make(['string'])->combine(['string']));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->union([1]));
assertType('Heritage\Support\Collection<string, string>', $collection::make(['string' => 'string'])->union(['string' => 'string']));

assertType('null', $collection::make()->min());
assertType('int|null', $collection::make([1])->min());
assertType('mixed', $collection::make([1])->min('string'));
assertType('mixed', $collection::make(['string' => 1])->min('string'));
assertType("'foo'|null", $collection::make([1])->min(function ($int) {
    assertType('int', $int);

    return 'foo';
}));
assertType('mixed', $collection::make([new User])->min('id'));

assertType('null', $collection::make()->max());
assertType('int|null', $collection::make([1])->max());
assertType('mixed', $collection::make([1])->max('string'));
assertType("'foo'|null", $collection::make([1])->max(function ($int) {
    assertType('int', $int);

    return 'foo';
}));
assertType('mixed', $collection::make([new User])->max('id'));

assertType('Heritage\Support\Collection<int, User>', $collection->nth(1, 2));

assertType('Heritage\Support\Collection<string, string>', $collection::make(['string' => 'string'])->only(['string']));
assertType('Heritage\Support\Collection<int, User>', $collection->only([1]));
assertType('Heritage\Support\Collection<int, string>', $collection::make(['string'])
    ->only([1]));

assertType('Heritage\Support\Collection<int, User>', $collection->forPage(1, 2));

assertType('Heritage\Support\Collection<int<0, 1>, Heritage\Support\Collection<int, User>>', $collection->partition(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));
assertType('Heritage\Support\Collection<int<0, 1>, Heritage\Support\Collection<int, string>>', $collection::make(['string'])->partition('string', '=', 'string'));
assertType('Heritage\Support\Collection<int<0, 1>, Heritage\Support\Collection<int, string>>', $collection::make(['string'])->partition('string', 'string'));
assertType('Heritage\Support\Collection<int<0, 1>, Heritage\Support\Collection<int, string>>', $collection::make(['string'])->partition('string'));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->concat([2]));
assertType('Heritage\Support\Collection<int, string>', $collection::make(['string'])->concat(['string']));
assertType('Heritage\Support\Collection<int, int|string>', $collection::make([1])->concat(['string']));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->random(2));
assertType('string', $collection::make(['string'])->random());

assertType('1|null', $collection
    ->reduce(function ($null, $user) {
        assertType('User', $user);
        assertType('1|null', $null);

        return 1;
    }));
assertType('0|1', $collection
    ->reduce(function ($int, $user) {
        assertType('User', $user);
        assertType('0|1', $int);

        return 1;
    }, 0));
assertType('0|1', $collection
    ->reduce(function ($int, $user, $key) {
        assertType('User', $user);
        assertType('0|1', $int);
        assertType('int', $key);

        return 1;
    }, 0));

assertType('1|null', $collection
    ->reduceWithKeys(function ($null, $user) {
        assertType('User', $user);
        assertType('1|null', $null);

        return 1;
    }));
assertType('0|1', $collection
    ->reduceWithKeys(function ($int, $user) {
        assertType('User', $user);
        assertType('0|1', $int);

        return 1;
    }, 0));
assertType('0|1', $collection
    ->reduceWithKeys(function ($int, $user, $key) {
        assertType('User', $user);
        assertType('0|1', $int);
        assertType('int', $key);

        return 1;
    }, 0));
assertType("'bar'|'foo'", $collection::make([])->reduce(static fn (): string => 'foo', 'bar'));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->replace([1]));
assertType('Heritage\Support\Collection<int, User>', $collection->replace([new User]));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->replaceRecursive([1]));
assertType('Heritage\Support\Collection<int, User>', $collection->replaceRecursive([new User]));

assertType('Heritage\Support\Collection<int, User>', $collection->reverse());

// assertType('int|bool', $collection::make([1])->search(2));
// assertType('string|bool', $collection::make(['string' => 'string'])->search('string'));
// assertType('int|bool', $collection->search(function ($user, $int) {
//     assertType('User', $user);
//    assertType('int', $int);
//
//    return true;
// }));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->shuffle());
assertType('Heritage\Support\Collection<int, User>', $collection->shuffle());

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->skip(1));
assertType('Heritage\Support\Collection<int, User>', $collection->skip(1));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->skipUntil(1));
assertType('Heritage\Support\Collection<int, User>', $collection->skipUntil(new User));
assertType('Heritage\Support\Collection<int, User>', $collection->skipUntil(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->skipWhile(1));
assertType('Heritage\Support\Collection<int, User>', $collection->skipWhile(new User));
assertType('Heritage\Support\Collection<int, User>', $collection->skipWhile(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->slice(1));
assertType('Heritage\Support\Collection<int, User>', $collection->slice(1, 2));

assertType('Heritage\Support\Collection<int, Heritage\Support\Collection<int, User>>', $collection->split(3));
assertType('Heritage\Support\Collection<int, Heritage\Support\Collection<int, int>>', $collection::make([1])->split(3));

assertType('string', $collection::make(['string' => 'string'])->sole('string', 'string'));
assertType('string', $collection::make(['string' => 'string'])->sole('string', '=', 'string'));
assertType('User', $collection->sole(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));

assertType('User', $collection->firstOrFail());
assertType('User', $collection->firstOrFail('string', 'string'));
assertType('User', $collection->firstOrFail('string', '=', 'string'));
assertType('User', $collection->firstOrFail(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));

assertType('Heritage\Support\Collection<int, Heritage\Support\Collection<int, string>>', $collection::make(['string'])->chunk(1));
assertType('Heritage\Support\Collection<int, Heritage\Support\Collection<int, User>>', $collection->chunk(2));
assertType('Heritage\Support\Collection<int, Heritage\Support\Collection<string, User>>', $associativeCollection->chunk(2));
assertType('Heritage\Support\Collection<int, Heritage\Support\Collection<int, User>>', $associativeCollection->chunk(2, false));

assertType('Heritage\Support\Collection<int, Heritage\Support\Collection<int, User>>', $collection->chunkWhile(function ($user, $int, $collection) {
    assertType('User', $user);
    assertType('int', $int);
    assertType('Heritage\Support\Collection<int, User>', $collection);

    return true;
}));

assertType('Heritage\Support\Collection<int, Heritage\Support\Collection<int, User>>', $collection->chunkBy(fn ($user) => $user->getKey()));
assertType('Heritage\Support\Collection<int, Heritage\Support\Collection<int, User>>', $collection->chunkBy('name'));

assertType('Heritage\Support\Collection<int, User>', $collection->sort(function ($userA, $userB) {
    assertType('User', $userA);
    assertType('User', $userB);

    return 1;
}));
assertType('Heritage\Support\Collection<int, User>', $collection->sort());

assertType('Heritage\Support\Collection<int, User>', $collection->sortDesc());
assertType('Heritage\Support\Collection<int, User>', $collection->sortDesc(2));

assertType('Heritage\Support\Collection<int, User>', $collection->sortBy(function ($user, $int) {
    // assertType('User', $user);
    // assertType('int', $int);

    return 1;
}));
assertType('Heritage\Support\Collection<int, User>', $collection->sortBy('string'));
assertType('Heritage\Support\Collection<int, User>', $collection->sortBy('string', 1, false));
assertType('Heritage\Support\Collection<int, User>', $collection->sortBy([
    ['string', 'asc'],
    ['foo', SortDirection::Descending],
]));
assertType('Heritage\Support\Collection<int, User>', $collection->sortBy([function ($user, $int) {
    // assertType('User', $user);
    // assertType('int', $int);

    return 1;
}]));

assertType('Heritage\Support\Collection<int, User>', $collection->sortByDesc(function ($user, $int) {
    // assertType('User', $user);
    // assertType('int', $int);

    return 1;
}));
assertType('Heritage\Support\Collection<int, User>', $collection->sortByDesc('string'));
assertType('Heritage\Support\Collection<int, User>', $collection->sortByDesc('string', 1));
assertType('Heritage\Support\Collection<int, User>', $collection->sortByDesc([
    ['string', 'asc'],
    ['foo', SortDirection::Descending],
]));
assertType('Heritage\Support\Collection<int, User>', $collection->sortByDesc([function ($user, $int) {
    // assertType('User', $user);
    // assertType('int', $int);

    return 1;
}]));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->sortKeys());
assertType('Heritage\Support\Collection<string, string>', $collection::make(['string' => 'string'])->sortKeys(1, true));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->sortKeysDesc());
assertType('Heritage\Support\Collection<string, string>', $collection::make(['string' => 'string'])->sortKeysDesc(1));

assertType('mixed', $collection::make([1])->sum('string'));
assertType('mixed', $collection::make([['count' => 1]])->sum('count'));
assertType('int<1, 2>', $collection::make(['string'])->sum(function ($string) {
    assertType('string', $string);

    return mt_rand(1, 2);
}));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->take(1));
assertType('Heritage\Support\Collection<int, User>', $collection->take(1));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->takeUntil(1));
assertType('Heritage\Support\Collection<int, User>', $collection->takeUntil(new User));
assertType('Heritage\Support\Collection<int, User>', $collection->takeUntil(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->takeWhile(1));
assertType('Heritage\Support\Collection<int, User>', $collection->takeWhile(new User));
assertType('Heritage\Support\Collection<int, User>', $collection->takeWhile(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));

assertType('Heritage\Support\Collection<int, User>', $collection->tap(function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);
}));

assertType('Heritage\Support\Collection<int, int>', $collection->pipe(function ($collection) {
    assertType('Heritage\Support\Collection<int, User>', $collection);

    return collect([1]);
}));
assertType('1', $collection::make([1])->pipe(function ($collection) {
    assertType('Heritage\Support\Collection<int, int>', $collection);

    return 1;
}));

assertType('User', $collection->pipeInto(User::class));

assertType('Heritage\Support\Collection<(int|string), mixed>', $collection::make(['string' => 'string'])->pluck('string'));
assertType('Heritage\Support\Collection<(int|string), mixed>', $collection::make(['string' => 'string'])->pluck('string', 'string'));

assertType('Heritage\Support\Collection<int, User>', $collection->reject());
assertType('Heritage\Support\Collection<int, User>', $collection->reject(new User));
assertType('Heritage\Support\Collection<int, User>', $collection->reject(function ($user) {
    assertType('User', $user);

    return true;
}));
assertType('Heritage\Support\Collection<int, User>', $collection->reject(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return true;
}));

assertType('Heritage\Support\Collection<int, User>', $collection->unique());
assertType('Heritage\Support\Collection<int, User>', $collection->unique(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return $user->getTable();
}));
assertType('Heritage\Support\Collection<string, string>', $collection::make(['string' => 'string'])->unique(function ($stringA, $stringB) {
    assertType('string', $stringA);
    assertType('string', $stringB);

    return $stringA;
}, true));

assertType('Heritage\Support\Collection<int, User>', $collection->uniqueStrict());
assertType('Heritage\Support\Collection<int, User>', $collection->uniqueStrict(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return $user->getTable();
}));

assertType('Heritage\Support\Collection<int, User>', $collection->values());
assertType('Heritage\Support\Collection<int, string>', $collection::make(['string', 'string'])->values());
assertType('Heritage\Support\Collection<int, int|string>', $collection::make(['string', 1])->values());

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->pad(2, 0));
assertType('Heritage\Support\Collection<int, int|string>', $collection::make([1])->pad(2, 'string'));
assertType('Heritage\Support\Collection<int, int|User>', $collection->pad(2, 0));

assertType('Heritage\Support\Collection<(int|string), int>', $collection::make([1])->countBy());
assertType('Heritage\Support\Collection<(int|string), int>', $collection::make(['string' => 'string'])->countBy('string'));
assertType('Heritage\Support\Collection<(int|string), int>', $collection::make([new User])->countBy('email'));
assertType('Heritage\Support\Collection<(int|string), int>', $collection::make([new User])->countBy(static fn ($user) => 'email'));
assertType('Heritage\Support\Collection<(int|string), int>', $collection::make([new User])->countBy(static fn ($user) => 0));
assertType('Heritage\Support\Collection<(int|string), int>', $collection::make([new User])->countBy(static fn ($user) => Digit::One));
assertType('Heritage\Support\Collection<(int|string), int>', $collection::make([new User])->countBy(static fn ($user) => NamedDigit::One));
assertType('Heritage\Support\Collection<(int|string), int>', $collection::make(['string'])->countBy(function ($string, $int) {
    assertType('string', $string);
    assertType('int', $int);

    return $string;
}));

assertType('Heritage\Support\Collection<int, Heritage\Support\Collection<int, int|User>>', $collection->zip([1]));
assertType('Heritage\Support\Collection<int, Heritage\Support\Collection<int, string|User>>', $collection->zip(['string']));
assertType('Heritage\Support\Collection<int, Heritage\Support\Collection<int, string>>', $collection::make(['string' => 'string'])->zip(['string']));

assertType('Heritage\Support\Collection<int, User>', $collection->collect());
assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->collect());

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->push(2));

assertType('array<int, User>', $collection->all());

assertType('User|null', $collection->get(0));
assertType("'string'|User", $collection->get(0, 'string'));
assertType("'string'|User", $collection->get(0, function () {
    return 'string';
}));

$getOrPutCollection = $collection::make([new User]);
assertType("'string'|User", $getOrPutCollection->getOrPut(0, 'string'));
assertType("Heritage\Support\Collection<int, 'string'|User>", $getOrPutCollection);

$getOrPutCollection = $collection::make([new User]);
assertType("'string'|User", $getOrPutCollection->getOrPut(0, fn () => 'string'));
assertType("Heritage\Support\Collection<int, 'string'|User>", $getOrPutCollection);

assertType('Heritage\Support\Collection<int, User>', $collection->forget(1));
assertType('Heritage\Support\Collection<int, User>', $collection->forget([1, 2]));

assertType('User|null', $collection->pop());
assertType('Heritage\Support\Collection<int, User>', $collection->pop(2));

assertType('Heritage\Support\Collection<int, string>', $collection::make([
    'string-key-1' => 'string-value-1',
    'string-key-2' => 'string-value-2',
])->pop(2));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->prepend(2));
assertType('Heritage\Support\Collection<int, User>', $collection->prepend(new User, 2));
assertType('Heritage\Support\Collection<int|string, int>', $collection::make(['foo' => 1])->prepend(2));
assertType('Heritage\Support\Collection<string, int>', $collection::make(['bar' => 1])->prepend(2, 'baz'));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->unshift(2));
assertType('Heritage\Support\Collection<int|string, User>', $collection::make(['foo' => new User])->unshift(new User));

assertType('Heritage\Support\Collection<int, int>', $collection::make([1])->push(2));
assertType('Heritage\Support\Collection<int, User>', $collection->push(new User, new User));
assertType('Heritage\Support\Collection<int|string, User>', $collection::make(['foo' => new User])->push(new User));

assertType('User|null', $collection->pull(1));
assertType("'string'|User", $collection->pull(1, 'string'));
assertType("'string'|User", $collection->pull(1, function () {
    return 'string';
}));

assertType('Heritage\Support\Collection<int, User>', $collection->put(1, new User));
assertType('Heritage\Support\Collection<string, string>', $collection::make([
    'string-key-1' => 'string-value-1',
])->put('string-key-2', 'string-value-2'));

assertType('User|null', $collection->shift());
assertType('Heritage\Support\Collection<int, string>', $collection::make([
    'string-key-1' => 'string-value-1',
    'string-key-2' => 'string-value-2',
])->shift(2));

assertType(
    'Heritage\Support\Collection<int, Heritage\Support\Collection<int, User>>',
    $collection->sliding(2)
);

assertType(
    'Heritage\Support\Collection<int, Heritage\Support\Collection<string, string>>',
    $collection::make(['string' => 'string'])->sliding(2, 1)
);

assertType(
    'Heritage\Support\Collection<int, Heritage\Support\Collection<int, User>>',
    $collection->splitIn(2)
);

assertType(
    'Heritage\Support\Collection<int, Heritage\Support\Collection<string, string>>',
    $collection::make(['string' => 'string'])->splitIn(1)
);

assertType('Heritage\Support\Collection<int, User>', $collection->splice(1));
assertType('Heritage\Support\Collection<int, User>', $collection->splice(1, 1, [new User]));

assertType('Heritage\Support\Collection<int, int>', $collection->transform(function ($user, $int): int {
    assertType('User', $user);
    assertType('int', $int);

    return $int * 2;
}));

assertType('Heritage\Support\Collection<int, User>', $collection->transform(function ($value, $key) {
    assertType('int', $value);
    assertType('int', $key);

    return new User;
}));

assertType('Heritage\Support\Collection<int, User>', $collection->add(new User));
assertType('Heritage\Support\Collection<int|string, User>', $collection::make(['foo' => new User])->add(new User));

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends \Heritage\Support\Collection<TKey, TValue>
 */
class CustomCollection extends Collection
{
}

// assertType('CustomCollection<int, User>', CustomCollection::make([new User]));
assertType('Heritage\Support\Collection<int, User>', CustomCollection::make([new User])->toBase());

assertType('bool', $collection->offsetExists(0));
assertType('bool', isset($collection[0]));

$collection->offsetSet(0, new User);
$collection->offsetSet(null, new User);
assertType('User', $collection[0] = new User);

$collection->offsetUnset(0);
unset($collection[0]);

assertType('array<int, mixed>', $collection->toArray());
assertType('array<string, mixed>', collect(['string' => 'string'])->toArray());
assertType('array<int, mixed>', collect([1, 2])->toArray());

assertType('ArrayIterator<int, User>', $collection->getIterator());
foreach ($collection as $int => $user) {
    assertType('int', $int);
    assertType('User', $user);
}

class Animal
{
}
class Tiger extends Animal
{
}
class Lion extends Animal
{
}
class Zebra extends Animal
{
}

class Zoo
{
    /**
     * @var \Heritage\Support\Collection<int, Animal>
     */
    private Collection $animals;

    public function __construct()
    {
        $this->animals = collect([
            new Tiger,
            new Lion,
            new Zebra,
        ]);
    }

    /**
     * @return \Heritage\Support\Collection<int, Animal>
     */
    public function getWithoutZebras(): Collection
    {
        return $this->animals->filter(fn (Animal $animal) => ! $animal instanceof Zebra);
    }
}

$zoo = new Zoo();

assertType('Heritage\Support\Collection<int, Animal>', $zoo->getWithoutZebras());

$coll = $zoo->getWithoutZebras();
assertType("Heritage\Support\HigherOrderCollectionProxy<'average', Animal, Heritage\Support\Collection<int, Animal>>", $coll->average);
assertType("Heritage\Support\HigherOrderCollectionProxy<'avg', Animal, Heritage\Support\Collection<int, Animal>>", $coll->avg);
assertType("Heritage\Support\HigherOrderCollectionProxy<'contains', Animal, Heritage\Support\Collection<int, Animal>>", $coll->contains);
assertType("Heritage\Support\HigherOrderCollectionProxy<'doesntContain', Animal, Heritage\Support\Collection<int, Animal>>", $coll->doesntContain);
assertType("Heritage\Support\HigherOrderCollectionProxy<'each', Animal, Heritage\Support\Collection<int, Animal>>", $coll->each);
assertType("Heritage\Support\HigherOrderCollectionProxy<'every', Animal, Heritage\Support\Collection<int, Animal>>", $coll->every);
assertType("Heritage\Support\HigherOrderCollectionProxy<'filter', Animal, Heritage\Support\Collection<int, Animal>>", $coll->filter);
assertType("Heritage\Support\HigherOrderCollectionProxy<'first', Animal, Heritage\Support\Collection<int, Animal>>", $coll->first);
assertType("Heritage\Support\HigherOrderCollectionProxy<'flatMap', Animal, Heritage\Support\Collection<int, Animal>>", $coll->flatMap);
assertType("Heritage\Support\HigherOrderCollectionProxy<'groupBy', Animal, Heritage\Support\Collection<int, Animal>>", $coll->groupBy);
assertType("Heritage\Support\HigherOrderCollectionProxy<'keyBy', Animal, Heritage\Support\Collection<int, Animal>>", $coll->keyBy);
assertType("Heritage\Support\HigherOrderCollectionProxy<'last', Animal, Heritage\Support\Collection<int, Animal>>", $coll->last);
assertType("Heritage\Support\HigherOrderCollectionProxy<'map', Animal, Heritage\Support\Collection<int, Animal>>", $coll->map);
assertType("Heritage\Support\HigherOrderCollectionProxy<'max', Animal, Heritage\Support\Collection<int, Animal>>", $coll->max);
assertType("Heritage\Support\HigherOrderCollectionProxy<'min', Animal, Heritage\Support\Collection<int, Animal>>", $coll->min);
assertType("Heritage\Support\HigherOrderCollectionProxy<'partition', Animal, Heritage\Support\Collection<int, Animal>>", $coll->partition);
assertType("Heritage\Support\HigherOrderCollectionProxy<'percentage', Animal, Heritage\Support\Collection<int, Animal>>", $coll->percentage);
assertType("Heritage\Support\HigherOrderCollectionProxy<'reject', Animal, Heritage\Support\Collection<int, Animal>>", $coll->reject);
assertType("Heritage\Support\HigherOrderCollectionProxy<'skipUntil', Animal, Heritage\Support\Collection<int, Animal>>", $coll->skipUntil);
assertType("Heritage\Support\HigherOrderCollectionProxy<'skipWhile', Animal, Heritage\Support\Collection<int, Animal>>", $coll->skipWhile);
assertType("Heritage\Support\HigherOrderCollectionProxy<'some', Animal, Heritage\Support\Collection<int, Animal>>", $coll->some);
assertType("Heritage\Support\HigherOrderCollectionProxy<'sortBy', Animal, Heritage\Support\Collection<int, Animal>>", $coll->sortBy);
assertType("Heritage\Support\HigherOrderCollectionProxy<'sortByDesc', Animal, Heritage\Support\Collection<int, Animal>>", $coll->sortByDesc);
assertType("Heritage\Support\HigherOrderCollectionProxy<'sum', Animal, Heritage\Support\Collection<int, Animal>>", $coll->sum);
assertType("Heritage\Support\HigherOrderCollectionProxy<'takeUntil', Animal, Heritage\Support\Collection<int, Animal>>", $coll->takeUntil);
assertType("Heritage\Support\HigherOrderCollectionProxy<'takeWhile', Animal, Heritage\Support\Collection<int, Animal>>", $coll->takeWhile);
assertType("Heritage\Support\HigherOrderCollectionProxy<'unique', Animal, Heritage\Support\Collection<int, Animal>>", $coll->unique);
assertType("Heritage\Support\HigherOrderCollectionProxy<'unless', Animal, Heritage\Support\Collection<int, Animal>>", $coll->unless);
assertType("Heritage\Support\HigherOrderCollectionProxy<'until', Animal, Heritage\Support\Collection<int, Animal>>", $coll->until);
assertType("Heritage\Support\HigherOrderCollectionProxy<'when', Animal, Heritage\Support\Collection<int, Animal>>", $coll->when);

enum Digit
{
    case One;
    case Two;
    case Three;
}

enum NamedDigit: string
{
    case One = 'one';
    case Two = 'two';
    case Three = 'three';
}

enum NumberedDigit: int
{
    case One = 1;
    case Two = 2;
    case Three = 3;
}
