<?php

use function PHPStan\Testing\assertType;

$collection = User::all();
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection);

assertType('User|null', $collection->find(1));
assertType("'string'|User", $collection->find(1, 'string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->find([1]));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->load('string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->load(['string']));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->load(['string' => ['foo' => fn ($q) => $q]]));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->load(['string' => function ($query) {
    // assertType('Heritage\Database\Eloquent\Relations\Relation<*,*,*>', $query);
}]));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadAggregate('string', 'string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadAggregate(['string'], 'string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadAggregate(['string' => ['foo' => fn ($q) => $q]], 'string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadAggregate(['string'], 'string', 'string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadAggregate(['string' => function ($query) {
    // assertType('Heritage\Database\Eloquent\Relations\Relation<*,*,*>', $query);
}], 'string', 'string'));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadCount('string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadCount(['string']));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadCount(['string' => ['foo' => fn ($q) => $q]]));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadCount(['string' => function ($query) {
    // assertType('Heritage\Database\Eloquent\Relations\Relation<*,*,*>', $query);
}]));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMax('string', 'string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMax(['string'], 'string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMax(['string' => ['foo' => fn ($q) => $q]], 'string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMax(['string' => function ($query) {
    // assertType('Heritage\Database\Eloquent\Relations\Relation<*,*,*>', $query);
}], 'string'));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMin('string', 'string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMin(['string'], 'string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMin(['string' => ['foo' => fn ($q) => $q]], 'string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMin(['string' => function ($query) {
    // assertType('Heritage\Database\Eloquent\Relations\Relation<*,*,*>', $query);
}], 'string'));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadSum('string', 'string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadSum(['string'], 'string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadSum(['string' => ['foo' => fn ($q) => $q]], 'string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadSum(['string' => function ($query) {
    // assertType('Heritage\Database\Eloquent\Relations\Relation<*,*,*>', $query);
}], 'string'));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadAvg('string', 'string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadAvg(['string'], 'string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadAvg(['string' => ['foo' => fn ($q) => $q]], 'string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadAvg(['string' => function ($query) {
    // assertType('Heritage\Database\Eloquent\Relations\Relation<*,*,*>', $query);
}], 'string'));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadExists('string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadExists(['string']));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadExists(['string' => ['foo' => fn ($q) => $q]]));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadExists(['string' => function ($query) {
    // assertType('Heritage\Database\Eloquent\Relations\Relation<*,*,*>', $query);
}]));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMissing('string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMissing(['string']));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMissing(['string' => ['foo' => fn ($q) => $q]]));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMissing(['string' => function ($query) {
    // assertType('Heritage\Database\Eloquent\Relations\Relation<*,*,*>', $query);
}]));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMorph('string', ['string']));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMorph('string', ['string' => ['foo' => fn ($q) => $q]]));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMorph('string', ['string' => function ($query) {
    // assertType('Heritage\Database\Eloquent\Relations\Relation<*,*,*>', $query);
}]));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMorphCount('string', ['string']));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMorphCount('string', ['string' => ['foo' => fn ($q) => $q]]));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->loadMorphCount('string', ['string' => function ($query) {
    // assertType('Heritage\Database\Eloquent\Relations\Relation<*,*,*>', $query);
}]));

assertType('bool', $collection->contains(function ($user) {
    assertType('User', $user);

    return true;
}));
assertType('bool', $collection->contains('string', '=', 'string'));

assertType('array<int, (int|string)>', $collection->modelKeys());

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->merge($collection));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->merge([new User]));

assertType(
    'Heritage\Support\Collection<int, User>',
    $collection->map(function ($user, $int) {
        assertType('User', $user);
        assertType('int', $int);

        return new User;
    })
);

assertType(
    'Heritage\Support\Collection<int, User>',
    $collection->mapWithKeys(function ($user, $int) {
        assertType('User', $user);
        assertType('int', $int);

        return [new User];
    })
);
assertType(
    'Heritage\Support\Collection<string, User>',
    $collection->mapWithKeys(function ($user, $int) {
        return ['string' => new User];
    })
);

assertType(
    'Heritage\Database\Eloquent\Collection<int, User>',
    $collection->fresh()
);
assertType(
    'Heritage\Database\Eloquent\Collection<int, User>',
    $collection->fresh('string')
);
assertType(
    'Heritage\Database\Eloquent\Collection<int, User>',
    $collection->fresh(['string'])
);

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->diff($collection));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->diff([new User]));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->intersect($collection));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->intersect([new User]));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->unique());
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->unique(function ($user, $int) {
    assertType('User', $user);
    assertType('int', $int);

    return $user->getTable();
}));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->unique('string'));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->only(null));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->only(['string']));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->except(null));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->except(['string']));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->makeHidden('string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->makeHidden(['string']));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->makeVisible('string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->makeVisible(['string']));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->append('string'));
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->append(['string']));

assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->unique());
assertType('Heritage\Database\Eloquent\Collection<int, User>', $collection->uniqueStrict());

assertType('array<User>', $collection->getDictionary());
assertType('array<User>', $collection->getDictionary($collection));
assertType('array<User>', $collection->getDictionary([new User]));

assertType('Heritage\Support\Collection<(int|string), mixed>', $collection->pluck('string'));
assertType('Heritage\Support\Collection<(int|string), mixed>', $collection->pluck(['string']));

assertType('Heritage\Support\Collection<int, int>', $collection->keys());

assertType('Heritage\Support\Collection<int, Heritage\Support\Collection<int, int|User>>', $collection->zip([1]));
assertType('Heritage\Support\Collection<int, Heritage\Support\Collection<int, string|User>>', $collection->zip(['string']));

assertType('Heritage\Support\Collection<int, mixed>', $collection->collapse());

assertType('Heritage\Support\Collection<int, mixed>', $collection->flatten());
assertType('Heritage\Support\Collection<int, mixed>', $collection->flatten(4));

assertType('Heritage\Support\Collection<User, int>', $collection->flip());

assertType('Heritage\Support\Collection<int, int|User>', $collection->pad(2, 0));
assertType('Heritage\Support\Collection<int, string|User>', $collection->pad(2, 'string'));

assertType('array<int, mixed>', $collection->getQueueableIds());

assertType('array<int, string>', $collection->getQueueableRelations());

assertType('Heritage\Database\Eloquent\Builder<User>', $collection->toQuery());
