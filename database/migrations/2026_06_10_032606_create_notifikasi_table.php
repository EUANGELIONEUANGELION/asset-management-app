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
    Schema::create('notifikasi', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id');
        $table->foreignId('aset_id')->nullable();
        $table->text('pesan');
        $table->string('channel')->default('whatsapp');
        $table->boolean('is_read')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
