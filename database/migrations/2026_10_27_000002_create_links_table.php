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
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // En MySQL la collation por defecto no distingue mayúsculas: "aB3" y "Ab3"
            // chocarían en el índice único. utf8mb4_bin los trata como códigos distintos.
            $code = $table->string('code', 50)->unique();
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $code->collation('utf8mb4_bin');
            }

            $table->text('original_url');
            $table->char('url_hash', 64)->index();
            $table->string('title')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('clicks_count')->default(0);
            $table->timestamp('last_clicked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
