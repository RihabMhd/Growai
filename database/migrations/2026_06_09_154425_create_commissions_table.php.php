<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->unique()                 
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')   
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('amount', 10, 2);
            $table->string('type', 20);    
            $table->string('trigger_status', 50);

            $table->enum('state', ['credited', 'reversed'])->default('credited');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};