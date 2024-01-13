<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;
    public $table = "teams";

    protected $fillable = [
        'team_name',
        'team_type_id',
        'owner_user_id',
        'is_active',
    ];
}
