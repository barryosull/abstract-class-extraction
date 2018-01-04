<?php namespace ProjectonistTests\Integration\Projectionist\Service\EventStore;

use Projectionist\Adapter\Event;
use Projectionist\Adapter\EventStream;

abstract class EventStreamTest extends \PHPUnit_Framework_TestCase
{
    abstract protected function makeEventStream(): EventStream;

    public function test_can_get_next_event()
    {
        $stream = $this->makeEventStream();

        $event = $stream->next();

        $this->assertInstanceOf(Event::class, $event);
    }

    public function test_returns_null_when_no_more_events()
    {
        $stream = $this->makeEventStream();

        $stream->next();

        $event_b = $stream->next();

        $this->assertNull($event_b);
    }
}