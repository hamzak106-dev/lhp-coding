<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_images', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('event_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedTinyInteger('position')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            // Fetch an event's images in display order; serve the primary fast.
            $table->index(['event_id', 'position'], 'event_images_event_position_index');
            $table->index(['event_id', 'is_primary'], 'event_images_event_primary_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_images');
    }
};
