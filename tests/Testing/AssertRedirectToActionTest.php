<?php

namespace Heritage\Tests\Testing;

use Heritage\Contracts\Routing\Registrar;
use Heritage\Http\RedirectResponse;
use Heritage\Routing\Controller;
use Heritage\Routing\UrlGenerator;
use Heritage\Support\Facades\Facade;
use Orchestra\Testbench\TestCase;

class AssertRedirectToActionTest extends TestCase
{
    /**
     * @var \Heritage\Routing\UrlGenerator
     */
    public $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $router = $this->app->make(Registrar::class);

        $router->get('controller/index', [TestActionController::class, 'index']);
        $router->get('controller/show/{id}', [TestActionController::class, 'show']);

        $router->get('redirect-to-index', function () {
            return new RedirectResponse($this->urlGenerator->action([TestActionController::class, 'index']));
        });

        $router->get('redirect-to-show', function () {
            return new RedirectResponse($this->urlGenerator->action([TestActionController::class, 'show'], ['id' => 123]));
        });

        $this->urlGenerator = $this->app->make(UrlGenerator::class);
    }

    public function testAssertRedirectToActionWithoutParameters(): void
    {
        $this->get('redirect-to-index')
            ->assertRedirectToAction([TestActionController::class, 'index']);
    }

    public function testAssertRedirectToActionWithParameters(): void
    {
        $this->get('redirect-to-show')
            ->assertRedirectToAction([TestActionController::class, 'show'], ['id' => 123]);
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication(null);

        parent::tearDown();
    }
}

class TestActionController extends Controller
{
    public function index()
    {
        return 'ok';
    }

    public function show($id)
    {
        return "id: $id";
    }
}
