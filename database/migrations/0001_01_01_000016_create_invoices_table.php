<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('status')->default('draft');
            $table->date('issue_date');
            $table->date('due_date');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('subtotal'); // stored in paise/cents
            $table->unsignedBigInteger('tax_total'); // stored in paise/cents
            $table->unsignedBigInteger('total'); // stored in paise/cents
            $table->string('currency')->default('INR');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
