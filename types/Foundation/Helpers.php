<?php

use Heritage\Config\Repository;

use function PHPStan\Testing\assertType;

assertType('Heritage\Foundation\Application', app());
assertType('mixed', app('foo'));
assertType('Heritage\Config\Repository', app(Repository::class));

assertType('Heritage\Contracts\Auth\Factory', auth());
assertType('Heritage\Contracts\Auth\Guard', auth('foo'));

assertType('Heritage\Cache\CacheManager', cache());
assertType('bool', cache(['foo' => 'bar'], 42));
assertType('mixed', cache('foo', 42));

assertType('Heritage\Config\Repository', config());
assertType('null', config(['foo' => 'bar']));
assertType('mixed', config('foo'));

assertType('Heritage\Log\Context\Repository', context());
assertType('Heritage\Log\Context\Repository', context(['foo' => 'bar']));
assertType('mixed', context('foo'));

assertType('Heritage\Cookie\CookieJar', cookie());
assertType('Symfony\Component\HttpFoundation\Cookie', cookie('foo'));

assertType('Heritage\Foundation\Bus\PendingDispatch', dispatch('foo'));
assertType('Heritage\Foundation\Bus\PendingClosureDispatch', dispatch(fn () => 1));

assertType('Psr\Log\LoggerInterface', logger());
assertType('null', logger('foo'));

assertType('Heritage\Log\LogManager', logs());
assertType('Psr\Log\LoggerInterface', logs('foo'));

assertType('123|null', rescue(fn () => 123));
assertType('123|345', rescue(fn () => 123, 345));
assertType('123|345', rescue(fn () => 123, fn () => 345));

assertType('Heritage\Routing\Redirector', redirect());
assertType('Heritage\Http\RedirectResponse', redirect('foo'));

assertType('mixed', resolve('foo'));
assertType('Heritage\Config\Repository', resolve(Repository::class));

assertType('Heritage\Http\Request', request());
assertType('mixed', request('foo'));
assertType('array<string, mixed>', request(['foo', 'bar']));

assertType('Heritage\Contracts\Routing\ResponseFactory', response());
assertType('Heritage\Http\Response', response('foo'));

assertType('Heritage\Session\SessionManager', session());
assertType('mixed', session('foo'));
assertType('null', session(['foo' => 'bar']));

assertType('Heritage\Contracts\Translation\Translator', trans());
assertType('array|string', trans('foo'));

assertType('Heritage\Contracts\Validation\Factory', validator());
assertType('Heritage\Contracts\Validation\Validator', validator([]));

assertType('Heritage\Contracts\View\Factory', view());
assertType('Heritage\Contracts\View\View', view('foo'));

assertType('Heritage\Contracts\Routing\UrlGenerator', url());
assertType('string', url('foo'));
