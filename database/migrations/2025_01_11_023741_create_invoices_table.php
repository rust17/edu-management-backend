<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('no')->unique();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('student_id'); // users.id
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->decimal('amount', 10, 2);
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('course_id');
            $table->index('student_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}
