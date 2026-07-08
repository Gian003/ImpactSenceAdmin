@extends('investigation.layouts.app')

@section('title', 'Incident Record Form')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/investigation/incident-record.css') }}">
@endpush

@section('content')

@php
    // Reached directly (blank form, fast to fill by hand) or via
    // investigation.incident-records.show/{incident} (real data prefilled
    // below); the investigator still reviews/completes every field before
    // printing either way.
    //
    // $incident isn't passed at all on the blank-form route, and `?->`
    // only guards against a null value — it still throws "Undefined
    // variable" if the variable was never set in the first place. `??`
    // is the one that safely defaults an unset variable.
    $incident = $incident ?? null;
    $incidentDateTime = $incident?->created_at?->format('Y-m-d\TH:i');
@endphp

{{-- Hidden on the printed form itself (see @media print in
     incident-record.css) — just page chrome for finding past records. --}}
<div class="text-end mb-2">
    <a href="{{ route('investigation.incident-records.all') }}" style="font-size:.82rem; color:#1b3d52; font-weight:600;">
        View All Saved Records →
    </a>
</div>

<form id="irfForm" method="POST" action="{{ route('investigation.incident-records.store') }}">
@csrf
<input type="hidden" name="record_id" value="{{ $recordId ?? '' }}">

<div class="irf-printable">

{{-- ══════════════════════════════════════════════════════
     PAGE 1 — IRF Main Form
══════════════════════════════════════════════════════ --}}
<div id="page1">
<div class="irf-page">

    {{-- Document Header --}}
    <div class="irf-doc-header mb-2">
        <img src="{{ asset('images/pnp_logo.png') }}" alt="PNP" onerror="this.style.display='none'">
        <div>
            <h2>Philippine National Police</h2>
            <h1>INCIDENT RECORD FORM</h1>
        </div>
        <img src="{{ asset('images/investigation_logo.png') }}" alt="PNP Investigation" onerror="this.style.display='none'">
    </div>

    {{-- Link to Incident — manual picker so a record can be linked to a real
         incident no matter how this form was opened (a blank direct visit
         has no incident context at all otherwise). Not required: unlinked
         records for walk-in reports still save, just show only on the
         "All Incident Records" list rather than any specific incident's
         report page. --}}
    <div class="irf-link-panel">
        <span class="irf-label">🔗 Link to Incident (optional)</span>
        <select name="incident_id" style="width:100%;">
            <option value="">— Not linked to any incident —</option>
            @foreach($incidents ?? [] as $inc)
            <option value="{{ $inc->id }}" @selected($incident && (int) $incident->id === $inc->id)>
                #{{ $inc->id }} — {{ ucfirst($inc->type) }} — {{ $inc->rider?->full_name ?? 'Unknown rider' }} — {{ $inc->created_at->format('M d, Y h:i A') }}
            </option>
            @endforeach
        </select>
    </div>

    <table class="irf-table">

        {{-- IRF Entry / Type / Copy For --}}
        <tr>
            <td style="width:35%;">
                <span class="irf-label">IRF Entry Number:</span>
                <input class="irf-input" type="text" name="irf_entry_number">
            </td>
            <td style="width:45%;">
                <span class="irf-label">Type of Incident:</span>
                <input class="irf-input" type="text" name="type_of_incident"
                       value="{{ $incident ? ucfirst($incident->type).' incident' : '' }}"
                       placeholder="(Operation) Manhunt Charlie/Arrest with Warrant">
            </td>
            <td style="width:20%;">
                <span class="irf-label">Copy For:</span>
                <input class="irf-input" type="text" name="copy_for">
            </td>
        </tr>

        {{-- Instructions --}}
        <tr>
            <td colspan="3" class="irf-instruction">
                INSTRUCTIONS: Refer to PNP SOP on Recording of Incidents in the Police Blotter in filling up this form. This Incident Record Form (IRF)
                may reproduced, photocopied, and/or downloaded from the DIDM website, www.didm.pnp.gov.ph.
            </td>
        </tr>

        {{-- Date Reported / Date of Incident / Place --}}
        <tr>
            <td>
                <span class="irf-label">Date and Time Reported:</span>
                <input class="irf-input" type="datetime-local" name="date_reported" value="{{ $incidentDateTime }}">
            </td>
            <td>
                <span class="irf-label">Date and Time of Incident:</span>
                <input class="irf-input" type="datetime-local" name="date_incident" value="{{ $incidentDateTime }}">
            </td>
            <td>
                <span class="irf-label">Place of Incident: Barangay, Town/City, Province:</span>
                <input class="irf-input" type="text" name="place_incident" value="{{ $incident?->address ?? '' }}">
            </td>
        </tr>

        {{-- ── ITEM A: REPORTING PERSON ── --}}
        <tr><td colspan="3" class="irf-section">ITEM "A" — REPORTING PERSON</td></tr>
        <tr>
            <td><span class="irf-label">Family Name:</span><input class="irf-input" type="text" name="a_family_name"></td>
            <td><span class="irf-label">First Name:</span><input class="irf-input" type="text" name="a_first_name"></td>
            <td>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:4px;">
                    <div><span class="irf-label">Middle Name:</span><input class="irf-input" type="text" name="a_middle_name"></div>
                    <div><span class="irf-label">Qualifier:</span><input class="irf-input" type="text" name="a_qualifier"></div>
                    <div><span class="irf-label">Nickname:</span><input class="irf-input" type="text" name="a_nickname"></div>
                </div>
            </td>
        </tr>
        <tr>
            <td><span class="irf-label">Citizenship:</span><input class="irf-input" type="text" name="a_citizenship"></td>
            <td><span class="irf-label">Gender:</span><input class="irf-input" type="text" name="a_gender"></td>
            <td><span class="irf-label">Address:</span><input class="irf-input" type="text" name="a_address"></td>
        </tr>

        {{-- ── ITEM B: SUSPECT'S DATA ── --}}
        <tr><td colspan="3" class="irf-section">ITEM "B" — SUSPECT'S DATA</td></tr>
        <tr>
            <td><span class="irf-label">Family Name:</span><input class="irf-input" type="text" name="b_family_name"></td>
            <td><span class="irf-label">First Name:</span><input class="irf-input" type="text" name="b_first_name"></td>
            <td>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:4px;">
                    <div><span class="irf-label">Middle Name:</span><input class="irf-input" type="text" name="b_middle_name"></div>
                    <div><span class="irf-label">Qualifier:</span><input class="irf-input" type="text" name="b_qualifier"></div>
                    <div><span class="irf-label">Nickname:</span><input class="irf-input" type="text" name="b_nickname"></div>
                </div>
            </td>
        </tr>
        <tr>
            <td style="width:16%"><span class="irf-label">Citizenship:</span><input class="irf-input" type="text" name="b_citizenship"></td>
            <td colspan="2">
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr 1fr; gap:4px;">
                    <div><span class="irf-label">Gender:</span><input class="irf-input" type="text" name="b_gender"></div>
                    <div><span class="irf-label">Civil Status:</span><input class="irf-input" type="text" name="b_civil_status"></div>
                    <div><span class="irf-label">Date of Birth:</span><input class="irf-input" type="date" name="b_dob"></div>
                    <div><span class="irf-label">Age:</span><input class="irf-input" type="number" name="b_age"></div>
                    <div><span class="irf-label">Place of Birth:</span><input class="irf-input" type="text" name="b_pob"></div>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2"><span class="irf-label">Address (House Number/Street) Village Sitio:</span><input class="irf-input" type="text" name="b_address"></td>
            <td>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:4px;">
                    <div><span class="irf-label">Barangay:</span><input class="irf-input" type="text" name="b_barangay"></div>
                    <div><span class="irf-label">Town/City:</span><input class="irf-input" type="text" name="b_town"></div>
                    <div><span class="irf-label">Province:</span><input class="irf-input" type="text" name="b_province"></div>
                </div>
            </td>
        </tr>
        <tr>
            <td><span class="irf-label">Highest Educational Attainment:</span><input class="irf-input" type="text" name="b_education"></td>
            <td><span class="irf-label">Occupation:</span><input class="irf-input" type="text" name="b_occupation"></td>
            <td><span class="irf-label">Relation to Victim:</span><input class="irf-input" type="text" name="b_relation_victim"></td>
        </tr>
        <tr>
            <td><span class="irf-label">Rank (if AFP/PNP Personnel):</span><input class="irf-input" type="text" name="b_rank"></td>
            <td colspan="2">
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:4px;">
                    <div><span class="irf-label">Unit Assignment:</span><input class="irf-input" type="text" name="b_unit"></div>
                    <div><span class="irf-label">Group Affiliation:</span><input class="irf-input" type="text" name="b_group"></div>
                    <div><span class="irf-label">With Previous Criminal Record:</span><input class="irf-input" type="text" name="b_criminal_record"></div>
                    <div><span class="irf-label">Barangay:</span><input class="irf-input" type="text" name="b_cr_barangay"></div>
                </div>
            </td>
        </tr>
        <tr>
            <td><span class="irf-label">Barangay:</span><input class="irf-input" type="text" name="b_barangay2"></td>
            <td colspan="2">
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr 1fr; gap:4px;">
                    <div><span class="irf-label">Weight:</span><input class="irf-input" type="text" name="b_weight"></div>
                    <div><span class="irf-label">Color of Eyes:</span><input class="irf-input" type="text" name="b_eye_color"></div>
                    <div><span class="irf-label">Color of Hair:</span><input class="irf-input" type="text" name="b_hair_color"></div>
                    <div><span class="irf-label">Distinguishing Marks:</span><input class="irf-input" type="text" name="b_marks"></div>
                    <div><span class="irf-label">Under the Influence:</span><input class="irf-input" type="text" name="b_influence"></div>
                </div>
            </td>
        </tr>

        {{-- Children and Conflict with the Law --}}
        <tr><td colspan="3" class="irf-subsection">FOR CHILDREN AND CONFLICT WITH THE LAW</td></tr>
        <tr>
            <td colspan="2"><span class="irf-label">Name of Guardian:</span><input class="irf-input" type="text" name="b_guardian_name"></td>
            <td><span class="irf-label">Guardian Address:</span><input class="irf-input" type="text" name="b_guardian_address"></td>
        </tr>

        {{-- ── ITEM C: VICTIM'S DATA ── --}}
        <tr><td colspan="3" class="irf-section">ITEM "C" — VICTIM'S DATA</td></tr>
        <tr>
            <td><span class="irf-label">Family Name:</span><input class="irf-input" type="text" name="c_family_name"></td>
            <td><span class="irf-label">First Name:</span><input class="irf-input" type="text" name="c_first_name" value="{{ $incident?->rider?->full_name ?? '' }}" placeholder="Full name — split into family/first name above"></td>
            <td>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:4px;">
                    <div><span class="irf-label">Middle Name:</span><input class="irf-input" type="text" name="c_middle_name"></div>
                    <div><span class="irf-label">Qualifier:</span><input class="irf-input" type="text" name="c_qualifier"></div>
                    <div><span class="irf-label">Nickname:</span><input class="irf-input" type="text" name="c_nickname"></div>
                </div>
            </td>
        </tr>
        <tr>
            <td style="width:16%"><span class="irf-label">Citizenship:</span><input class="irf-input" type="text" name="c_citizenship"></td>
            <td colspan="2">
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr 1fr 1fr; gap:4px;">
                    <div><span class="irf-label">Gender:</span><input class="irf-input" type="text" name="c_gender"></div>
                    <div><span class="irf-label">Civil Status:</span><input class="irf-input" type="text" name="c_civil_status"></div>
                    <div><span class="irf-label">Date of Birth:</span><input class="irf-input" type="date" name="c_dob" value="{{ $incident?->rider?->date_of_birth?->format('Y-m-d') ?? '' }}"></div>
                    <div><span class="irf-label">Age:</span><input class="irf-input" type="number" name="c_age" value="{{ $incident?->rider?->date_of_birth ? now()->diffInYears($incident?->rider->date_of_birth) : '' }}"></div>
                    <div><span class="irf-label">Place of Birth:</span><input class="irf-input" type="text" name="c_pob"></div>
                    <div><span class="irf-label">Phone Number:</span><input class="irf-input" type="text" name="c_phone" value="{{ $incident?->rider?->phone_number ?? '' }}"></div>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2"><span class="irf-label">Address (House Number/Street) Village Sitio:</span><input class="irf-input" type="text" name="c_address" value="{{ $incident?->rider?->address ?? '' }}"></td>
            <td>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:4px;">
                    <div><span class="irf-label">Barangay:</span><input class="irf-input" type="text" name="c_barangay"></div>
                    <div><span class="irf-label">Town/City:</span><input class="irf-input" type="text" name="c_town"></div>
                    <div><span class="irf-label">Province:</span><input class="irf-input" type="text" name="c_province"></div>
                </div>
            </td>
        </tr>
        <tr>
            <td><span class="irf-label">Highest Educational Attainment:</span><input class="irf-input" type="text" name="c_education"></td>
            <td><span class="irf-label">Occupation:</span><input class="irf-input" type="text" name="c_occupation"></td>
            <td><span class="irf-label">Relation to Suspect:</span><input class="irf-input" type="text" name="c_relation_suspect"></td>
        </tr>

        {{-- ── ITEM D: NARRATIVE ── --}}
        <tr><td colspan="3" class="irf-section">ITEM "D" — NARRATIVE OF INCIDENT</td></tr>
        <tr>
            <td><span class="irf-label">Date and Time Reported:</span><input class="irf-input" type="datetime-local" name="d_date_reported" value="{{ $incidentDateTime }}"></td>
            <td><span class="irf-label">Date and Time of Incident:</span><input class="irf-input" type="datetime-local" name="d_date_incident" value="{{ $incidentDateTime }}"></td>
            <td><span class="irf-label">Place of Incident: Barangay, Town/City, Province:</span><input class="irf-input" type="text" name="d_place_incident" value="{{ $incident?->address ?? '' }}"></td>
        </tr>
        <tr>
            <td colspan="3" style="font-size:.62rem; font-weight:700; padding:3px 5px; text-align:center;">
                THE NARRATIVE OF INCIDENT OR EVENT, ANSWERING THE WHO, WHEN, WHERE, WHY AND WHO HOW OF REPORTING.
            </td>
        </tr>
        <tr>
            <td colspan="3" style="height:200px; padding:6px;">
                <textarea class="irf-textarea" name="d_narrative" rows="10"
                          placeholder="Write the full narrative here..."></textarea>
            </td>
        </tr>

        {{-- Certify / Signatures --}}
        <tr>
            <td style="font-size:.62rem; font-weight:600; vertical-align:top; padding:6px;">
                I HERE BY CERTIFY TO THE CORRECTNESS OF THE FOREGOING TO THE BEST OF KNOWLEDGE AND BELIEF.
            </td>
            <td><span class="irf-label">Name of Reporting Person:</span><input class="irf-input" type="text" name="sig_reporting_name"></td>
            <td><span class="irf-label">Signature of Reporting Person:</span><input class="irf-input" type="text" name="sig_reporting_sig" placeholder="(Signature)"></td>
        </tr>
        <tr>
            <td style="font-size:.62rem; font-weight:600; padding:6px;">SUBSCRIBED AND SWORN TO BEFORE ME</td>
            <td><span class="irf-label">Name of Administering Officer (Duty Officer):</span><input class="irf-input" type="text" name="sig_admin_name"></td>
            <td><span class="irf-label">Signature of Administering Officer (Duty Officer):</span><input class="irf-input" type="text" name="sig_admin_sig" placeholder="(Signature)"></td>
        </tr>
        <tr>
            <td colspan="2" style="font-size:.62rem; font-weight:600; padding:6px;">
                RANK NAME AND DESIGNATION OF POLICE OFFICER (WHETHER HE/SHE IS THE DUTY INVESTIGATOR, INVESTIGATOR ON CASE OF THE ASSISTING POLICE OFFICER)<br>
                <input class="irf-input" type="text" name="sig_officer_rank" style="margin-top:4px;">
            </td>
            <td>
                <span class="irf-label">Signature of Duty Investigator/Investigator on Case/Assisting Police Officer:</span>
                <input class="irf-input" type="text" name="sig_officer_sig" placeholder="(Signature)">
            </td>
        </tr>
        <tr>
            <td><span class="irf-label">Incident Recorded in the Blotter By:</span><input class="irf-input" type="text" name="blotter_recorded_by"></td>
            <td><span class="irf-label">Rank/Name of Desk Officer:</span><input class="irf-input" type="text" name="desk_officer_name"></td>
            <td>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:4px;">
                    <div><span class="irf-label">Signature of Desk Officer:</span><input class="irf-input" type="text" name="desk_officer_sig" placeholder="(Signature)"></div>
                    <div><span class="irf-label">Blotter Entry Nr:</span><input class="irf-input" type="text" name="blotter_entry_nr"></div>
                </div>
            </td>
        </tr>

        {{-- Keep copy notice --}}
        <tr>
            <td colspan="3" style="font-size:.62rem; padding:6px; text-align:center; line-height:1.5;">
                Keep the copy of this Incident Record Form (IRF). An update of the progress of the investigation of the crime or incident that you
                reported will be given to you upon presentation of this IRF. For your reference, the data below is the contact details of this police station.
            </td>
        </tr>

        {{-- Police Station contact details --}}
        <tr>
            <td colspan="2"><span class="irf-label">Name of the Police Station:</span><input class="irf-input" type="text" name="station_name"></td>
            <td><span class="irf-label">Telephone:</span><input class="irf-input" type="text" name="station_tel"></td>
        </tr>
        <tr>
            <td colspan="2"><span class="irf-label">Investigator-on-Case:</span><input class="irf-input" type="text" name="investigator_name"></td>
            <td><span class="irf-label">Mobile Phone:</span><input class="irf-input" type="text" name="investigator_mobile"></td>
        </tr>
        <tr>
            <td colspan="2"><span class="irf-label">Name of Chief/Head of Office:</span><input class="irf-input" type="text" name="chief_name"></td>
            <td><span class="irf-label">Mobile Phone:</span><input class="irf-input" type="text" name="chief_mobile"></td>
        </tr>

    </table>
</div>

{{-- NEXT button --}}
<div class="irf-actions">
    <button type="button" class="btn-irf-next" onclick="goToPage(2)">NEXT</button>
</div>
</div>{{-- end page1 --}}


{{-- ══════════════════════════════════════════════════════
     PAGE 2 — Item E: Progress Report
══════════════════════════════════════════════════════ --}}
<div id="page2" style="display:none;">
<div class="irf-page">

    <table class="progress-table">
        <thead>
            <tr>
                <th colspan="2">ITEM "E" — PROGRESS REPORT</th>
            </tr>
            <tr>
                <th style="width:25%;">DATE</th>
                <th>PROGRESS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="vertical-align:top; padding:6px;">
                    <input type="date" name="e_date" style="width:100%; border:none; outline:none; background:transparent; font-size:.8rem;">
                </td>
                <td style="vertical-align:top; padding:6px;">
                    <textarea name="e_progress" style="width:100%; height:480px; border:none; outline:none; background:transparent; font-size:.8rem; resize:none; font-family:'Segoe UI',sans-serif;"
                              placeholder="Describe progress of investigation..."></textarea>
                </td>
            </tr>
        </tbody>
    </table>

</div>

{{-- BACK / Save / Save & Print buttons --}}
<div class="irf-actions" style="gap:12px;">
    <button type="button" class="btn-irf-next" style="background:#6c757d;" onclick="goToPage(1)">BACK</button>
    <button type="button" class="btn-irf-next btn-irf-save" id="saveBtn">SAVE</button>
    <button type="button" class="btn-irf-generate" id="saveAndPrintBtn">SAVE &amp; PRINT</button>
</div>
</div>{{-- end page2 --}}

</div>{{-- end irf-printable --}}

</form>

@endsection

@push('scripts')
<script>
    function goToPage(n) {
        document.getElementById('page1').style.display = n === 1 ? 'block' : 'none';
        document.getElementById('page2').style.display = n === 2 ? 'block' : 'none';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Reprint (investigation.incident-records.reprint) — overlays everything
    // that was actually typed the first time on top of whatever the page
    // already prefilled from the incident, so reopening a past IRF restores
    // it rather than starting blank again. Done in JS rather than adding
    // value="" to every one of the ~60 inputs individually.
    const savedData = @json($savedData ?? null);
    if (savedData) {
        const form = document.getElementById('irfForm');
        Object.entries(savedData).forEach(([name, value]) => {
            const field = form.elements[name];
            if (field && value !== null && value !== undefined) field.value = value;
        });
    }

    // Save confirmation — previously there was zero feedback on success and
    // a jarring native alert() on failure. Success auto-dismisses; errors
    // stay until closed, since silently losing typed data is worth noticing.
    function showToast(message, type) {
        document.querySelectorAll('.irf-toast').forEach(el => el.remove());

        const toast = document.createElement('div');
        toast.className = `irf-toast irf-toast-${type}`;
        const text = document.createElement('span');
        text.textContent = message;
        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.innerHTML = '&times;';
        closeBtn.onclick = () => toast.remove();
        toast.append(text, closeBtn);
        document.body.appendChild(toast);

        if (type === 'success') setTimeout(() => toast.remove(), 3500);
    }

    // Shared by both buttons — SAVE persists without printing, SAVE & PRINT
    // persists (marking printed_at) then opens the print dialog. Saving
    // always happens first so a failed/cancelled print never loses the data,
    // and printed_at is only ever set by the print path, never cleared, so
    // re-saving a previously-printed record doesn't erase that history.
    async function saveIncidentRecord(printed) {
        const form = document.getElementById('irfForm');
        const formData = new FormData(form);
        formData.set('printed', printed ? '1' : '0');
        const linkedIncident = form.elements['incident_id'].value;

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const result = await res.json();
            // Once saved, further saves from this same page update the same
            // row instead of creating duplicates.
            if (result.id) form.elements['record_id'].value = result.id;

            const linkNote = linkedIncident ? ' and linked to the incident' : ' (not linked to an incident)';
            showToast(
                printed ? `Saved & sent to print${linkNote}.` : `Saved${linkNote}.`,
                'success'
            );
        } catch (err) {
            console.error('Failed to save incident record:', err);
            showToast(
                printed
                    ? 'Could not save the record, but printing anyway — it won\'t appear on the Incident Report page until saved successfully.'
                    : 'Could not save the record. Check your connection and try again.',
                'error'
            );
        }

        if (printed) window.print();
    }

    document.getElementById('saveBtn').addEventListener('click', () => saveIncidentRecord(false));
    document.getElementById('saveAndPrintBtn').addEventListener('click', () => saveIncidentRecord(true));
</script>
@endpush
