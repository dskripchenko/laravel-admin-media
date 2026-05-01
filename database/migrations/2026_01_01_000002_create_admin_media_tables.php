<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_media', function (Blueprint $table): void {
            $table->id();
            $table->string('disk', 32);
            $table->string('path');
            $table->string('mime', 128);
            $table->unsignedBigInteger('size'); // bytes
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->json('exif')->nullable();
            $table->float('focal_x')->default(0.5);
            $table->float('focal_y')->default(0.5);
            $table->string('alt')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('collection', 64)->default('default')->index();
            $table->json('tags')->nullable();
            $table->unsignedBigInteger('uploader_id')->nullable()->index();
            $table->timestamps();

            $table->index(['mime']);
        });

        Schema::create('admin_media_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_id')->constrained('admin_media')->cascadeOnDelete();
            $table->string('name', 64);
            $table->string('path');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('mime', 128);
            $table->unsignedBigInteger('size');
            $table->string('format', 16)->nullable();
            $table->timestamps();

            $table->unique(['media_id', 'name']);
        });

        Schema::create('admin_media_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_id')->constrained('admin_media')->cascadeOnDelete();
            $table->morphs('attachable');
            $table->string('role', 64)->default('default');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_media_attachments');
        Schema::dropIfExists('admin_media_variants');
        Schema::dropIfExists('admin_media');
    }
};
