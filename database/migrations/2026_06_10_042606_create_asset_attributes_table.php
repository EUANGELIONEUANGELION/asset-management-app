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
    Schema::create('asset_attributes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('aset_id')->constrained('aset')->onDelete('cascade');
        $table->string('attribute_name');  // Contoh: 'merk', 'tipe', 'ram'
        $table->string('attribute_value'); // Contoh: 'Wismilak', 'ThinkPad T480', '16GB'
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_attributes');
    }
};
