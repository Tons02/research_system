<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ForgetPasswordRequest;
use App\Http\Resources\LoginResource;
use App\Models\User;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {

        $username = $request->username;
        $password = $request->password;

        $login = User::with([
            'role',
            'target_locations_users' => function ($query) {
                $query->wherePivot('is_done', 0);
            }
        ])->where('username', $username)->first();


        if (! $login || ! hash::check($password, $login->password)) {
            return $this->responseBadRequest('', 'Invalid Credentials');
        }

        $permissions = $login->role->access_permission ?? []; // Get permissions from the role or default to an empty array
        $token = $login->createToken($login->role->name, $permissions)->plainTextToken;

        $cookie = cookie('authcookie', $token);

        return response()->json([
            'message' => 'Successfully Logged In',
            'token' => $token,
            'data' => new LoginResource($login),
        ], 200)->withCookie($cookie);
    }

    public function Logout(Request $request)
    {
        $cookie = Cookie::forget('authcookie');
        auth('sanctum')->user()->currentAccessToken()->delete();
        return $this->responseSuccess('Logout successfully');
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::where('id', $id)->first();

        if (!$user) {
            return $this->responseUnprocessable('', 'Invalid ID provided for updating password. Please check the ID and try again.');
        }

        $user->update([
            'password' => $user->username,
        ]);

        return $this->responseSuccess('The Password has been reset');
    }

    public function changedPassword(ChangePasswordRequest $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->responseUnprocessable('', 'Please make sure you are logged in');
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return $this->responseSuccess('Password change successfully');
    }
}
