<?php namespace ProjectonistTests\Fakes\Services\EventStore;

class ThingHappened
{
    private $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function id()
    {
        return $this->id;
    }
}