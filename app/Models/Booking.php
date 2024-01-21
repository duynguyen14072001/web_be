<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    public $table = "bookings";

    protected $fillable = [
        'user_id',
        'doctor_id',
        'appointment_date',
        'time_from',
        'time_to',
        'status',
    ];
}
