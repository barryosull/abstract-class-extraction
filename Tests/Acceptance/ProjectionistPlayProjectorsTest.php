<?php namespace ProjectonistTests\Acceptance;

use Projectionist\AdapterFactory;
use Projectionist\ProjectionistFactory;
use Projectionist\ValueObjects\ProjectorReferenceCollection;
use ProjectonistTests\Fakes\Projectors\RunFromLaunch;
use ProjectonistTests\Fakes\Projectors\RunFromStart;

class ProjectionistPlayProjectorsTest extends \PHPUnit_Framework_TestCase
{
    public function test_does_not_play_run_once_projectors()
    {
        $adapter_factory = new AdapterFactory\InMemory();
        $projectionist_factory = new ProjectionistFactory($adapter_factory);
        $projectors = [new RunFromLaunch, new RunFromStart];
        $projectionist = $projectionist_factory->make($projectors);
        $adapter_factory->projectorPositionLedger()->reset();

        $projectionist->play();

        $stored_projector_positions = $adapter_factory->projectorPositionLedger()->all();

        $actual = $stored_projector_positions->references();

        $expected = ProjectorReferenceCollection::fromProjectors($projectors);

        $this->assertEquals($expected, $actual);
    }
}