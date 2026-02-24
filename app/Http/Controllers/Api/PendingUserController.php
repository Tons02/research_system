<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OneRdfChangePasswordRequest;
use App\Http\Requests\PendingUserRequest;
use App\Http\Resources\PendingUserResource;
use App\Models\PendingUser;
use App\Models\User;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PendingUserController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $status = $request->query('status');
        $pagination = $request->query('pagination');

        $PendingUser = PendingUser::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->orderBy('created_at', 'desc')
            ->useFilters()
            ->dynamicPaginate();

        if (!$pagination) {
            PendingUserResource::collection($PendingUser);
        } else {
            $PendingUser = PendingUserResource::collection($PendingUser);
        }
        return $this->responseSuccess('Pending User display successfully', $PendingUser);
    }

    public function store(PendingUserRequest $request)
    {
        $user = User::where('id_no', $request->id_no)
            ->where('id_prefix', $request->id_prefix)
            ->first();

        if ($user) {

            $user->update(
                [
                    'username' => $request->username,
                    'password' => $request->password
                ]
            );

            $user->save();
            return $this->responseSuccess('User Updated successfully');
        }

        $existingInPendingUser = PendingUser::where('id_no', $request->id_no)
            ->where('id_prefix', $request->id_prefix)
            ->first();

        if ($existingInPendingUser) {
            $existingInPendingUser->update([
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'suffix' => $request->suffix,
                'username' => $request->username,
                'password' => $request->username,
            ]);
            return $this->responseSuccess('Pending user updated successfully');
        }

        PendingUser::create([
            'id_prefix' => $request->id_prefix,
            'id_no' => $request->id_no,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'suffix' => $request->suffix,
            'username' => $request->username,
            'password' => $request->username,
        ]);

        return $this->responseSuccess('User added to pending list successfully');;
    }

    public function change_password(OneRdfChangePasswordRequest $request, $id_prefix_id_no)
    {
        // Split prefix and ID number
        list($id_prefix, $id_no) = explode('-', $id_prefix_id_no);

        $User = User::where('id_no', $id_no)
            ->where('id_prefix', $id_prefix)
            ->first();

        if (!$User) {
            return $this->responseBadRequest('User not found', '');
        }

        // Check old password
        if (!Hash::check($request->old_password, $User->password)) {
            return $this->responseBadRequest('Old password is incorrect', '');
        }

        $User->password = $request->password;
        $User->save();

        return $this->responseSuccess('Password updated successfully', $User);
    }

    public function reset_password(Request $request, $id_prefix_id_no)
    {
        // Split prefix and ID number
        list($id_prefix, $id_no) = explode('-', $id_prefix_id_no);

        $User = User::where('id_no', $id_no)
            ->where('id_prefix', $id_prefix)
            ->first();

        if (!$User) {
            return $this->responseBadRequest('User not found', '');
        }

        // Reset password to username
        $User->password = $User->username;
        $User->save();

        return $this->responseSuccess('Password reset successfully', $User);
    }
}
