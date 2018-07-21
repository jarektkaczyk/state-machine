<?php

namespace Sofa\Unit\StateMachine;

use Sofa\StateMachine\Fsm;
use PHPUnit\Framework\TestCase;
use Sofa\StateMachine\Transition;
use Sofa\StateMachine\StateMachineInterface;

class FsmTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->stateful_object = new StatefulDouble;
        $this->fsm = new Fsm($this->stateful_object, [
            Transition::make('off', 'start', 'idle'),
            Transition::make('idle', 'turn_off', 'off'),
            Transition::make('idle', 'move', 'moving'),
            Transition::make('moving', 'stop', 'idle'),
            Transition::make('moving', 'turn_off', 'california_cruisin'),
        ]);
    }

    /** @test */
    public function it_transitions_machine_between_states()
    {
        $this->assertEquals('off', $this->fsm->getCurrentState());
        $this->fsm->process('start');
        $this->fsm->process('move');
        $this->assertEquals('moving', $this->fsm->getCurrentState());
    }

    /** @test */
    public function it_puts_machine_object_in_proper_state()
    {
        $this->assertEquals('off', $this->fsm->getCurrentState());
        $this->fsm->process('start');
        $this->fsm->process('move');
        $this->fsm->process('turn_off');

        $this->assertEquals('california_cruisin', $this->stateful_object->getCurrentState());
    }

    /**
     * @test
     * @expectedException Sofa\StateMachine\DuplicateActionException
     */
    public function it_rejects_invalid_transition_definitions()
    {
        new Fsm(new StatefulDouble, [
            Transition::make('off', 'start', 'idle'),
            Transition::make('off', 'start', 'moving'),
        ]);
    }

    /**
     * @test
     * @expectedException Sofa\StateMachine\InvalidActionException
     */
    public function it_rejects_invalid_action_for_processing()
    {
        $this->fsm->process('move');
    }
}

class StatefulDouble implements StateMachineInterface
{
    public $state = 'off';

    public function getCurrentState() : string
    {
        return $this->state;
    }

    public function setState(string $state) : void
    {
        $this->state = $state;
    }
}
