<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
},['guards' => ['sanctum']]);

Broadcast::channel('syncing-companies.{access_permission}', function ($user, $access_permission) {
    // Replace this logic with your actual permissions data source.
    $permissions = $user->permissions ?? []; // This should be an array of the user's access permissions.

    // Check if the provided access_permission exists in the user's permissions.
    return in_array($access_permission, $permissions);
});

