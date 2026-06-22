<?php

use Illuminate\Support\Facades\Broadcast;

// Public channel — TOC dashboard listens for all new incidents
Broadcast::channel('incidents', fn () => true);

// Public channel — each patrol unit listens on patrol.{id}
Broadcast::channel('patrol.{patrolUnitId}', fn () => true);

// TOC admin listens for new patrol registration requests
Broadcast::channel('patrol-registrations', fn () => true);
