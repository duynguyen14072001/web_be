<?php

namespace App\Http\Controllers;

use App\Enums\StatusUser;
use App\Models\PasswordResetTokens;
use App\Mail\SendEmailResetPasswordLink;
use App\Mail\SendMailVerifyAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public  $status_code = 200;
    public function register(Request $request)
    {
        if (User::where('email', $request->email)->where('status', StatusUser::INACTIVE)->first()) {
            User::where('email', $request->email)->where('status', StatusUser::INACTIVE)->first()->delete();
        }
        $validator = Validator::make(
            $request->all(),
            [
                "email"                 =>          "required|email|unique:users,email",
                "nickname"              =>          "required",
                "password"              =>          "required|min:6",
            ],
            [
                "email.required"                        =>        "Please enter e-mail address",
                "email.unique"                          =>        "Email already exists",
                "nickname.required"                     =>        "Please enter nickname",
                "email.email"                           =>        "Please enter valid e-mail address",
                "password.required"                     =>        "Please enter password",
            ]
        );
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'failed',
                    "success" => false,
                    'error' => [
                        "message" => $validator->errors()->first()
                    ],
                ],
                400
            );
        }
        if (User::where('nickname', $request->nickname)->whereNull('anonymous_id')->first()) {
            return response()->json(
                [
                    'status' => 'failed',
                    'errors' => 'There are already people using this nickname',
                    'success' => false
                ],
                400
            );
        }
        $userDataArray = array(
            "email"     => $request->email,
            "password"  => bcrypt($request->password),
            'time_end_penalty' => config('config.time_end_penalty_default'),
            'time_end_cool_down' => config('config.time_end_cool_down_default'),
            'nickname' => $request->nickname,
        );
        $user = User::create($userDataArray);
        $user->status = StatusUser::INACTIVE;
        $user->save();
        if ($request->user_id) {
            $inviter = User::where('id', $request->user_id)->first();
            $inviter->coins += 1;
            $inviter->save();
        }
        // $credentials = $request->only('email', 'password');
        // if (Auth::attempt($credentials)) {
        //     $user = Auth::user();
        //     $token = $user->createToken('authToken')->plainTextToken;
        //     $user['token'] = $token;
        $fe_url = config('config.fe_url');
        $url = $fe_url . 'verify-email?email=' . $user->email;
        Mail::send(new SendMailVerifyAccount($user->email, $user->nickname, $url));
        return response()->json(["status" => $this->status_code, "success" => true, "message" => "Register successfully", "data" => $user]);
        // }
        // return response()->json(["status" => "failed", "success" => false, "errors" => "Unable to login. Incorrect password or email"], 400);
    }


    public function login(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "email"             =>          "required|email",
                "password"          =>          "required"
            ],
            [
                "email.required"    =>          "Please enter e-mail address",
                "email.email"       =>          "Please enter valid e-mail address",
                "password.required" =>          "Please enter password"
            ]
        );
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'failed',
                    "success" => false,
                    'error' => [
                        "message" => $validator->errors()->first()
                    ],
                ],
                400
            );
        }
        if (User::where('email', $request->email)->where('status', StatusUser::INACTIVE)->first()) {
            return response()->json(
                [
                    'status' => 'failed',
                    "success" => false,
                    'error' => 'Please confirm email'
                ],
                400
            );
        }
        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('authToken')->plainTextToken;
            $user['token'] = $token;
            return response()->json(["status" => $this->status_code, "success" => true, "message" => "Login successfully", "data" => $user]);
        }
        return response()->json(["status" => "failed", "success" => false, "errors" => "Unable to login. Incorrect password or email"], 400);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'You have been successfully logged out!'
        ], 400);
    }

    public function sendResetPasswordLink(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "email"             =>          "required|email"
            ],
            [
                "email.required"    =>          "Please enter e-mail address",
                "email.email"       =>          "Please enter valid e-mail address"
            ]
        );
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'failed',
                    "success" => false,
                    'error' => [
                        "message" => $validator->errors()->first()
                    ],
                ],
                400
            );
        }
        $email = $request->email;
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(["status" => "failed", "success" => false, "errors" => "User not found"], 400);
        }
        return DB::transaction(function () use ($email) {
            $token = null;
            Password::sendResetLink(['email' => $email], function ($user, $t) use ($email, &$token) {
                $token = $t;
                $fe_url = config('config.fe_url');
                $url = $fe_url . "set-new-password?email=" . $email . "&token=" . $token;
                Mail::send(new SendEmailResetPasswordLink($email, $url, $user->nickname));
            });

            $existingPasswordReset = PasswordResetTokens::where('email', $email)
                ->exists();

            if ($existingPasswordReset) {
                PasswordResetTokens::where('email', $email)->update(['token' => $token]);
            } else {
                $data['email'] = $email;
                $data = [
                    'token'   => $token,
                    'email'   => $email,
                ];
                PasswordResetTokens::create($data);
            }
            return response()->json(["status" => "success", "success" => true, "message" => "Sent email reset password success"], 200);
        });
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email'    => 'required|email',
                'password' => 'required|min:6',
                'token' => 'required',
            ],
            [
                "email.required"    =>          "Please enter e-mail address",
                "email.email"       =>          "Please enter valid e-mail address"
            ]
        );
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'failed',
                    "success" => false,
                    'error' => [
                        "message" => $validator->errors()->first()
                    ],
                ],
                400
            );
        }
        $data   = $request->only('email', 'password', 'password_confirmation', 'token');
        $passwordReset = PasswordResetTokens::where([
            ['email', $data['email']],
            ['token', $data['token']],
        ])->first();

        if ($passwordReset) {
            $user = User::where('email', $data['email'])->first();
            $user->password = bcrypt($request->password);
            $user->save();

            return response()->json(["status" => "success", "success" => true, "message" => "Reset password success"], 200);
        }

        return response()->json(["status" => "failed", "success" => false, "errors" => "Reset password fail"], 400);
    }

    public function verifyToken(Request $request)
    {
        $data   = $request->only('email', 'token');
        $passwordReset = PasswordResetTokens::where([
            ['email', $data['email']],
            ['token', $data['token']],
        ])->first();
        if ($passwordReset) {
            return response()->json(["status" => "success", "success" => true, "message" => "Available token"], 200);
        }
        return response()->json(["status" => "failed", "success" => false, "errors" => "Token expired"], 400);
    }

    public function verifyEmail(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->status = StatusUser::ACTIVE;
            $user->save();
            return response()->json(["status" => "success", "success" => true, "message" => "Verification completed"], 200);
        }
        return response()->json(["status" => "failed", "success" => false, "errors" => "Verification not completed"], 400);
    }
}
