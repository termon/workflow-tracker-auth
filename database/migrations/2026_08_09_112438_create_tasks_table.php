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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_node_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->boolean('creates_document_section')->default(true);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->text('document_markdown')->nullable();
            $table->string('status')->default('not_started')->index();
            $table->text('blocked_reason')->nullable();
            $table->string('priority')->default('medium')->index();
            $table->date('due_date')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->uuid('filesystem_key')->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['workflow_node_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
