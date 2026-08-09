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
        Schema::create('workflow_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('workflow_nodes')->restrictOnDelete();
            $table->string('type');
            $table->string('title');
            $table->unsignedInteger('position')->default(0);
            $table->string('ordering_mode')->nullable();
            $table->uuid('filesystem_key')->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['parent_id', 'position']);
            $table->index(['workflow_id', 'parent_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_nodes');
    }
};
