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
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('nama'); // Mengganti 'name' bawaan Laravel menjadi 'nama'
        $table->string('email')->unique();
        $table->string('password');
        $table->string('no_telepon');
        
        // Sesuaikan enum role ini dengan kebutuhan bisnis Anda, contoh:
        $table->enum('role', ['officer', 'tim', 'supervisor'])->default('officer');
        
        $table->rememberToken();
        $table->timestamps(); // Ini otomatis membuat 'created_at' dan 'updated_at'
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
