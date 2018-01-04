<?php namespace Projectionist\Config\InMemory;

class EventStore implements \Projectionist\Adapter\EventStore
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

    public function latestEvent(): \Projectionist\Adapter\Event
    {
        $event = last(self::$events);
        if (!$event) {
            throw new \Exception("No events in the EventStore");
        }
        return new Event($event);
    }

    public function getStream($last_event_id): \Projectionist\Adapter\EventStream
    {
        return new EventStream(self::$events);
    }


}