<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('fields')->cascadeOnDelete();
            $table->foreignId('time_slot_id')->constrained('time_slots')->cascadeOnDelete();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->decimal('total_price', 10, 2);
            $table->decimal('commission', 10, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 300)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('field_id');
            $table->index('status');
        });

        // ⚠️ CONTRAINTE ANTI-DOUBLE RÉSERVATION
        // Colonne générée : NULL si status != 'confirmed', sinon "field_id_slot_id"
        // L'index UNIQUE sur cette colonne garantit qu'un créneau ne peut avoir
        // qu'une seule réservation confirmée à la fois.
        DB::statement("
            ALTER TABLE reservations
            ADD COLUMN confirmed_slot_key VARCHAR(100)
                GENERATED ALWAYS AS (
                    IF(status = 'confirmed', CONCAT(field_id, '_', time_slot_id), NULL)
                ) STORED,
            ADD UNIQUE INDEX uq_confirmed_reservation (confirmed_slot_key)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
