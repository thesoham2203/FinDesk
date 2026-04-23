<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_line_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_rate_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->decimal('quantity', 8, 2); // e.g., 2.5 hours
            $table->unsignedBigInteger('unit_price'); // stored in paise/cents
            $table->unsignedBigInteger('line_total'); // stored in paise/cents
            $table->unsignedBigInteger('tax_amount'); // stored in paise/cents
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_line_items');
    }
};
