<?php

use App\Concert;
use App\Billing\PaymentGateway;
use App\Billing\FakePaymentGateway;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PurchaseTicketsTest extends TestCase
{
	use DatabaseMigrations;

    /** @test */
    function customer_can_purchase_concert_tickets()
    {
    	$paymentGateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $paymentGateway);  // Tell the container to bind to the fake gateway

        // Arrange - create a concert
    	$concert = factory(Concert::class)->create(['ticket_price' => 3250]);

        // Act - Purchase concert tickets
    	$this->json('POST', "/concerts/{$concert->id}/orders", [
    		'email' => 'testJohn@example.com',
    		'ticket_quantity' => 3,
    		'payment_token' => $paymentGateway->getValidTestToken(),
    	]);

        // Assert
        $this->assertResponseStatus(201);   // http response for created

        //	- Make sure the customer was charged the correct amount
        $this->assertEquals(9750, $paymentGateway->totalCharges());

        //	- Make sure an order exists for this customer
        $order = $concert->orders()->where('email', 'testJohn@example.com')->first();
 		$this->assertNotNull($order);
        $this->assertEquals(3, $order->tickets()->count());
    }
}