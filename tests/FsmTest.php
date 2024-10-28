<?php

namespace Sofa\Unit\StateMachine;

use PHPUnit\Framework\TestCase;
use Sofa\StateMachine\DuplicateActionException;
use Sofa\StateMachine\Fsm;
use Sofa\StateMachine\InvalidActionException;
use Sofa\StateMachine\StateMachineInterface;
use Sofa\StateMachine\Transition;

class FsmTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->stateful_object = new StatefulDouble;
        $this->fsm = new Fsm($this->stateful_object, [
            Transition::make('off', 'start', 'idle'),
            Transition::make('idle', 'turn_off', 'off'),
            Transition::make('idle', 'move', 'moving'),
            Transition::make('moving', 'stop', 'idle'),
            Transition::make('moving', 'turn_off', 'california_cruisin'),
            CustomTransition::make('off', 'custom_start', 'idle'),
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
    public function it_transitions_machine_between_object_states()
    {
        $this->assertEquals('off', $this->fsm->getCurrentState());
        $this->fsm->process(CustomTransition::make('off', 'custom_start', 'idle'));
        $this->assertEquals('idle', $this->fsm->getCurrentState());
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

    /** @test */
    public function it_allows_customizing_transitions_logic()
    {
        $this->fsm->process('custom_start', ['prop' => 'customized']);
        $this->assertEquals('customized', $this->stateful_object->prop);
    }

    /** @test */
    public function it_rejects_invalid_transition_definitions()
    {
        $this->expectException(DuplicateActionException::class);
        new Fsm(new StatefulDouble, [
            Transition::make('off', 'start', 'idle'),
            Transition::make('off', 'start', 'moving'),
        ]);
    }

    /** @test */
    public function it_rejects_invalid_action_for_processing()
    {
        $this->expectException(InvalidActionException::class);
        $this->fsm->process('move');
    }
}

class StatefulDouble implements StateMachineInterface
{
    public $state = 'off';
    public $prop = 'initial';

    public function getCurrentState() : string
    {
        return $this->state;
    }

    public function setState(string $state) : void
    {
        $this->state = $state;
    }
}

class CustomTransition extends Transition
{
    public function __invoke($stateful_object, $payload)
    {
        $stateful_object->prop = $payload['prop'];
        $stateful_object->setState($this->to_state);
    }
}
