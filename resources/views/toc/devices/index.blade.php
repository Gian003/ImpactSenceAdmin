@extends('toc.layouts.app')

@section('title', 'Device Management')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .dm-stat {
            background: #fff;
            border: 1px solid #e8d5d9;
            border-radius: 10px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .dm-stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dm-stat-val {
            font-size: 1.55rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1;
        }

        .dm-stat-lbl {
            font-size: .75rem;
            color: #64748b;
            margin-top: 2px;
        }

        .dm-chart-card {
            background: #fff;
            border: 1px solid #e8d5d9;
            border-radius: 10px;
            overflow: hidden;
        }

        .dm-chart-header {
            padding: 14px 18px;
            border-bottom: 1px solid #f5eeef;
            font-weight: 600;
            font-size: .875rem;
            color: #1e293b;
        }

        .dm-chart-body {
            padding: 18px;
        }
    </style>
@endpush

@section('content')

    {{-- Stat summary --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="dm-stat">
                <div class="dm-stat-icon" style="background:#ede9fe;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" stroke="#4c1d95"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <circle cx="12" cy="7" r="4" />
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    </svg>
                </div>
                <div>
                    <div class="dm-stat-val">{{ $totalRiders }}</div>
                    <div class="dm-stat-lbl">Registered Riders</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dm-stat">
                <div class="dm-stat-icon" style="background:#fce7f3;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" stroke="#9d174d"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M12 2a9 9 0 0 1 9 9v1H3v-1a9 9 0 0 1 9-9z" />
                        <path d="M3 12v2a9 9 0 0 0 18 0v-2" />
                        <path d="M9 21h6" />
                    </svg>
                </div>
                <div>
                    <div class="dm-stat-val">{{ $totalDevices }}</div>
                    <div class="dm-stat-lbl">Total Devices</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dm-stat">
                <div class="dm-stat-icon" style="background:#d1fae5;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" stroke="#065f46"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                </div>
                <div>
                    <div class="dm-stat-val">{{ $activeDevices }}</div>
                    <div class="dm-stat-lbl">Active Devices</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="dm-stat">
                <div class="dm-stat-icon" style="background:#e0f2fe;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" stroke="#0c4a6e"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                    </svg>
                </div>
                <div>
                    <div class="dm-stat-val">{{ $pairedDevices }}</div>
                    <div class="dm-stat-lbl">Paired Devices</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts row --}}
    <div class="row g-3 mb-4">

        {{-- Riders & Devices trend --}}
        <div class="col-lg-7">
            {{-- h-100 (real Bootstrap utility — the old "h-80" here matched
                 nothing, Bootstrap's scale only goes 25/50/75/100, so this
                 card was never actually being stretched) + d-flex flex-column
                 makes the card fill the row's full stretched height (Bootstrap
                 rows are flex with align-items:stretch by default), and
                 flex-grow-1 on the body below lets it absorb that extra
                 height instead of leaving empty space below a fixed-size
                 header. --}}
            <div class="dm-chart-card h-100 d-flex flex-column">
                <div class="dm-chart-header">Registrations — Last 7 Days</div>
                <div class="dm-chart-body flex-grow-1">
                    <canvas id="regTrendChart" height="80"></canvas>
                </div>
            </div>
        </div>

        {{-- Device status doughnut --}}
        <div class="col-lg-5">
            <div class="dm-chart-card h-100 d-flex flex-column">
                <div class="dm-chart-header">Device Status</div>
                <div class="dm-chart-body flex-grow-1 d-flex align-items-center justify-content-center"
                    style="min-height:230px;">
                    <canvas id="deviceStatusChart" style="max-height:200px;"></canvas>
                </div>
            </div>
        </div>

    </div>

    {{-- Incidents trend --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="dm-chart-card">
                <div class="dm-chart-header">Incident Reports — Last 7 Days</div>
                <div class="dm-chart-body">
                    <canvas id="incidentTrendChart" height="50"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="alert alert-dismissible py-2 mb-3" role="alert"
            style="background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; font-size:.84rem; border-radius:8px;">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-dismissible py-2 mb-3" role="alert"
            style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; font-size:.84rem; border-radius:8px;">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- How-to guide — the register → provision → pair sequence isn't obvious:
     a physical unit and its database record only become "the same device"
     because someone typed the same device_code into both places. Using
     <details>/<summary> (native HTML, not a custom JS accordion) so it's
     keyboard-operable and announced as an expandable section to screen
     readers with no extra ARIA needed — see WCAG 3.3.2 (Labels or
     Instructions): a multi-step process like this is exactly the case
     that success criterion exists for. --}}
    <details class="dm-chart-card mb-4">
        <summary style="cursor:pointer; padding:14px 18px; font-weight:700; font-size:.88rem; color:#1e293b;">
            How to register and set up a new device
        </summary>
        <div style="padding:2px 18px 18px 18px; border-top:1px solid #f5eeef;">
            <ol style="margin:14px 0 0 0; padding-left:20px; font-size:1rem; color:#374151; line-height:1.7;">
                <li>
                    <strong>Register the device below</strong> — enter the <code>device_code</code>
                    printed on the physical unit (Model and Firmware are optional notes for your
                    own records). This only creates a record on this website; it doesn't touch
                    the physical device at all.
                </li>
                <li>
                    Once registered, the device appears in <strong>Unlinked Devices</strong> below
                    with an auto-generated <strong>Pairing Key</strong> — click <em>Copy</em> to
                    grab it.
                </li>
                <li>
                    <strong>Plug the physical device into a computer via USB</strong>, open a
                    serial terminal at 115200 baud, and reset it. Within the first few seconds
                    it accepts a command:
                    <div
                        style="font-family:monospace; background:#f1f5f9; color:#1e293b; padding:8px 12px; border-radius:6px; margin:6px 0; font-size:.85rem; display:inline-block;">
                        SET &lt;device_code&gt;
                    </div>
                    <br>
                    using the <em>exact same</em> device_code you registered in step 1 — that's
                    what makes the physical unit and the database record "the same device."
                    The pairing key from step 2 is <strong>not</strong> needed for this step.
                </li>
                <li>
                    Give the <strong>device code</strong> and <strong>pairing key</strong> to the
                    rider — they enter both in the mobile app's device pairing screen to link it
                    to their account.
                </li>
            </ol>
            <p style="font-size:.85rem; color:#64748b; margin:14px 0 0 0;">
                Registering and provisioning can technically happen in either order, but a device
                provisioned before it's registered will fail every API call ("Device not
                registered") until the matching registration exists — registering first avoids
                that.
            </p>
        </div>
    </details>

    {{-- Register Device form --}}
    <h6 class="fw-bold mb-2" style="color:#1e293b; font-size:.88rem; text-transform:uppercase; letter-spacing:.05em;">
        Register New Device
    </h6>
    <div class="dm-chart-card mb-4">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('toc.devices.store') }}">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" style="font-size:.78rem; color:#475569; font-weight:600;">Device
                            Code</label>
                        <input type="text" name="device_code" class="form-control form-control-sm"
                            placeholder="e.g. ITK-BLK4-GRP5-MDL1"
                            style="border-color:#e8d5d9; font-size:.83rem; border-radius:7px; font-family:monospace;"
                            value="{{ old('device_code') }}" required>
                        <div style="font-size:.7rem; color:#64748b; margin-top:3px;">Printed on the physical device</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:.78rem; color:#475569; font-weight:600;">Model</label>
                        <input type="text" name="model" class="form-control form-control-sm"
                            placeholder="e.g. ImpactSense Pro X1"
                            style="border-color:#e8d5d9; font-size:.83rem; border-radius:7px;"
                            value="{{ old('model') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"
                            style="font-size:.78rem; color:#475569; font-weight:600;">Firmware</label>
                        <input type="text" name="firmware_version" class="form-control form-control-sm"
                            placeholder="e.g. 2.0.1" style="border-color:#e8d5d9; font-size:.83rem; border-radius:7px;"
                            value="{{ old('firmware_version') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm text-white fw-semibold px-4 w-100"
                            style="background:#7B1A2E; border-color:#7B1A2E; font-size:.82rem; border-radius:7px;">
                            Register Device
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Unlinked Devices (available for pairing) --}}
    @if (($unlinkedDevices ?? collect())->isNotEmpty())
        <h6 class="fw-bold mb-2" style="color:#1e293b; font-size:.88rem; text-transform:uppercase; letter-spacing:.05em;">
            Unlinked Devices
            <span
                style="font-size:.72rem; font-weight:500; color:#6b7280; margin-left:6px; text-transform:none; letter-spacing:0;">Ready
                to pair via mobile app</span>
        </h6>
        <div class="dm-chart-card mb-4">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:.83rem;">
                    <thead>
                        <tr>
                            <th
                                style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                                Device Code</th>
                            <th
                                style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                                Pairing Key</th>
                            <th
                                style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                                Model</th>
                            <th
                                style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                                Firmware</th>
                            <th
                                style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                                Registered</th>
                            <th style="padding:11px 16px; background:#7B1A2E; border:none;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($unlinkedDevices as $dev)
                            <tr>
                                <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                                    <span
                                        style="font-family:monospace; background:#f1f5f9; padding:2px 8px; border-radius:5px; font-size:.82rem; color:#1e293b;">
                                        {{ $dev->device_code }}
                                    </span>
                                </td>
                                <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                                    <span class="pairing-key-cell"
                                        style="display:inline-flex; align-items:center; gap:8px;">
                                        <span class="pk-value"
                                            style="font-family:monospace; background:#fce7f3; color:#7B1A2E; padding:2px 10px; border-radius:5px; font-size:.82rem; font-weight:700; letter-spacing:.08em;">
                                            {{ $dev->pairing_key }}
                                        </span>
                                        <button type="button" onclick="copyPk(this, '{{ $dev->pairing_key }}')"
                                            style="background:none; border:none; cursor:pointer; color:#6b7280; font-size:.72rem; padding:0;"
                                            title="Copy">
                                            Copy
                                        </button>
                                    </span>

                                    {{-- The line that proves reports come from this
                                         hardware. Copied whole and pasted into the
                                         serial monitor while provisioning; never
                                         shown on the device itself, and never in any
                                         response the device receives. --}}
                                    @if ($dev->signing_secret)
                                        <div style="margin-top:6px;">
                                            <button type="button"
                                                onclick="copyPk(this, '{{ $dev->provisioningCommand() }}')"
                                                style="background:none; border:1px dashed #cbd5e1; border-radius:5px;
                                                       cursor:pointer; color:#475569; font-size:.7rem; padding:2px 8px;"
                                                title="Paste this into the serial monitor during provisioning">
                                                Copy signing command
                                            </button>
                                            @if ($dev->requiresSignature())
                                                <span style="font-size:.7rem; color:#15803d; margin-left:6px;"
                                                      title="This device has signed a report, so unsigned reports claiming to be it are refused.">
                                                    signed reports active
                                                </span>
                                            @else
                                                <span style="font-size:.7rem; color:#b45309; margin-left:6px;"
                                                      title="Until this device signs once, unsigned reports from it are still accepted.">
                                                    not provisioned to sign yet
                                                </span>
                                            @endif

                                            {{-- For a board that lost its flash, or a code moved to
                                                 a replacement board: it restarts its request counter
                                                 below what the server has already accepted, and every
                                                 report is refused as a replay until this is pressed. --}}
                                            <form method="POST" action="{{ route('toc.devices.reprovision', $dev) }}"
                                                  style="display:inline;"
                                                  onsubmit="return confirm('Give {{ $dev->device_code }} a new signing secret and clear its counter?\n\nIts current secret stops working immediately. You will need to send the new SECRET line to the board before it can report again.\n\nUse this after erasing a board, or when moving this code to a replacement board.');">
                                                @csrf
                                                <button type="submit"
                                                    style="background:none; border:none; cursor:pointer; color:#9a3412;
                                                           font-size:.7rem; text-decoration:underline; padding:0; margin-left:6px;"
                                                    title="Erased or replaced the board? This issues a new secret and resets the counter.">
                                                    Re-provision
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                                <td style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef;">
                                    {{ $dev->model ?? '—' }}</td>
                                <td
                                    style="padding:11px 16px; color:#475569; border-bottom:1px solid #f5eeef; font-family:monospace; font-size:.8rem;">
                                    {{ $dev->firmware_version ?? '—' }}</td>
                                <td
                                    style="padding:11px 16px; color:#64748b; border-bottom:1px solid #f5eeef; font-size:.8rem;">
                                    {{ $dev->created_at->format('M d, Y') }}</td>
                                <td style="padding:8px 16px; border-bottom:1px solid #f5eeef;">
                                    <button type="button"
                                        onclick="openEditModal({{ $dev->id }}, '{{ addslashes($dev->device_code) }}', '{{ addslashes($dev->model ?? '') }}', '{{ addslashes($dev->firmware_version ?? '') }}')"
                                        style="background:none; border:1px solid #e8d5d9; border-radius:6px; color:#7B1A2E; font-size:.75rem; font-weight:600; padding:3px 12px; cursor:pointer;">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Rider table --}}
    <h6 class="fw-bold mb-2" style="color:#1e293b; font-size:.88rem; text-transform:uppercase; letter-spacing:.05em;">
        Rider — Device Registry
    </h6>
    <div class="dm-chart-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.83rem;">
                <thead>
                    <tr>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Device Code</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Model</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Firmware</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Battery</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Full Name</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Age</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Contact Number</th>
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Status</th>
                        {{-- Cross-referenced against incidents.device_id — a
                             device racking up an unusual number (especially
                             false_alarm-flagged ones) is a hardware/
                             calibration signal worth flagging. --}}
                        <th
                            style="padding:11px 16px; color:#fff; font-weight:700; font-size:.78rem; background:#7B1A2E; border:none;">
                            Incidents</th>
                        <th style="padding:11px 16px; background:#7B1A2E; border:none;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($riders as $rider)
                        <tr>
                            <td style="padding:11px 16px; color:#334155; border-bottom:1px solid #f5eeef;">
                                @if ($rider->device)
                                    <span
                                        style="font-family:monospace; background:#f1f5f9; padding:2px 8px; border-radius:5px; font-size:.82rem;">
                                        {{ $rider->device->device_code }}
                                    </span>

                                    {{-- A board can fail after it is paired, so the same
                                         recovery has to be reachable from here too. --}}
                                    @if ($rider->device->signing_secret)
                                        <div style="margin-top:5px; display:flex; align-items:center; gap:8px;">
                                            <button type="button"
                                                onclick="copyPk(this, '{{ $rider->device->provisioningCommand() }}')"
                                                style="background:none; border:1px dashed #cbd5e1; border-radius:5px;
                                                       cursor:pointer; color:#475569; font-size:.68rem; padding:1px 7px;"
                                                title="Paste this into the serial monitor during provisioning">
                                                Copy signing command
                                            </button>
                                            <form method="POST" action="{{ route('toc.devices.reprovision', $rider->device) }}"
                                                  style="display:inline;"
                                                  onsubmit="return confirm('Give {{ $rider->device->device_code }} a new signing secret and clear its counter?\n\nIts current secret stops working immediately. You will need to send the new SECRET line to the board before it can report again.');">
                                                @csrf
                                                <button type="submit"
                                                    style="background:none; border:none; cursor:pointer; color:#9a3412;
                                                           font-size:.68rem; text-decoration:underline; padding:0;"
                                                    title="Erased or replaced the board? This issues a new secret and resets the counter.">
                                                    Re-provision
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                @else
                                    <span style="color:#64748b;">No device</span>
                                @endif
                            </td>
                            <td style="padding:11px 16px; color:#64748b; border-bottom:1px solid #f5eeef;">
                                {{ $rider->device?->model ?? '—' }}</td>
                            <td
                                style="padding:11px 16px; color:#64748b; border-bottom:1px solid #f5eeef; font-family:monospace; font-size:.8rem;">
                                {{ $rider->device?->firmware_version ?? '—' }}</td>
                            <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                                @if ($rider->device?->battery_level !== null)
                                    @php
                                        $battery = $rider->device->battery_level;
                                        $batteryColor = $battery < 20 ? '#991b1b' : ($battery < 50 ? '#92400e' : '#334155');
                                    @endphp
                                    <span style="color:{{ $batteryColor }}; font-weight:{{ $battery < 20 ? '700' : '400' }};">{{ $battery }}%</span>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td
                                style="padding:11px 16px; color:#1e293b; font-weight:500; border-bottom:1px solid #f5eeef;">
                                {{ $rider->full_name }}</td>
                            <td style="padding:11px 16px; color:#64748b; border-bottom:1px solid #f5eeef;">
                                {{ $rider->date_of_birth ? $rider->date_of_birth->age : 'N/A' }}</td>
                            <td style="padding:11px 16px; color:#64748b; border-bottom:1px solid #f5eeef;">
                                {{ $rider->phone_number ?? 'N/A' }}</td>
                            <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                                @if ($rider->device && $rider->device->is_active)
                                    <span
                                        style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#d1fae5; color:#065f46;">Active</span>
                                @elseif($rider->device)
                                    <span
                                        style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#fee2e2; color:#991b1b;">Inactive</span>
                                @else
                                    <span
                                        style="display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; background:#f1f5f9; color:#64748b;">Unpaired</span>
                                @endif
                            </td>
                            <td style="padding:11px 16px; border-bottom:1px solid #f5eeef;">
                                @if ($rider->device)
                                    <span style="color:#64748b; font-weight:{{ $rider->device->incidents_count > 0 ? '600' : '400' }};">{{ $rider->device->incidents_count }}</span>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td style="padding:8px 16px; border-bottom:1px solid #f5eeef;">
                                @if ($rider->device)
                                    <button type="button"
                                        onclick="openEditModal({{ $rider->device->id }}, '{{ addslashes($rider->device->device_code) }}', '{{ addslashes($rider->device->model ?? '') }}', '{{ addslashes($rider->device->firmware_version ?? '') }}')"
                                        style="background:none; border:1px solid #e8d5d9; border-radius:6px; color:#7B1A2E; font-size:.75rem; font-weight:600; padding:3px 12px; cursor:pointer;">
                                        Edit
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No registered riders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Edit Device Modal --}}
    <div id="editDeviceModal"
        style="display:none; position:fixed; inset:0; z-index:1050; background:rgba(0,0,0,.45); align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:12px; width:100%; max-width:420px; margin:16px; box-shadow:0 8px 32px rgba(0,0,0,.18);">
            <div style="padding:18px 22px; border-bottom:1px solid #f5eeef; display:flex; align-items:center; justify-content:space-between;">
                <span style="font-weight:700; font-size:.95rem; color:#1e293b;">Edit Device</span>
                <button onclick="closeEditModal()" style="background:none; border:none; font-size:1.2rem; color:#64748b; cursor:pointer; line-height:1;">&times;</button>
            </div>
            <form id="editDeviceForm" method="POST">
                @csrf
                @method('PATCH')
                <div style="padding:20px 22px; display:flex; flex-direction:column; gap:14px;">
                    <div>
                        <label style="font-size:.78rem; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Device Code</label>
                        <input type="text" name="device_code" id="edit_device_code"
                            style="width:100%; border:1px solid #e8d5d9; border-radius:7px; padding:7px 10px; font-family:monospace; font-size:.85rem; color:#1e293b;"
                            required>
                        <div style="font-size:.7rem; color:#64748b; margin-top:3px;">Must match exactly what is set in the firmware's <code>config.h</code></div>
                    </div>
                    <div>
                        <label style="font-size:.78rem; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Model <span style="font-weight:400;">(optional)</span></label>
                        <input type="text" name="model" id="edit_model"
                            style="width:100%; border:1px solid #e8d5d9; border-radius:7px; padding:7px 10px; font-size:.85rem;">
                    </div>
                    <div>
                        <label style="font-size:.78rem; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Firmware Version <span style="font-weight:400;">(optional)</span></label>
                        <input type="text" name="firmware_version" id="edit_firmware"
                            style="width:100%; border:1px solid #e8d5d9; border-radius:7px; padding:7px 10px; font-family:monospace; font-size:.85rem;"
                            placeholder="e.g. 2.0.1">
                    </div>
                </div>
                <div style="padding:14px 22px; border-top:1px solid #f5eeef; display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="closeEditModal()"
                        style="background:#f1f5f9; border:none; border-radius:7px; padding:8px 18px; font-size:.83rem; font-weight:600; color:#475569; cursor:pointer;">
                        Cancel
                    </button>
                    <button type="submit"
                        style="background:#7B1A2E; border:none; border-radius:7px; padding:8px 20px; font-size:.83rem; font-weight:600; color:#fff; cursor:pointer;">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ── Demonstration ─────────────────────────────────────────────────────

         A capstone defence cannot involve actually crashing a motorcycle, and
         a demo that only describes the dispatch chain proves nothing. This
         fires a crash through the same controller the physical helmet unit
         posts to, so what the panel watches is the production path.

         Deliberately last on the page, visually separated, and behind a
         confirmation that spells out what it does — it writes a real incident,
         and anything that writes a real incident should be hard to hit by
         accident. --}}
    <h6 class="fw-bold mb-2 mt-5"
        style="color:#1e293b; font-size:.88rem; text-transform:uppercase; letter-spacing:.05em;">
        Demonstration
    </h6>

    <div class="dm-chart-card mb-4" style="border:1.5px dashed #c2410c;">
        <div class="card-body p-4">

            <div class="d-flex align-items-start gap-2 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none"
                     stroke="#c2410c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     viewBox="0 0 24 24" style="flex-shrink:0; margin-top:2px;">
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <div>
                    <div style="font-size:.88rem; font-weight:700; color:#9a3412;">
                        Simulate a crash from a helmet device
                    </div>
                    <div style="font-size:.84rem; color:#7c2d12; line-height:1.5; margin-top:2px;">
                        Files a <strong>real incident</strong> through the same endpoint the
                        physical unit uses. It appears on the live board, sounds the alert and
                        can be dispatched — exactly like a genuine call. Its address is prefixed
                        <code>{{ \App\Models\Incident::SIMULATION_PREFIX }}</code> so nobody
                        mistakes it for one, and it can be cleared again below.
                    </div>
                </div>
            </div>

            @if($simulatableDevices->isEmpty())
                <div style="background:#fffbeb; border:1px solid #fcd34d; color:#78350f;
                            border-radius:8px; padding:12px 14px; font-size:.85rem;">
                    No device is paired to a rider yet. A device can only file an incident on
                    behalf of the rider it is paired to — pair one above first.
                </div>
            @else
            <form method="POST" action="{{ route('toc.devices.simulate') }}" id="simulateForm">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label style="font-size:.78rem; font-weight:600; color:#475569; display:block; margin-bottom:4px;">
                            Reporting device
                        </label>
                        <select name="device_id" required
                                style="width:100%; border:1px solid #e8d5d9; border-radius:7px; padding:8px 10px; font-size:.85rem;">
                            @foreach($simulatableDevices as $d)
                                <option value="{{ $d->id }}">
                                    {{ $d->device_code }} — {{ $d->rider->full_name ?? 'paired rider' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label style="font-size:.78rem; font-weight:600; color:#475569; display:block; margin-bottom:4px;">
                            Severity
                        </label>
                        <select name="severity" id="simSeverity" required
                                style="width:100%; border:1px solid #e8d5d9; border-radius:7px; padding:8px 10px; font-size:.85rem;">
                            <option value="critical">Critical — 4 alert cycles, pulsing ring</option>
                            <option value="high" selected>High — 3 alert cycles</option>
                            <option value="medium">Medium — 2 alert cycles</option>
                            <option value="low">Low — 1 alert cycle</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label style="font-size:.78rem; font-weight:600; color:#475569; display:block; margin-bottom:4px;">
                            Location
                        </label>
                        {{-- Presets are real Urdaneta roads, so the pin lands
                             somewhere the panel recognises rather than in a
                             field outside the city. --}}
                        <select id="simPreset"
                                style="width:100%; border:1px solid #e8d5d9; border-radius:7px; padding:8px 10px; font-size:.85rem;">
                            <option value="15.9770|120.5700|Urdaneta Bypass Road">Urdaneta Bypass Road</option>
                            <option value="15.9750|120.5650|Amadeo R. Perez Jr. Avenue, Poblacion">Amadeo R. Perez Jr. Ave, Poblacion</option>
                            <option value="15.9800|120.5610|McArthur Highway, Nancayasan">McArthur Highway, Nancayasan</option>
                            <option value="15.9870|120.5700|Brgy. San Vicente Road">Brgy. San Vicente Road</option>
                            <option value="custom">Custom coordinates…</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label style="font-size:.78rem; font-weight:600; color:#475569; display:block; margin-bottom:4px;">
                            Place name shown on the board
                        </label>
                        <input type="text" name="place" id="simPlace" required maxlength="120"
                               value="Urdaneta Bypass Road"
                               style="width:100%; border:1px solid #e8d5d9; border-radius:7px; padding:8px 10px; font-size:.85rem;">
                    </div>

                    <div class="col-md-3">
                        <label style="font-size:.78rem; font-weight:600; color:#475569; display:block; margin-bottom:4px;">
                            Latitude
                        </label>
                        <input type="number" step="any" name="latitude" id="simLat" required value="15.9770"
                               style="width:100%; border:1px solid #e8d5d9; border-radius:7px; padding:8px 10px; font-family:monospace; font-size:.85rem;">
                    </div>

                    <div class="col-md-3">
                        <label style="font-size:.78rem; font-weight:600; color:#475569; display:block; margin-bottom:4px;">
                            Longitude
                        </label>
                        <input type="number" step="any" name="longitude" id="simLng" required value="120.5700"
                               style="width:100%; border:1px solid #e8d5d9; border-radius:7px; padding:8px 10px; font-family:monospace; font-size:.85rem;">
                    </div>

                    <div class="col-12">
                        {{-- One checkbox per leg, all off by default.

                             Bundled into a single "notify" switch, rehearsing the
                             Twilio call also meant spending Semaphore credits and
                             texting a real family member — so in practice nobody
                             rehearsed it, and the one leg most worth showing a
                             panel went undemonstrated. --}}
                        <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:8px; padding:12px 14px;">
                            <div style="font-size:.8rem; font-weight:700; color:#9a3412; margin-bottom:8px;">
                                Which alerts to actually send
                            </div>

                            @php
                                $callsOff = ! config('services.outbound.calls', true);
                                $smsOff   = ! config('services.outbound.sms', true);
                            @endphp
                            @if ($callsOff || $smsOff)
                                {{-- Said here, loudly. Otherwise a rehearsal where nothing
                                     rings reads as a broken system, and someone "fixes" it
                                     by changing something that was never wrong. --}}
                                <div style="background:#ecfdf5; border:1px solid #6ee7b7; border-radius:6px;
                                            padding:8px 10px; margin-bottom:10px; font-size:.78rem; color:#065f46;">
                                    <strong>Rehearsal mode.</strong>
                                    @if ($callsOff && $smsOff)
                                        Calls and texts are switched off server-wide
                                    @elseif ($callsOff)
                                        Calls are switched off server-wide
                                    @else
                                        Texts are switched off server-wide
                                    @endif
                                    — nothing below leaves the building, however it is started,
                                    including a crash from a real device. What would have been
                                    sent is written to the log. Re-enable in <code>.env</code>
                                    (<code>OUTBOUND_CALLS_ENABLED</code> / <code>OUTBOUND_SMS_ENABLED</code>).
                                </div>
                            @endif

                            <div class="form-check mb-2">
                                <input class="form-check-input sim-channel" type="checkbox"
                                       name="call_toc" value="1" id="simCallToc">
                                <label class="form-check-label" for="simCallToc"
                                       style="font-size:.84rem; color:#475569;">
                                    <strong>Call the TOC hotline</strong>
                                    <code style="font-size:.78rem;">{{ config('services.twilio.toc_number') ?: 'not configured' }}</code>
                                    <span style="display:block; font-size:.78rem; color:#94a3b8;">
                                        Twilio speaks the crash details aloud. Roughly $0.29 and ~25 seconds per call.
                                        @unless(config('services.twilio.account_sid'))
                                            <strong style="color:#b91c1c;">Twilio is not configured — this will do nothing.</strong>
                                        @endunless
                                    </span>
                                </label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input sim-channel" type="checkbox"
                                       name="send_sms" value="1" id="simSendSms">
                                <label class="form-check-label" for="simSendSms"
                                       style="font-size:.84rem; color:#475569;">
                                    <strong>Text the rider's emergency contact</strong>
                                    <span style="display:block; font-size:.78rem; color:#94a3b8;">
                                        A real SMS to a real family member, and it spends Semaphore credits.
                                    </span>
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input sim-channel" type="checkbox"
                                       name="push_rider" value="1" id="simPushRider">
                                <label class="form-check-label" for="simPushRider"
                                       style="font-size:.84rem; color:#475569;">
                                    <strong>Push to the rider's phone</strong>
                                    <span style="display:block; font-size:.78rem; color:#94a3b8;">
                                        Free. Only arrives if that rider has the app installed and signed in.
                                    </span>
                                </label>
                            </div>

                            @php
                                // Counted here so the drill can say up front whether
                                // anyone will actually be alerted. Guarded: before the
                                // on_duty migration runs, the column does not exist,
                                // and this page must not break over a count.
                                try {
                                    $freeUnits = \App\Models\PatrolUnit::query()
                                        ->where('on_duty', true)
                                        ->where('status', '!=', 'dispatched')
                                        ->where('last_seen_at', '>=', now()->subMinutes(\App\Models\PatrolUnit::ONLINE_WITHIN_MINUTES))
                                        ->count();
                                } catch (\Throwable $e) {
                                    $freeUnits = null;
                                }
                            @endphp
                            <div class="form-check mt-2">
                                <input class="form-check-input sim-channel" type="checkbox"
                                       name="alert_patrol" value="1" id="simAlertPatrol">
                                <label class="form-check-label" for="simAlertPatrol"
                                       style="font-size:.84rem; color:#475569;">
                                    <strong>Alert the nearest on-duty patrol unit</strong>
                                    <span style="display:block; font-size:.78rem; color:#94a3b8;">
                                        Calls and pushes the closest free unit within
                                        {{ rtrim(rtrim(number_format((float) config('services.patrol_alert.radius_km', 5), 1), '0'), '.') }} km
                                        of the coordinates above. Each call is a real Twilio call.
                                        @if ($freeUnits === null)
                                            <strong style="color:#b91c1c;">Run <code>php artisan migrate</code> first — this will do nothing yet.</strong>
                                        @elseif ($freeUnits === 0)
                                            <strong style="color:#b91c1c;">No unit is on duty with the app open right now, so nobody will be alerted.</strong>
                                        @else
                                            <strong style="color:#15803d;">{{ $freeUnits }} {{ $freeUnits === 1 ? 'unit is' : 'units are' }} on duty and free right now.</strong>
                                        @endif
                                        @unless (config('services.patrol_alert.enabled'))
                                            Real crashes do not do this yet — it is switched off until PNP approves it.
                                        @endunless
                                    </span>
                                </label>
                            </div>

                            <div style="font-size:.78rem; color:#9a3412; margin-top:10px;">
                                All unticked = a board-only drill. The incident still appears and
                                the alert still sounds; nothing leaves the building.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
                    <button type="button" onclick="openSimulateConfirm()"
                            style="background:#c2410c; border:none; border-radius:7px; padding:9px 20px;
                                   font-size:.85rem; font-weight:700; color:#fff; cursor:pointer;">
                        Simulate crash
                    </button>

                    @if(($simulationCount ?? 0) > 0)
                        <span style="font-size:.82rem; color:#64748b;">
                            {{ $simulationCount }} simulated
                            {{ \Illuminate\Support\Str::plural('incident', $simulationCount) }}
                            currently on record.
                        </span>
                    @endif
                </div>
            </form>
            @endif

            @if(($simulationCount ?? 0) > 0)
                <form method="POST" action="{{ route('toc.devices.simulations.destroy') }}"
                      class="mt-3 pt-3" style="border-top:1px solid #f5eeef;"
                      onsubmit="return confirm('Remove all {{ $simulationCount }} simulated incidents? Real calls are untouched.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            style="background:#f1f5f9; border:1px solid #cbd5e1; border-radius:7px;
                                   padding:7px 16px; font-size:.82rem; font-weight:600; color:#475569; cursor:pointer;">
                        Remove {{ $simulationCount }} simulated
                        {{ \Illuminate\Support\Str::plural('incident', $simulationCount) }}
                    </button>
                    <span style="font-size:.8rem; color:#94a3b8; margin-left:8px;">
                        Clears the board and the statistics after a demonstration.
                    </span>
                </form>
            @endif
        </div>
    </div>

    {{-- Confirmation. Same modal idiom as the edit-device dialog above. --}}
    <div id="simulateConfirmModal"
         style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.55);
                z-index:1200; align-items:center; justify-content:center; padding:20px;">
        <div style="background:#fff; border-radius:12px; max-width:480px; width:100%; overflow:hidden;">
            <div style="background:#c2410c; padding:14px 22px;">
                <span style="color:#fff; font-weight:700; font-size:.95rem;">File a simulated crash?</span>
            </div>
            <div style="padding:20px 22px; font-size:.87rem; color:#374151; line-height:1.6;">
                <p style="margin:0 0 10px;">This will:</p>
                <ul style="margin:0 0 12px; padding-left:20px;">
                    <li>create a <strong>real incident record</strong> in the database;</li>
                    <li>put it on the live tracking board and <strong>sound the alert</strong>
                        on every open TOC screen;</li>
                    <li>keep sounding until somebody dispatches a unit or silences it.</li>
                </ul>
                <div id="simNotifyWarning"
                     style="margin:0 0 12px; padding:10px 12px; background:#fef2f2; border:1px solid #fecaca;
                            border-radius:7px; color:#991b1b; display:none;">
                    It will also reach real people:
                    <ul style="margin:6px 0 0; padding-left:20px;" id="simNotifyList"></ul>
                </div>
                <p style="margin:0; color:#64748b; font-size:.83rem;">
                    Its address will read
                    <code>{{ \App\Models\Incident::SIMULATION_PREFIX }}…</code>, and you can
                    remove it again from this page afterwards.
                </p>
            </div>
            <div style="padding:14px 22px; border-top:1px solid #f5eeef; display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" onclick="closeSimulateConfirm()"
                        style="background:#f1f5f9; border:none; border-radius:7px; padding:8px 18px;
                               font-size:.83rem; font-weight:600; color:#475569; cursor:pointer;">
                    Cancel
                </button>
                <button type="button" onclick="document.getElementById('simulateForm').submit();"
                        style="background:#c2410c; border:none; border-radius:7px; padding:8px 20px;
                               font-size:.83rem; font-weight:600; color:#fff; cursor:pointer;">
                    Yes, simulate it
                </button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    {{-- Server-side data the external script needs. copyPk() (used by inline
     onclick="" attributes elsewhere on this page) now lives in the external
     file below, along with everything else that was in this block. --}}
    <script>
        const editBaseUrl = '{{ route('toc.devices.index') }}';

        function openEditModal(id, code, model, firmware) {
            const form = document.getElementById('editDeviceForm');
            form.action = editBaseUrl.replace(/\/devices$/, '') + '/devices/' + id;
            document.getElementById('edit_device_code').value = code;
            document.getElementById('edit_model').value = model;
            document.getElementById('edit_firmware').value = firmware;
            const modal = document.getElementById('editDeviceModal');
            modal.style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editDeviceModal').style.display = 'none';
        }

        document.getElementById('editDeviceModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });

        // ── Demonstration panel ───────────────────────────────────────────
        (function () {
            const preset = document.getElementById('simPreset');
            const place  = document.getElementById('simPlace');
            const lat    = document.getElementById('simLat');
            const lng    = document.getElementById('simLng');
            if (!preset) return;   // no paired device, so the form isn't rendered

            preset.addEventListener('change', function () {
                if (this.value === 'custom') {
                    // Leave whatever is there and let the operator type — clearing
                    // the fields would make the required inputs invalid with no
                    // visible reason.
                    lat.focus();
                    lat.select();
                    return;
                }
                const [la, ln, name] = this.value.split('|');
                lat.value   = la;
                lng.value   = ln;
                place.value = name;
            });

            // Typing coordinates by hand means the preset no longer describes
            // what will be filed, so stop claiming it does.
            [lat, lng].forEach(function (el) {
                el.addEventListener('input', function () {
                    preset.value = 'custom';
                });
            });
        })();

        function openSimulateConfirm() {
            const form = document.getElementById('simulateForm');
            if (!form.reportValidity()) return;

            // Spell out, at the moment of confirming, exactly who this reaches
            // outside the room. A ticked box three fields up the page is easy
            // to forget you ticked.
            const legs = [
                ['simCallToc',   'ring the TOC hotline and speak the alert (about $0.29)'],
                ['simSendSms',   "send a real SMS to the rider's emergency contact"],
                ['simPushRider', "push a crash alert to the rider's phone"],
                ['simAlertPatrol', 'call and push the nearest on-duty patrol unit'],
            ];

            const list = document.getElementById('simNotifyList');
            const warn = document.getElementById('simNotifyWarning');
            list.innerHTML = '';

            let any = false;
            legs.forEach(function ([id, text]) {
                const box = document.getElementById(id);
                if (box && box.checked) {
                    any = true;
                    const li = document.createElement('li');
                    li.textContent = text;
                    list.appendChild(li);
                }
            });

            warn.style.display = any ? '' : 'none';
            document.getElementById('simulateConfirmModal').style.display = 'flex';
        }

        function closeSimulateConfirm() {
            document.getElementById('simulateConfirmModal').style.display = 'none';
        }

        document.getElementById('simulateConfirmModal').addEventListener('click', function (e) {
            if (e.target === this) closeSimulateConfirm();
        });
    </script>
    <script>
        window.TocDevicesConfig = {
            chartLabels: @json($chartLabels),
            trendRiders: @json($trendRiders),
            trendDevices: @json($trendDevices),
            trendIncidents: @json($trendIncidents),
            activeDevices: {{ $activeDevices }},
            pairedDevices: {{ $pairedDevices }},
            totalDevices: {{ $totalDevices }},
        };
    </script>
    <script src="{{ asset('js/toc/devices.js') }}?v={{ filemtime(public_path('js/toc/devices.js')) }}"></script>
@endpush
