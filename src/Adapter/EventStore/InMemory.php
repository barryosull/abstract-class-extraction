<?php namespace Projectionist\Adapter\EventStore;

use Projectionist\Adapter\EventWrapper;
use Projectionist\Adapter\EventStream;

class InMemory implements \Projectionist\Adapter\EventStore
{
    private static $events = [];

    public static function setEvents(array $events)
    {
        self::$events = $events;
    }

    public function hasEvents(): bool
    {
        return count(self::$events) != 0;
    }

    public function latestEvent(): \Projectionist\Adapter\EventWrapper
    {
        $event = last(self::$events);
        if (!$event) {
            throw new \Exception("No events in the EventStore");
        }
        return new EventWrapper\Identifiable($event);
    }

    public function getStream($last_event_id): \Projectionist\Adapter\EventStream
    {
        return new EventStream\InMemory(self::$events);
    }


}