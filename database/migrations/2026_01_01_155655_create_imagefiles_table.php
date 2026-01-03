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
        Schema::create('imagefiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('file_request_index');
            
            $table->string('orig_name');
            $table->string('orig_path');
            $table->biginteger('orig_size');
            $table->string('orig_format');

            $table->string('compressed_name')->nullable();
            $table->string('compressed_path')->nullable();
            $table->biginteger('compressed_size')->nullable();
            $table->string('compressed_format')->nullable();

            $table->enum('current_status', ['waiting', 'processing', 'complete', 'failed'])->default('waiting');
            $table->string('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imagefiles');
    }
};
