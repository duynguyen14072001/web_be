<?php

use App\Enums\RoleUser;
use App\Enums\StatusUserEnum;
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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', [StatusUserEnum::ACTIVE, StatusUserEnum::INACTIVE])->after('password');
            $table->enum('role', [RoleUser::DOCTOR, RoleUser::ADMIN, RoleUser::PATIENT])->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
