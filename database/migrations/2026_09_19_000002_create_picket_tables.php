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
        if (!Schema::hasTable('picket_areas')) {
            Schema::create('picket_areas', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('jobs')->nullable();
                $table->time('start_time')->default('06:30:00');
                $table->time('end_time')->default('07:00:00');
                $table->string('duty_hours')->nullable()->default('06:30 - 07:00');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('picket_schedules')) {
            Schema::create('picket_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('picket_area_id')->constrained('picket_areas')->onDelete('cascade');
                $table->tinyInteger('day_of_week')->comment('1 = Monday, 2 = Tuesday, ..., 6 = Saturday');
                $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['picket_area_id', 'day_of_week', 'employee_id'], 'picket_sched_unique');
            });
        }

        if (!Schema::hasTable('picket_swaps')) {
            Schema::create('picket_swaps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('requester_id')->constrained('employees')->onDelete('cascade');
                $table->date('requested_date');
                $table->foreignId('target_employee_id')->constrained('employees')->onDelete('cascade');
                $table->date('target_date');
                $table->enum('status', ['pending', 'approved_by_target', 'approved', 'rejected'])->default('pending');
                $table->text('notes')->nullable();
                $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('picket_swaps');
        Schema::dropIfExists('picket_schedules');
        Schema::dropIfExists('picket_areas');
    }
};
