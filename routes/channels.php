<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', fn ($user, $id) => (int) $user->id === (int) $id);
Broadcast::channel('users.{id}', fn ($user, $id) => (int) $user->id === (int) $id);
