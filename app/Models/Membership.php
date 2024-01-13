<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    use HasFactory;

    public $table = 'memberships';

    protected $fillable = [
        'user_id',
        'team_id',
        'team_position_id',
        'is_admin',
        'is_leader'
    ];
}
