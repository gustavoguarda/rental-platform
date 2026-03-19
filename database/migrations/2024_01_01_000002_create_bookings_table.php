<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('guest_name');
            $table->string('guest_email');
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedSmallInteger('guests_count');
            $table->unsignedInteger('total_price_cents');
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('pending');
            $table->string('source_channel')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Composite index for availability lookups (most frequent query)
            $table->index(['property_id', 'status', 'check_in', 'check_out']);
            $table->index(['guest_email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
