<?php

use App\Order;
use App\Ticket;
use App\Concert;
use App\Reservation;
use App\Billing\Charge;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrderTest extends TestCase
{
	use DatabaseMigrations;

    /** @test */
    function creating_an_order_from_tickets_email_and_charge()
    {
        $tickets = factory(Ticket::class, 3)->create();
        $charge = new Charge([
            'amount' => 3600,
            'card_last_four' => '1234',
        ]);

        $order = Order::forTickets($tickets, 'john.unit@example.com', $charge);

        $this->assertEquals('john.unit@example.com', $order->email);
        $this->assertEquals(3, $order->ticketQuantity());
        $this->assertEquals(3600, $order->amount);
        $this->assertEquals('1234', $order->card_last_four);
    }

    /** @test */
    function retrieving_an_order_by_confirmation_number()
    {
        $order = factory(Order::class)->create([
            'confirmation_number' => 'ORDERCONFIRMATION1234',
        ]);

        $foundOrder = Order::findByConfirmationNumber('ORDERCONFIRMATION1234');

        $this->assertEquals($order->id, $foundOrder->id);
    }

    /** @test */
    function retrieving_a_non_existent_order_by_confirmation_number_throws_an_exception()
    {
        try {
            Order::findByConfirmationNumber('NONEXISTENTORDER');
        } catch (ModelNotFoundException $e) {
            return;
        }

        $this->fail("No matching order was found for confirmation number 'NONEXISTENTORDER', but an exception was not thrown.");


    }

    /** @test */
    function converting_to_an_array()
    {
        $order = factory(Order::class)->create([
            'confirmation_number' => 'ORDERCONFIRMATION1234',
            'email' => 'jane.unit@example.com',
            'amount' => 6000,
        ]);

        $order->tickets()->saveMany(factory(Ticket::class)->times(5)->create());

        $result = $order->toArray();

        $this->assertEquals([
            'confirmation_number' => 'ORDERCONFIRMATION1234',
            'email' => 'jane.unit@example.com',
            'ticket_quantity' => 5,
            'amount' => 6000,
        ], $result);
    }
}