<?php

namespace Sofa\StateMachine;

interface StateMachineInterface
{
    public function getCurrentState() : string;

    public function setState(string $state) : void;
}
