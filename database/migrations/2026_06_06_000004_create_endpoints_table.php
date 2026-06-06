<?php

use App\Enums\AuthType;
use App\Enums\HttpMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('method')->default(HttpMethod::GET->value);
            $table->text('url');
            $table->longText('description')->nullable();
            $table->string('auth_type')->default(AuthType::None->value);
            $table->json('auth_config')->nullable();
            // Documentation detail stored as JSON for a simple form-builder binding.
            $table->json('parameters')->nullable();
            $table->json('headers')->nullable();
            $table->json('query_params')->nullable();
            $table->json('path_params')->nullable();
            $table->json('request_body')->nullable();
            $table->json('responses')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endpoints');
    }
};
