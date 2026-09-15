<?php

namespace Heritage\Tests\Integration\Queue\Fixtures;

use Heritage\Database\Eloquent\Collection;
use Heritage\Queue\SerializesModels;
use Heritage\Tests\Integration\Queue\ModelSerializationTestUser;

class TypedPropertyTestClass
{
    use SerializesModels;

    public ModelSerializationTestUser $user;

    public ModelSerializationTestUser $uninitializedUser;

    protected int $id;

    private array $names;

    public function __construct(ModelSerializationTestUser $user, int $id, array $names)
    {
        $this->user = $user;
        $this->id = $id;
        $this->names = $names;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getNames()
    {
        return $this->names;
    }
}

class TypedPropertyCollectionTestClass
{
    use SerializesModels;

    public Collection $users;

    public function __construct(Collection $users)
    {
        $this->users = $users;
    }
}
