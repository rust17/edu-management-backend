<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('passport:install');
    }

    /**
     * Test Omise pay with paid invoice
     */
    public function test_omise_pay_with_paid_invoice()
    {
        $student = User::factory()->create(['role' => 'student']);
        $invoice = Invoice::factory()->create([
            'student_id' => $student->id,
            'status' => Invoice::STATUS_PAID
        ]);

        $response = $this->actingAs($student, 'api')->postJson('/api/payments/omise-card', [
            'invoice_id' => $invoice->id,
            'token' => 'tok_test_123'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The order has already been paid'
            ]);
    }
}
