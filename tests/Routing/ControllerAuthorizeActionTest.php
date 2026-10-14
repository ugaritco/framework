<?php

namespace Heritage\Tests\Routing;

use Heritage\Auth\Access\AuthorizationException;
use Heritage\Auth\Access\Gate;
use Heritage\Capabilities\Attributes\Authorize;
use Heritage\Capabilities\Capability;
use Heritage\Container\Container;
use Heritage\Contracts\Auth\Access\Gate as GateContract;
use Heritage\Database\Eloquent\Model;
use Heritage\Http\Request;
use Heritage\Outcomes\Outcome;
use Heritage\Parcels\Parcel;
use Heritage\Routing\Controller;
use Heritage\Support\Facades\Facade;
use LogicException;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * A Capability or Feature declaring #[Authorize] runs from a controller only once its ability is granted.
 */
class ControllerAuthorizeActionTest extends TestCase
{
    protected Container $previousContainer;

    /**
     * Boot a container with a gate whose user is always present, and register the policy.
     */
    protected function setUp(): void
    {
        $this->previousContainer = Container::getInstance();

        $container = new Container;
        Container::setInstance($container);
        Facade::setFacadeApplication($container);

        $gate = new Gate($container, fn () => (object) ['id' => 1]);
        $gate->policy(AuthorizedRecord::class, AuthorizedRecordPolicy::class);
        $container->instance(GateContract::class, $gate);

        AuthorizedCapability::$runs = 0;
        AuthorizedRecordPolicy::$canCreate = false;
    }

    /**
     * Restore the global container.
     */
    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Container::setInstance($this->previousContainer);
        Facade::setFacadeApplication($this->previousContainer);
    }

    public function test_the_ability_is_checked_against_the_parcel_model(): void
    {
        $outcome = (new AuthorizingController)->call(AuthorizedCapability::class, (new AuthorizedParcel)->setModel(new AuthorizedRecord(['allowed' => true])));

        $this->assertTrue($outcome->isSuccess());
        $this->assertSame(1, AuthorizedCapability::$runs);
    }

    public function test_a_denied_ability_stops_the_action_before_it_runs(): void
    {
        try {
            (new AuthorizingController)->call(AuthorizedCapability::class, (new AuthorizedParcel)->setModel(new AuthorizedRecord(['allowed' => false])));
            $this->fail('The ability should have been denied.');
        } catch (AuthorizationException) {
            $this->assertSame(0, AuthorizedCapability::$runs);
        }
    }

    public function test_without_a_parcel_model_the_ability_is_checked_on_the_model_class(): void
    {
        $this->expectException(AuthorizationException::class);

        (new AuthorizingController)->call(AuthorizedCapability::class, new AuthorizedParcel);
    }

    public function test_a_class_level_ability_can_be_granted(): void
    {
        AuthorizedRecordPolicy::$canCreate = true;

        $this->assertTrue((new AuthorizingController)->call(AuthorizedCapability::class, null)->isSuccess());
    }

    public function test_an_action_without_the_attribute_is_not_checked(): void
    {
        $this->assertTrue((new AuthorizingController)->call(OpenCapability::class, null)->isSuccess());
    }

    public function test_an_ability_requiring_a_model_fails_loudly_without_one(): void
    {
        try {
            (new AuthorizingController)->call(InstanceCapability::class, new AuthorizedParcel);
            $this->fail('A missing model should have been reported.');
        } catch (LogicException $e) {
            $this->assertStringContainsString(InstanceCapability::class, $e->getMessage());
            $this->assertStringContainsString(AuthorizedRecord::class, $e->getMessage());
        }
    }

    public function test_an_ability_granted_by_the_form_request_is_not_checked_again(): void
    {
        $request = new Request;
        $request->attributes->set(Authorize::GRANTED, InstanceCapability::class);
        Container::getInstance()->instance('request', $request);

        $this->assertTrue((new AuthorizingController)->call(InstanceCapability::class, new AuthorizedParcel)->isSuccess());
        $this->expectException(AuthorizationException::class);
        (new AuthorizingController)->call(AuthorizedCapability::class, new AuthorizedParcel);
    }

    public function test_an_ability_requiring_a_model_runs_with_one(): void
    {
        $outcome = (new AuthorizingController)->call(InstanceCapability::class, (new AuthorizedParcel)->setModel(new AuthorizedRecord(['allowed' => true])));

        $this->assertTrue($outcome->isSuccess());
    }
}

class AuthorizingController extends Controller
{
    /**
     * Expose run() to the test.
     *
     * @param  string  $actionClass
     * @param  Parcel|null  $data
     * @return Outcome
     */
    public function call(string $actionClass, ?Parcel $data): Outcome
    {
        return $this->run($actionClass, $data);
    }
}

class AuthorizedRecord extends Model
{
    protected $guarded = [];
}

class AuthorizedRecordPolicy
{
    public static bool $canCreate = false;

    /**
     * Save a record: the record's flag decides an update, $canCreate a creation.
     *
     * @param  object  $user
     * @param  AuthorizedRecord|null  $record
     * @return bool
     */
    public function save(object $user, ?AuthorizedRecord $record = null): bool
    {
        return $record === null ? static::$canCreate : (bool) $record->allowed;
    }

    /**
     * View a record: always needs the record itself.
     *
     * @param  object  $user
     * @param  AuthorizedRecord  $record
     * @return bool
     */
    public function view(object $user, AuthorizedRecord $record): bool
    {
        return (bool) $record->allowed;
    }
}

class AuthorizedParcel extends Parcel
{
    //
}

abstract class TestCapability extends Capability
{
    /**
     * Fail without details.
     *
     * @param  Throwable  $e
     * @return Outcome
     */
    protected function failed(Throwable $e): Outcome
    {
        return Outcome::failure();
    }
}

#[Authorize('save', AuthorizedRecord::class)]
class AuthorizedCapability extends TestCapability
{
    public static int $runs = 0;

    /**
     * Count the run.
     *
     * @param  AuthorizedParcel|null  $data
     * @return Outcome
     */
    protected function perform(?AuthorizedParcel $data = null): Outcome
    {
        static::$runs++;

        return Outcome::success();
    }
}

#[Authorize('view', AuthorizedRecord::class)]
class InstanceCapability extends TestCapability
{
    /**
     * Succeed.
     *
     * @return Outcome
     */
    protected function perform(): Outcome
    {
        return Outcome::success();
    }
}

class OpenCapability extends TestCapability
{
    /**
     * Succeed.
     *
     * @return Outcome
     */
    protected function perform(): Outcome
    {
        return Outcome::success();
    }
}
