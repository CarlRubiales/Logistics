<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uploaded_files')) {
            Schema::create('uploaded_files', function (Blueprint $table) {
                $table->id();
                $table->string('path')->unique();
                $table->string('mime_type', 100)->default('image/jpeg');
                $table->longText('contents');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uploaded_files');
    }
};
