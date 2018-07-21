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
     * @param  string  $action
     * @return bool
     */
    public function isActionValid(string $action) : bool
    {
        return in_array($action, $this->getAvailableActions());
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
     * @param  string $action
     * @return string
     */
    public function process(string $action) : string
    {
        if (!$this->isActionValid($action)) {
            throw new InvalidActionException('Provided action is not available in current state');
        }

        $transition = $this->transitions[$this->state][$action];

        $this->stateful_object->setState($transition->to_state);

        return $this->state = $transition->to_state;
    }
}
