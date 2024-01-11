<?php

use App\Enums\StatusTeamEnum;
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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('team_name');
            $table->unsignedBigInteger('team_type_id');
            $table->foreign('team_type_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->unsignedBigInteger('owner_user_id');
            $table->foreign('owner_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->enum('is_active', array(StatusTeamEnum::YES, StatusTeamEnum::NO))->default(StatusTeamEnum::NO);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
