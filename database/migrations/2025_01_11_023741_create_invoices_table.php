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
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('student_id'); // users.id
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
            $table->softDeletes();

            // 外键约束
            $table->foreign('course_id')
                  ->references('id')
                  ->on('courses')
                  ->onDelete('restrict');

            $table->foreign('student_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');
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
