<?php

namespace Sofa\StateMachine;

/**
 * simple POPO to handle State Machine transitions
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
}
