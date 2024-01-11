<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public  $status_code = 200;
    public function listEvent($id)
    {
        $events = Event::where('team_id', $id);
        return response()->json(["status" => $this->status_code, "success" => true, "message" => "Register successfully", "data" => $events]);
    }
}
