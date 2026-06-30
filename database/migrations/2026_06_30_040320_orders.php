<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number')->unique();
            $table->string('platform')->nullable();
            // amazon, meesho, flipkart, woocommerce, whatsapp, manual

            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('shipping_address')->nullable();

            $table->dateTime('pickup_deadline')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');

            $table->enum('status', [
                'pending',
                'packing',
                'packed',
                'ready_to_ship',
                'shipped',
                'issue',
                'cancelled',
            ])->default('pending');

            $table->string('shipping_label')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('packer_id')->nullable()->constrained('users')->nullOnDelete();

            $table->dateTime('packing_started_at')->nullable();
            $table->dateTime('packed_at')->nullable();
            $table->dateTime('ready_to_ship_at')->nullable();
            $table->dateTime('shipped_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
