<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public  $status_code = 200;
    public function listMemberships($team_id)
    {
        $memberships = Membership::where('team_id', $team_id);

    }
}
