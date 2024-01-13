<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamPosition extends Model
{
    use HasFactory;
    public $table = "team_positions";

    protected $fillable = [
        'name',
        'team_id',
    ];
}
