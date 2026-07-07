<?php

use App\Models\PatrolUnit;
use Illuminate\Support\Facades\Broadcast;

// Public channel — TOC dashboard listens for all new incidents
Broadcast::channel('incidents', fn () => true);

// Private channel — only the patrol unit it belongs to may listen on patrol.{id}.
// Authenticated via the Sanctum-guarded broadcasting auth route registered in
// routes/api.php, so $user here resolves from the mobile app's bearer token.
Broadcast::channel('patrol.{patrolUnitId}', function ($user, $patrolUnitId) {
    return $user instanceof PatrolUnit && (int) $user->id === (int) $patrolUnitId;
});

// TOC admin listens for new patrol registration requests
Broadcast::channel('patrol-registrations', fn () => true);

// Public channel — TOC location-tracking map listens for patrol position
// updates. Approximate patrol location is no more sensitive than what's
// already shown on this authenticated dashboard page, same trust model as
// the 'incidents' channel above.
Broadcast::channel('patrol-locations', fn () => true);
