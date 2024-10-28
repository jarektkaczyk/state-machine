# Sofa/StateMachine

#### [Finite State Machine](https://en.wikipedia.org/wiki/Finite-state_machine) implementation

[![Downloads](https://poser.pugx.org/sofa/state-machine/downloads)](https://packagist.org/packages/sofa/state-machine) [![stable](https://poser.pugx.org/sofa/state-machine/v/stable.svg)](https://packagist.org/packages/sofa/state-machine)
[![Coverage Status](https://coveralls.io/repos/github/jarektkaczyk/state-machine/badge.svg?branch=master)](https://coveralls.io/github/jarektkaczyk/state-machine?branch=master)


## Installation

Add package to your project:

```
composer require sofa/state-machine
```


## Usage

State machine helps you eliminate `switch` and/or `if/else` statements in your code to determine available actions in given state.

Let's use a naive example from a Laravel's Blade view template and underlying Eloquent `Order` model:

```php
@foreach($orders as $order)
    {{ $order->reference }} status: {{ $order->status }}

    @if($order->status === 'new')
        <button>start processing</button>

    @elseif($order->status === 'awaiting_payment')
        <button>record payment</button>

    @elseif($order->status === 'awaiting_shipment')
        <button>save tracking number</button>

    @elseif($order->status === 'in_delivery')
        <button>record delivery</button>
        <button>open claim</button>

    @elseif($order->status === 'complete')
        <button>open claim</button>

    @elseif($order->status === 'processing_claim')
        <button>refund</button>
        <button>close claim</button>
    @endif
@endforeach
```

This quickly gets out of hand, especially when a new status is introduced or the processing order changes.

---

**To streamline it, we can implement state machine for the Order entity:**

1. implement interface on the `Order` model
    ```php
    class Order extends Model implements \Sofa\StateMachine\StateMachineInterface
    {
        //...

        public function getCurrentState() : string
        {
            return $this->status;
        }

        public function setState(string $state) : void
        {
            $this->status = $state;
            $this->save();
        }
    }
    ```

2. define available transitions and prepare data for the template:
    ```php
    $transitions = [
        Transition::make(/*from_state*/ 'new', /*action*/ 'start processing', /*to_state*/ 'awaiting_payment'),
        Transition::make('awaiting_payment', 'record payment', 'awaiting_shipment'),
        Transition::make('awaiting_shipment', 'save tracking number', 'in_delivery'),
        Transition::make('in_delivery', 'record delivery', 'complete'),
        Transition::make('in_delivery', 'open claim', 'processing_claim'),
        Transition::make('complete', 'open claim', 'processing_claim'),
        Transition::make('processing_claim', 'close claim', 'complete'),
        Transition::make('processing_claim', 'refund', 'refunded'),
    ];

    foreach ($orders as $order) {
        $order_state = new \Sofa\StateMachine\Fsm($order, $transitions);

        $order->available_actions = $order_state->getAvailableActions();
    }
    ```

3. and we end up with controller & template code decoupled from the Process logic & order:
    ```php
    @foreach($orders as $order)
        {{ $order->reference }} status: {{ $order->status }}

        @foreach($order->available_actions as $action)
            <button>{{ $action }}</button>
        @endforeach
    @endforeach
    ```

4. finally let's process the actions
    ```php
    // controller handling the action
    public function handleAction($order_id, Request $request)
    {
        $order_state = new \Sofa\StateMachine\Fsm(Order::find($order_id), $transitions);

        $this->validate($request, [
            'action' => Rule::in($order_state->getAvailableActions()),
            // ...
        ]);
        $order_state->process($request->get('action'));

        return Redirect::to('some/place');
    }

    ```

With this setup we no **longer have to change our controllers or views, whenever business requirements change**. Instead we add a new transition to the state machine definition.

---

#### I need more control during transition - how to?

The above example assumes very simple transition process, ie. `$order->status = $new_status`. This can be enough sometimes, but often we will need more flexibility during transitions. To address this need you can customize your `Transition` definitions, so they turn from simple **POPO** into `callable` that will be invoked, when state machine processes appropriate **action**:

```php
class Refund extends \Sofa\StateMachine\Transition
{
    public function __invoke(StateMachineInterface $order, $payload)
    {
        // $payload is any object you pass to the process method:
        // $order_state->process('refund', $anything_you_need_here);
        $order->refunded_at = $payload['time'];
        $order->refunded_by = $payload['user_id'];

        $order->setState($this->to_state);
    }
}

// Then our transitions definition would like something like:
$transitions = [
    // ...
    Transition::make('processing_claim', 'close claim', 'complete'),
    Refund::make('processing_claim', 'refund', 'refunded'),
];
```

---

Happy Coding!


#### Contribution

All contributions are welcome. Make your PR PSR-2 compliant and tested.
