<?php namespace ProjectonistTests\Acceptance;

use Projectionist\Adapter\EventLog;
use Projectionist\Adapter\ProjectorPositionLedger;
use Projectionist\ConfigFactory;
use Projectionist\ProjectionistFactory;
use Projectionist\Services\ProjectorException;
use Projectionist\ValueObjects\ProjectorPosition;
use Projectionist\ValueObjects\ProjectorPositionCollection;
use Projectionist\ValueObjects\ProjectorReferenceCollection;
use Projectionist\ValueObjects\ProjectorStatus;
use ProjectonistTests\Fakes\Projectors\BrokenProjector;
use ProjectonistTests\Fakes\Projectors\RunFromLaunch;
use ProjectonistTests\Fakes\Projectors\RunFromStart;
use ProjectonistTests\Fakes\Projectors\RunOnce;
use ProjectonistTests\Fakes\Services\EventLog\ThingHappened;

class ProjectionistPlayProjectorsTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProjectionistFactory */
    private $projectionist_factory;

    /** @var ProjectorPositionLedger */
    private $projector_position_ledger;

    /** @var EventLog\InMemory */
    private $event_log;

    public function setUp()
    {
        $config = (new ConfigFactory\InMemory)->make();
        $config->projectorPositionLedger()->clear();
        $this->projectionist_factory = new ProjectionistFactory($config);
        $this->projector_position_ledger = $config->projectorPositionLedger();
        $this->event_log = $config->eventLog();
        $this->event_log->reset();
    }

    const EVENT_1_ID = '94ae0b60-ddb4-4cf0-bb75-4b588fea3c3c';
    const EVENT_2_ID = '359e43d8-025e-49ec-a017-3a99c1ce89ba';

    private function seedEvent(string $event_id)
    {
        $event = new ThingHappened($event_id);
        $this->event_log->appendEvent($event);
    }

    public function test_plays_projectors_up_till_the_latest_event()
    {
        $this->seedEvent(self::EVENT_1_ID);
        $projectors = [new RunFromLaunch, new RunFromStart];
        $projectionist = $this->projectionist_factory->make($projectors);

        $projectionist->play();

        $projector_refs = ProjectorReferenceCollection::fromProjectors($projectors);
        $stored_projector_positions = $this->projector_position_ledger->fetchCollection($projector_refs);

        $this->assertProjectorsAreAtPosition($projectors, self::EVENT_1_ID, $stored_projector_positions);
    }

    private function assertProjectorsAreAtPosition(array $projectors, string $expected_position, ProjectorPositionCollection $positions)
    {
        $this->assertCount(count($projectors), $positions);
        $this->assertProjectorsHaveProcessedEvent($projectors, $expected_position);
        $positions->each(function(ProjectorPosition $position) use ($expected_position) {
           $this->assertEquals($expected_position, $position->last_position);
           $this->assertEquals(ProjectorStatus::working(), $position->status);
        });
    }

    private function assertProjectorsHaveProcessedEvent(array $projectors, string $event_id)
    {
        foreach ($projectors as $projector) {
            $this->assertTrue($projector::hasProjectedEvent($event_id));
        }
    }

    public function test_does_not_play_run_once_projectors()
    {
        $this->seedEvent(self::EVENT_1_ID);
        $projectors = [new RunFromLaunch, new RunFromStart, new RunOnce()];
        $projectionist = $this->projectionist_factory->make($projectors);

        $projectionist->play();

        $this->assertFalse(RunOnce::hasProjectedEvent(self::EVENT_1_ID));
    }

    public function test_playing_a_broken_projector_fails_elegantly()
    {
        $this->seedEvent(self::EVENT_1_ID);
        $projectors = [new RunFromLaunch, new RunFromStart, new BrokenProjector()];
        $projectionist = $this->projectionist_factory->make($projectors);

        $this->expectException(ProjectorException::class);

        $projectionist->play();
    }

    public function test_playing_after_a_failure_continues_normally()
    {
        $this->seedEvent(self::EVENT_1_ID);
        $projectors = [new RunFromLaunch, new RunFromStart, new BrokenProjector];
        $projectionist = $this->projectionist_factory->make($projectors);

        $first_play_failed = false;
        try {
            $projectionist->play();
        } catch (\Throwable $e) {
            $first_play_failed = true;
        }

        $this->assertTrue($first_play_failed);

        $this->seedEvent(self::EVENT_2_ID);

        $projectionist->play();

        $expected_projectors = [new RunFromLaunch, new RunFromStart];
        $projector_refs = ProjectorReferenceCollection::fromProjectors($expected_projectors);
        $stored_projector_positions = $this->projector_position_ledger->fetchCollection($projector_refs);

        $this->assertProjectorsAreAtPosition($expected_projectors, self::EVENT_2_ID, $stored_projector_positions);
    }
}