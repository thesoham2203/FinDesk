<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('employee');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();

            $table->foreign('manager_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['manager_id']);
            $table->dropColumn(['role', 'department_id', 'manager_id']);
        });
    }
};
