<?php

namespace Sofa\StateMachine;

/**
 * Simple POPO to handle State Machine transitions
 */
class Transition
{
    /** @var string */
    public $from_state;
    /** @var string */
    public $action;
    /** @var string */
    public $to_state;

    protected function __construct($from_state, $action, $to_state)
    {
        $this->action = $action;
        $this->to_state = $to_state;
        $this->from_state = $from_state;
    }

    public static function make(string $from_state, string $action, string $to_state) : self
    {
        return new static($from_state, $action, $to_state);
    }

    // Extend this class and implement __invoke method to have more flexibility in your transition.
    //
    // public function __invoke(StateMachineInterface $stateful_object, $payload)
    // {
    //     // do more here if necessary
    //
    //     $stateful_object->setState($this->to_state);
    // }
}
