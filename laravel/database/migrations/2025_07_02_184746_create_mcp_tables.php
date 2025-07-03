<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('company_mcp_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('ai_model')->default('llama3');
            $table->integer('max_context_length')->default(4000);
            $table->json('allowed_tools')->nullable();
            $table->json('custom_instructions')->nullable();
            $table->json('security_rules')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies');
            $table->unique('company_id');
        });
        
        Schema::create('mcp_interactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('message');
            $table->json('response');
            $table->json('context')->nullable();
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('user_id')->references('id')->on('users');
            $table->index(['company_id', 'created_at']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcp_interactions');
        Schema::dropIfExists('company_mcp_configs');
    }
};