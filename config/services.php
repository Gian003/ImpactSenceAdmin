<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'fcm' => [
        'project_id'           => env('FIREBASE_PROJECT_ID'),
        'service_account_json' => env('FIREBASE_SERVICE_ACCOUNT_JSON'),
    ],

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'semaphore' => [
        'api_key'     => env('SEMAPHORE_API_KEY'),
        'sender_name' => env('SEMAPHORE_SENDER_NAME'), // must be pre-registered with Semaphore, or omit to use their default
    ],

    'twilio' => [
        'account_sid'  => env('TWILIO_ACCOUNT_SID'),
        'auth_token'   => env('TWILIO_AUTH_TOKEN'),
        'from_number'  => env('TWILIO_FROM_NUMBER'),
        'toc_number'   => env('TOC_HOTLINE_NUMBER'),
        // Amazon Polly voice used for the spoken crash alert. Neural voices
        // sound markedly less robotic than the standard set; override via
        // env if it doesn't land well on a real handset.
        'voice'        => env('TWILIO_VOICE', 'Polly.Matthew-Neural'),
    ],

    // Rehearsal switches. Off means the channel is not used by anything —
    // a device crash, a rider's report, a drill, a patrol alert — so a test
    // cannot ring a real phone or spend credits however it was started.
    //
    // Guarded inside VoiceCallService and SmsService rather than at the call
    // sites, because there are several call sites and one of them will always
    // be the one nobody remembered. Everything else still runs: the incident
    // is filed, the dashboard lights up, the timeline records what would have
    // been sent.
    'outbound' => [
        'calls' => (bool) env('OUTBOUND_CALLS_ENABLED', true),
        'sms'   => (bool) env('OUTBOUND_SMS_ENABLED', true),
    ],

    // Alerting the nearest free patrol unit alongside the TOC hotline call.
    // See App\Jobs\AlertNearestPatrol.
    //
    // Off by default: it lets a crash reach an officer before the TOC has
    // dispatched anyone, which is a change to how PNP Urdaneta runs calls and
    // theirs to approve. The TOC still sees every alert and can reassign.
    'patrol_alert' => [
        'enabled'        => (bool) env('PATROL_ALERT_ENABLED', false),
        // Farther than this and a unit is no nearer than the TOC's own choice.
        'radius_km'      => (float) env('PATROL_ALERT_RADIUS_KM', 5),
        // How long an alerted unit has to accept before the next one is tried.
        'accept_seconds' => (int) env('PATROL_ALERT_ACCEPT_SECONDS', 90),
        // Units tried in turn before the call is left to the TOC alone.
        'max_units'      => (int) env('PATROL_ALERT_MAX_UNITS', 3),
        // Ring the officer's phone as well as pushing to the app. The push
        // carries the map pin; the call is what gets noticed on a motorcycle.
        'call'           => (bool) env('PATROL_ALERT_CALL', true),
    ],

];
