<?php namespace Tests\Unit\ValueObjects;

use App\ValueObjects\ProjectorMode;
use App\ValueObjects\ProjectorReference;
use Tests\Fakes\Projectors\NoModeProjector;
use Tests\Fakes\Projectors\RunFromStart;
use Tests\Fakes\Projectors\RunOnce;

class ProjectorReferenceTest extends \PHPUnit_Framework_TestCase
{
    public function test_reads_mode_from_projector()
    {
        $ref = new ProjectorReference(RunOnce::class);

        $this->assertEquals(ProjectorMode::RUN_ONCE, $ref->mode());
    }

    public function test_gives_default_mode_if_none_set()
    {
        $ref = new ProjectorReference(NoModeProjector::class);

        $this->assertEquals(ProjectorMode::RUN_FROM_START, $ref->mode());
    }
}