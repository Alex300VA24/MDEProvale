<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('names', 150);
            $table->string('father_surname', 100);
            $table->string('mother_surname', 100);
            $table->string('username', 100)->unique();
            $table->string('email', 100)->unique();
            $table->char('dni', 8)->unique();
            $table->char('cui', 1);
            $table->foreignId('state_id')->constrained();
            $table->foreignId('rol_id')->constrained();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
