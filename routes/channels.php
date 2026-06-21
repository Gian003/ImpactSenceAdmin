<?php

use Illuminate\Support\Facades\Broadcast;

// Public channel — TOC dashboard listens for all new incidents
Broadcast::channel('incidents', fn () => true);

// Private channel — each patrol unit only receives its own dispatches
// Channel name: patrol.{patrol_unit_id}
Broadcast::channel('patrol.{patrolUnitId}', function ($user, int $patrolUnitId) {
    return (int) $user->id === $patrolUnitId;
});
