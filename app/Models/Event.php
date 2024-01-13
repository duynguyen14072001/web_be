<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;
    public $table = "events";

    protected $fillable = [
        'name',
        'team_id',
        'event_type_id',
        'status',
    ];
}
