<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public  $status_code = 200;
    public function getListTeamByUserId($id)
    {
        $teams = Membership::join('teams', 'teams.id', '=', 'memberships.team_id')
            ->where('memberships.user_id', $id)
            ->select('teams.*')
            ->get();
        return response()->json(["status" => $this->status_code, "success" => true, "message" => "Register successfully", "data" => $teams]);
    }
}
