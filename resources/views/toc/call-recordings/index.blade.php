@extends('toc.layouts.app')

@section('title', 'Call Recordings')

@section('content')

<p class="text-muted mb-4" style="font-size:.95rem; max-width:720px; color:#475569 !important;">
    Recordings of the automated voice alerts Twilio places to the TOC hotline when
    a crash is detected. Twilio records these on its own servers &mdash; the exact
    audio sent down the line &mdash; so what you hear here is what the duty officer
    heard, without any speaker or background noise.
</p>

@if(! $isConfigured)
    <div class="card border-0 rounded-3 p-4" style="border:1px solid #e8d5d9 !important;">
        <div style="font-size:.95rem; color:#475569;">
            Twilio isn't configured on this server, so there's nothing to list.
            Set <code>TWILIO_ACCOUNT_SID</code> and <code>TWILIO_AUTH_TOKEN</code>
            in the environment to enable alert calls and their recordings.
        </div>
    </div>
@else

<h6 class="fw-bold mb-2" style="color:#1e293b;">
    Alert Calls
    @if(count($recordings))
    <span class="badge rounded-pill ms-1" style="background:#7B1A2E; font-size:.78rem;">{{ count($recordings) }}</span>
    @endif
</h6>

<div class="card border-0 rounded-3 overflow-hidden" style="border:1px solid #e8d5d9 !important;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.95rem;">
            <thead>
                <tr>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.88rem; background:#7B1A2E; border:none;">Date &amp; Time</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.88rem; background:#7B1A2E; border:none;">Incident</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.88rem; background:#7B1A2E; border:none;">Duration</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.88rem; background:#7B1A2E; border:none;">Status</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.88rem; background:#7B1A2E; border:none;">Playback</th>
                    <th style="padding:11px 16px; color:#fff; font-weight:700; font-size:.88rem; background:#7B1A2E; border:none;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($recordings as $recording)
                <tr>
                    <td style="padding:11px 16px; color:#1e293b; border-bottom:1px solid #f5eeef; white-space:nowrap;">
                        {{-- Twilio's SDK hands back a plain DateTime (always
                             UTC); Carbon is used here rather than
                             DateTime::setTimezone because the latter needs a
                             DateTimeZone object, not the string that
                             config('app.timezone') returns. --}}
                        {{ $recording->dateCreated
                            ? \Carbon\Carbon::instance($recording->dateCreated)->timezone(config('app.timezone'))->format('M d, Y h:i A')
                            : '—' }}
                    </td>
                    {{-- Resolved by matching Twilio's call_sid against the SID
                         stored on the incident when the alert call was placed.
                         Recordings from before that linking existed (or from a
                         call whose SID never got saved) simply show as
                         unlinked rather than guessing by timestamp. --}}
                    @php $incident = $incidents[$recording->callSid] ?? null; @endphp
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">
                        @if($incident)
                            <div style="color:#1e293b; font-weight:600;">
                                {{ $incident->rider?->full_name ?? 'Unknown rider' }}
                            </div>
                            <div style="font-size:.85rem; color:#64748b;">
                                {{ ucfirst($incident->type) }} &middot; {{ ucfirst($incident->severity) }}
                                @if($incident->address) &middot; {{ $incident->address }} @endif
                            </div>
                        @else
                            <span style="color:#94a3b8;">Not linked</span>
                        @endif
                    </td>
                    <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">
                        {{ $recording->duration !== null ? $recording->duration . 's' : '—' }}
                    </td>
                    <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                        {{-- Twilio takes a moment to finish processing after a
                             call ends, so a just-placed call shows as
                             "processing" before its audio is fetchable. --}}
                        @if($recording->status === 'completed')
                            <span style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.8rem; font-weight:600; background:#d1fae5; color:#065f46;">Completed</span>
                        @else
                            <span style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.8rem; font-weight:600; background:#fef3c7; color:#92400e;">{{ ucfirst($recording->status ?? 'unknown') }}</span>
                        @endif
                    </td>
                    <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                        @if($recording->status === 'completed')
                            <audio controls preload="none" style="height:34px; max-width:260px;">
                                <source src="{{ route('toc.call-recordings.audio', $recording->sid) }}" type="audio/mpeg">
                                Your browser can't play audio inline &mdash; use Download instead.
                            </audio>
                        @else
                            <span style="color:#94a3b8; font-size:.9rem;">Not ready yet</span>
                        @endif
                    </td>
                    <td style="padding:11px 16px; border-bottom:1px solid #f5eeef; white-space:nowrap;">
                        @if($recording->status === 'completed')
                            <a href="{{ route('toc.call-recordings.audio', $recording->sid) }}?download=1"
                               style="color:#7B1A2E; font-weight:600; font-size:.9rem; text-decoration:none;">
                                Download
                            </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-5" style="font-size:.95rem;">
                        No call recordings yet.
                        <div class="mt-2" style="font-size:.88rem; color:#94a3b8; max-width:520px; margin:0 auto;">
                            Recording was only just enabled, so alert calls placed before
                            that have no audio stored. The next crash alert to the TOC
                            hotline will appear here once Twilio finishes processing it.
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endif

@endsection
