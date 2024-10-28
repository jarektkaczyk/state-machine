<?php

namespace Sofa\StateMachine;

class Fsm
{
    /** @var array */
    private $transitions = [];
    /** @var string */
    private $state;
    /** @var \Sofa\StateMachine\StateMachineInterface */
    private $stateful_object;

    /**
     * @param \Sofa\StateMachine\StateMachineInterface $stateful_object
     * @param \Sofa\StateMachine\Transition[] $transitions
     */
    public function __construct(StateMachineInterface $stateful_object, array $transitions)
    {
        $this->state = $stateful_object->getCurrentState();
        $this->stateful_object = $stateful_object;

        foreach ($transitions as $transition) {
            $this->addTransition($transition);
        }
    }

    protected function addTransition(Transition $transition) : void
    {
        $from_state = $transition->from_state;
        $action = $transition->action;

        if (isset($this->transitions[$from_state][$action])) {
            throw new DuplicateActionException('Transition for action [' . $action . '] already registered');
        } elseif (!isset($this->transitions[$from_state])) {
            $this->transitions[$from_state] = [];
        }

        $this->transitions[$from_state][$action] = $transition;
    }

    /**
     * Get actions available in current state.
     *
     * @return string[]
     */
    public function getAvailableActions() : array
    {
        return array_keys($this->transitions[$this->state] ?? []);
    }

    /**
     * Determine whether provided action is available in current state.
     *
     * @param  string|Transition  $action
     * @return bool
     */
    public function isActionValid(string|Transition $action) : bool
    {
        return in_array(is_string($action) ? $action : $action->action, $this->getAvailableActions());
    }

    /**
     * Get current state of the machine.
     *
     * @return string
     */
    public function getCurrentState() : string
    {
        return $this->state;
    }

    /**
     * Process action and return new state after transition.
     *
     * @param  string|Transition $action
     * @param  mixed $payload
     * @return string
     */
    public function process(string|Transition $action, $payload = null) : string
    {
        $action = is_string($action) ? $action : $action->action;

        if (!$this->isActionValid($action)) {
            throw new InvalidActionException(
                'Provided action [' . $action . '] is not available in current state [' . $this->state . ']'
            );
        }

        $transition = $this->transitions[$this->state][$action];

        if (is_callable($transition)) {
            $transition($this->stateful_object, $payload);
        } else {
            $this->stateful_object->setState($transition->to_state);
        }

        return $this->state = $transition->to_state;
    }
}
