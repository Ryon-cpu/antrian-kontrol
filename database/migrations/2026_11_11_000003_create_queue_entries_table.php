<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_entries', function (Blueprint $table) {
            $table->id();
            $table->date('queue_date');
            $table->unsignedTinyInteger('number');       // 1..99, ditampilkan 01..99
            $table->unsignedSmallInteger('position');    // urutan panggil (bisa digeser)
            $table->char('device_hash', 64)->index();    // sha256 token perangkat pendamping

            $table->string('patient_name', 100);
            $table->unsignedTinyInteger('patient_age');
            $table->string('patient_address', 255);
            $table->string('complaint', 500);            // keterangan sakit

            $table->string('status', 16)->default('waiting');
            $table->unsignedTinyInteger('call_count')->default(0);
            $table->timestamp('last_called_at')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->boolean('notified_near')->default(false);
            $table->timestamps();

            $table->unique(['queue_date', 'number']);
            $table->index(['queue_date', 'status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_entries');
    }
};
