<?php

use Illuminate\Support\Facades\Broadcast;

// Public channel — TOC dashboard listens for all new incidents
Broadcast::channel('incidents', fn () => true);

// Public channel — each patrol unit listens on patrol.{id}
// No auth needed here because the channel is public (Channel, not PrivateChannel).
// Switch to PrivateChannel + auth before production.
Broadcast::channel('patrol.{patrolUnitId}', fn () => true);
