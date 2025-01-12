<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->date('year_month'); // 存储年月
            $table->decimal('fee', 10, 2); // 课程费用，10位数字，2位小数
            $table->unsignedBigInteger('teacher_id'); // users.id
            $table->timestamps();
            $table->softDeletes();

            // 外键约束
            $table->foreign('teacher_id')
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
        Schema::dropIfExists('courses');
    }
}
