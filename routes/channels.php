<?php

// routes/channels.php
use Illuminate\Support\Facades\Broadcast;

// Define a channel for user-specific events
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
