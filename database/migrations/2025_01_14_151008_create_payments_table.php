<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('student_id'); // users.id
            $table->enum('payment_platform', ['omise'])->nullable();
            $table->enum('payment_method', ['card'])->nullable();
            $table->string('transaction_no', 255)->unique()->nullable();
            $table->decimal('transaction_fee', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('invoice_id');
            $table->index('student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
