@extends('investigation.layouts.app')

@section('title', 'Incident Record Form — Simple Entry')

@push('styles')
{{-- .btn-irf-next / .btn-irf-save / .btn-irf-generate / .irf-toast* — shared
     with the official form, since this page reuses its JS (save/save+PDF,
     toast notifications) unchanged. --}}
<link rel="stylesheet" href="{{ asset('css/investigation/incident-record.css') }}">
<link rel="stylesheet" href="{{ asset('css/investigation/incident-record-simple.css') }}">
@endpush

@section('content')

@php
    // Same rules as the official form (investigation.incident-records.index)
    // — $incident isn't passed at all on the blank-form route, and `?->`
    // alone still throws "Undefined variable" if the variable was never set.
    $incident = $incident ?? null;
    $incidentDateTime = $incident?->created_at?->format('Y-m-d\TH:i');
@endphp

<div class="d-flex align-items-center justify-content-between mb-2">
    <a href="{{ route('investigation.incident-records.all') }}" style="font-size:.85rem; color:#1b3d52; font-weight:600;">
        View All Saved Records &rarr;
    </a>
    {{-- Escape hatch for cases that genuinely need every official field
         (suspect priors, full station/signature block, etc.) — this view
         intentionally doesn't force all of that up front. --}}
    <a href="{{ $incident ? route('investigation.incident-records.show', $incident) : route('investigation.incident-records.index') }}"
       style="font-size:.85rem; color:#1b3d52; font-weight:600;">
        Use Full Official Form &rarr;
    </a>
</div>

<form id="irfForm" method="POST" action="{{ route('investigation.incident-records.store') }}">
@csrf
<input type="hidden" name="record_id" value="{{ $recordId ?? '' }}">
{{-- The official form has a manual "Link to Incident" picker for this;
     this view skips that picker for simplicity and just links to whichever
     incident it was opened from (empty/unlinked on a blank/walk-in visit).
     Required regardless — incident-records.js reads
     form.elements['incident_id'] unconditionally, so leaving this out
     doesn't just mean "no incident link," it throws before the save even
     starts, silently breaking both SAVE and SAVE & VIEW PDF. --}}
<input type="hidden" name="incident_id" value="{{ $incident?->id }}">

<div class="simple-form-wrap">

    <div class="simple-form-card">
        <div class="simple-section-title">Report Basics</div>
        <div class="simple-row simple-row-3">
            <div class="simple-field">
                <label for="irf_entry_number">IRF Entry Number</label>
                <input type="text" id="irf_entry_number" name="irf_entry_number">
            </div>
            <div class="simple-field">
                <label for="type_of_incident">Type of Incident</label>
                <input type="text" id="type_of_incident" name="type_of_incident"
                       value="{{ $incident ? ucfirst($incident->type).' incident' : '' }}"
                       placeholder="(Operation) Manhunt Charlie/Arrest with Warrant">
            </div>
            <div class="simple-field">
                <label for="copy_for">Copy For</label>
                <input type="text" id="copy_for" name="copy_for">
            </div>
        </div>
        <div class="simple-row simple-row-3">
            <div class="simple-field">
                <label for="date_reported">Date &amp; Time Reported</label>
                <input type="datetime-local" id="date_reported" name="date_reported" value="{{ $incidentDateTime }}">
            </div>
            <div class="simple-field">
                <label for="date_incident">Date &amp; Time of Incident</label>
                <input type="datetime-local" id="date_incident" name="date_incident" value="{{ $incidentDateTime }}">
            </div>
            <div class="simple-field">
                <label for="place_incident">Place of Incident</label>
                <input type="text" id="place_incident" name="place_incident" value="{{ $incident?->address ?? '' }}"
                       placeholder="Barangay, Town/City, Province">
            </div>
        </div>
    </div>

    <div class="simple-form-card">
        <div class="simple-section-title">Who's Reporting This</div>
        <div class="simple-row simple-row-3">
            <div class="simple-field">
                <label for="a_family_name">Family Name</label>
                <input type="text" id="a_family_name" name="a_family_name">
            </div>
            <div class="simple-field">
                <label for="a_first_name">First Name</label>
                <input type="text" id="a_first_name" name="a_first_name">
            </div>
            <div class="simple-field">
                <label for="a_middle_name">Middle Name</label>
                <input type="text" id="a_middle_name" name="a_middle_name">
            </div>
        </div>
        <div class="simple-row simple-row-3">
            <div class="simple-field">
                <label for="a_citizenship">Citizenship</label>
                <input type="text" id="a_citizenship" name="a_citizenship">
            </div>
            <div class="simple-field">
                <label for="a_gender">Gender</label>
                <input type="text" id="a_gender" name="a_gender">
            </div>
            <div class="simple-field">
                <label for="a_address">Address</label>
                <input type="text" id="a_address" name="a_address">
            </div>
        </div>
        <div class="simple-row simple-row-2">
            <div class="simple-field">
                <label for="a_qualifier">Qualifier</label>
                <input type="text" id="a_qualifier" name="a_qualifier">
            </div>
            <div class="simple-field">
                <label for="a_nickname">Nickname</label>
                <input type="text" id="a_nickname" name="a_nickname">
            </div>
        </div>
    </div>

    <div class="simple-form-card">
        <div class="simple-section-title">Victim</div>
        <div class="simple-row simple-row-3">
            <div class="simple-field">
                <label for="c_family_name">Family Name</label>
                <input type="text" id="c_family_name" name="c_family_name">
            </div>
            <div class="simple-field">
                <label for="c_first_name">First Name</label>
                <input type="text" id="c_first_name" name="c_first_name"
                       value="{{ $incident?->rider?->full_name ?? '' }}"
                       placeholder="Full name — split into family/first name">
            </div>
            <div class="simple-field">
                <label for="c_middle_name">Middle Name</label>
                <input type="text" id="c_middle_name" name="c_middle_name">
            </div>
        </div>
        <div class="simple-row simple-row-3">
            <div class="simple-field">
                <label for="c_dob">Date of Birth</label>
                <input type="date" id="c_dob" name="c_dob" value="{{ $incident?->rider?->date_of_birth?->format('Y-m-d') ?? '' }}">
            </div>
            <div class="simple-field">
                <label for="c_age">Age</label>
                <input type="number" id="c_age" name="c_age" value="{{ $incident?->rider?->date_of_birth ? $incident->rider->date_of_birth->age : '' }}">
            </div>
            <div class="simple-field">
                <label for="c_gender">Gender</label>
                <input type="text" id="c_gender" name="c_gender">
            </div>
        </div>
        <div class="simple-row simple-row-2">
            <div class="simple-field">
                <label for="c_phone">Phone Number</label>
                <input type="text" id="c_phone" name="c_phone" value="{{ $incident?->rider?->phone_number ?? '' }}">
            </div>
            <div class="simple-field">
                <label for="c_address">Address</label>
                <input type="text" id="c_address" name="c_address" value="{{ $incident?->rider?->address ?? '' }}">
            </div>
        </div>
        <details class="simple-details"
                 data-fields="c_citizenship,c_civil_status,c_pob,c_barangay,c_town,c_province,c_education,c_occupation,c_relation_suspect,c_qualifier,c_nickname">
            <summary>More victim details (citizenship, background, barangay, etc.)</summary>
            <div class="simple-details-body">
                <div class="simple-row simple-row-3">
                    <div class="simple-field">
                        <label for="c_citizenship">Citizenship</label>
                        <input type="text" id="c_citizenship" name="c_citizenship">
                    </div>
                    <div class="simple-field">
                        <label for="c_civil_status">Civil Status</label>
                        <input type="text" id="c_civil_status" name="c_civil_status">
                    </div>
                    <div class="simple-field">
                        <label for="c_pob">Place of Birth</label>
                        <input type="text" id="c_pob" name="c_pob">
                    </div>
                </div>
                <div class="simple-row simple-row-3">
                    <div class="simple-field">
                        <label for="c_barangay">Barangay</label>
                        <input type="text" id="c_barangay" name="c_barangay">
                    </div>
                    <div class="simple-field">
                        <label for="c_town">Town/City</label>
                        <input type="text" id="c_town" name="c_town">
                    </div>
                    <div class="simple-field">
                        <label for="c_province">Province</label>
                        <input type="text" id="c_province" name="c_province">
                    </div>
                </div>
                <div class="simple-row simple-row-3">
                    <div class="simple-field">
                        <label for="c_education">Highest Educational Attainment</label>
                        <input type="text" id="c_education" name="c_education">
                    </div>
                    <div class="simple-field">
                        <label for="c_occupation">Occupation</label>
                        <input type="text" id="c_occupation" name="c_occupation">
                    </div>
                    <div class="simple-field">
                        <label for="c_relation_suspect">Relation to Suspect</label>
                        <input type="text" id="c_relation_suspect" name="c_relation_suspect">
                    </div>
                </div>
                <div class="simple-row simple-row-2">
                    <div class="simple-field">
                        <label for="c_qualifier">Qualifier</label>
                        <input type="text" id="c_qualifier" name="c_qualifier">
                    </div>
                    <div class="simple-field">
                        <label for="c_nickname">Nickname</label>
                        <input type="text" id="c_nickname" name="c_nickname">
                    </div>
                </div>
            </div>
        </details>
    </div>

    <details class="simple-details simple-details-card"
             data-fields="b_family_name,b_first_name,b_middle_name,b_gender,b_age,b_address,b_relation_victim,b_rank,b_criminal_record,b_citizenship,b_civil_status,b_dob,b_pob,b_barangay,b_town,b_province,b_education,b_occupation,b_unit,b_group,b_cr_barangay,b_barangay2,b_weight,b_influence,b_eye_color,b_hair_color,b_marks,b_qualifier,b_nickname,b_guardian_name,b_guardian_address">
        <summary>+ Suspect information <span class="simple-details-hint">(only if there is one)</span></summary>
        <div class="simple-details-body">
            <div class="simple-row simple-row-3">
                <div class="simple-field">
                    <label for="b_family_name">Family Name</label>
                    <input type="text" id="b_family_name" name="b_family_name">
                </div>
                <div class="simple-field">
                    <label for="b_first_name">First Name</label>
                    <input type="text" id="b_first_name" name="b_first_name">
                </div>
                <div class="simple-field">
                    <label for="b_middle_name">Middle Name</label>
                    <input type="text" id="b_middle_name" name="b_middle_name">
                </div>
            </div>
            <div class="simple-row simple-row-3">
                <div class="simple-field">
                    <label for="b_gender">Gender</label>
                    <input type="text" id="b_gender" name="b_gender">
                </div>
                <div class="simple-field">
                    <label for="b_age">Age</label>
                    <input type="number" id="b_age" name="b_age">
                </div>
                <div class="simple-field">
                    <label for="b_address">Address</label>
                    <input type="text" id="b_address" name="b_address">
                </div>
            </div>
            <div class="simple-row simple-row-3">
                <div class="simple-field">
                    <label for="b_relation_victim">Relation to Victim</label>
                    <input type="text" id="b_relation_victim" name="b_relation_victim">
                </div>
                <div class="simple-field">
                    <label for="b_rank">Rank (if AFP/PNP Personnel)</label>
                    <input type="text" id="b_rank" name="b_rank">
                </div>
                <div class="simple-field">
                    <label for="b_criminal_record">Previous Criminal Record</label>
                    <input type="text" id="b_criminal_record" name="b_criminal_record">
                </div>
            </div>
            <div class="simple-row simple-row-3">
                <div class="simple-field">
                    <label for="b_citizenship">Citizenship</label>
                    <input type="text" id="b_citizenship" name="b_citizenship">
                </div>
                <div class="simple-field">
                    <label for="b_civil_status">Civil Status</label>
                    <input type="text" id="b_civil_status" name="b_civil_status">
                </div>
                <div class="simple-field">
                    <label for="b_dob">Date of Birth</label>
                    <input type="date" id="b_dob" name="b_dob">
                </div>
            </div>
            <div class="simple-row simple-row-3">
                <div class="simple-field">
                    <label for="b_pob">Place of Birth</label>
                    <input type="text" id="b_pob" name="b_pob">
                </div>
                <div class="simple-field">
                    <label for="b_barangay">Barangay</label>
                    <input type="text" id="b_barangay" name="b_barangay">
                </div>
                <div class="simple-field">
                    <label for="b_town">Town/City</label>
                    <input type="text" id="b_town" name="b_town">
                </div>
            </div>
            <div class="simple-row simple-row-3">
                <div class="simple-field">
                    <label for="b_province">Province</label>
                    <input type="text" id="b_province" name="b_province">
                </div>
                <div class="simple-field">
                    <label for="b_education">Highest Educational Attainment</label>
                    <input type="text" id="b_education" name="b_education">
                </div>
                <div class="simple-field">
                    <label for="b_occupation">Occupation</label>
                    <input type="text" id="b_occupation" name="b_occupation">
                </div>
            </div>
            <div class="simple-row simple-row-3">
                <div class="simple-field">
                    <label for="b_unit">Unit Assignment</label>
                    <input type="text" id="b_unit" name="b_unit">
                </div>
                <div class="simple-field">
                    <label for="b_group">Group Affiliation</label>
                    <input type="text" id="b_group" name="b_group">
                </div>
                <div class="simple-field">
                    <label for="b_cr_barangay">Barangay (Criminal Record)</label>
                    <input type="text" id="b_cr_barangay" name="b_cr_barangay">
                </div>
            </div>
            <div class="simple-row simple-row-3">
                <div class="simple-field">
                    <label for="b_barangay2">Barangay</label>
                    <input type="text" id="b_barangay2" name="b_barangay2">
                </div>
                <div class="simple-field">
                    <label for="b_weight">Weight</label>
                    <input type="text" id="b_weight" name="b_weight">
                </div>
                <div class="simple-field">
                    <label for="b_influence">Under the Influence</label>
                    <input type="text" id="b_influence" name="b_influence">
                </div>
            </div>
            <div class="simple-row simple-row-3">
                <div class="simple-field">
                    <label for="b_eye_color">Color of Eyes</label>
                    <input type="text" id="b_eye_color" name="b_eye_color">
                </div>
                <div class="simple-field">
                    <label for="b_hair_color">Color of Hair</label>
                    <input type="text" id="b_hair_color" name="b_hair_color">
                </div>
                <div class="simple-field">
                    <label for="b_marks">Distinguishing Marks</label>
                    <input type="text" id="b_marks" name="b_marks">
                </div>
            </div>
            <div class="simple-row simple-row-3">
                <div class="simple-field">
                    <label for="b_qualifier">Qualifier</label>
                    <input type="text" id="b_qualifier" name="b_qualifier">
                </div>
                <div class="simple-field">
                    <label for="b_nickname">Nickname</label>
                    <input type="text" id="b_nickname" name="b_nickname">
                </div>
            </div>
            <div class="simple-row simple-row-2">
                <div class="simple-field">
                    <label for="b_guardian_name">Name of Guardian (if a minor)</label>
                    <input type="text" id="b_guardian_name" name="b_guardian_name">
                </div>
                <div class="simple-field">
                    <label for="b_guardian_address">Guardian Address</label>
                    <input type="text" id="b_guardian_address" name="b_guardian_address">
                </div>
            </div>
        </div>
    </details>

    <div class="simple-form-card">
        <div class="simple-section-title">What Happened</div>
        <div class="simple-row simple-row-4">
            <div class="simple-field">
                <label for="vehicles_involved">Vehicles Involved</label>
                <input type="number" min="0" id="vehicles_involved" name="vehicles_involved" value="{{ $incident?->vehicles_involved }}">
            </div>
            <div class="simple-field">
                <label for="injured_count">Injured</label>
                <input type="number" min="0" id="injured_count" name="injured_count" value="{{ $incident?->injured_count }}">
            </div>
            <div class="simple-field">
                <label for="road_condition">Road Condition</label>
                <select id="road_condition" name="road_condition">
                    <option value=""></option>
                    @foreach(['Dry', 'Wet', 'Icy', 'Under Repair'] as $opt)
                    <option value="{{ $opt }}" @selected($incident?->road_condition === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="simple-field">
                <label for="weather_condition">Weather</label>
                <select id="weather_condition" name="weather_condition">
                    <option value=""></option>
                    @foreach(['Clear', 'Cloudy', 'Rainy', 'Foggy', 'Stormy'] as $opt)
                    <option value="{{ $opt }}" @selected($incident?->weather_condition === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="simple-field">
            <label for="d_narrative">Narrative — who, when, where, why, and how</label>
            <textarea id="d_narrative" name="d_narrative" rows="6" placeholder="Write the full narrative here..."></textarea>
        </div>
    </div>

    <details class="simple-details simple-details-card"
             data-fields="sig_reporting_name,sig_reporting_sig,sig_admin_name,sig_admin_sig,sig_officer_rank,sig_officer_sig,blotter_recorded_by,desk_officer_name,blotter_entry_nr,desk_officer_sig,station_name,station_tel,investigator_name,investigator_mobile,chief_name,chief_mobile">
        <summary>+ Signatures &amp; station info <span class="simple-details-hint">(can be filled in later)</span></summary>
        <div class="simple-details-body">
            <div class="simple-row simple-row-2">
                <div class="simple-field">
                    <label for="sig_reporting_name">Name of Reporting Person</label>
                    <input type="text" id="sig_reporting_name" name="sig_reporting_name">
                </div>
                <div class="simple-field">
                    <label for="sig_reporting_sig">Signature of Reporting Person</label>
                    <input type="text" id="sig_reporting_sig" name="sig_reporting_sig" placeholder="(Signature)">
                </div>
            </div>
            <div class="simple-row simple-row-2">
                <div class="simple-field">
                    <label for="sig_admin_name">Name of Administering Officer (Duty Officer)</label>
                    <input type="text" id="sig_admin_name" name="sig_admin_name">
                </div>
                <div class="simple-field">
                    <label for="sig_admin_sig">Signature of Administering Officer</label>
                    <input type="text" id="sig_admin_sig" name="sig_admin_sig" placeholder="(Signature)">
                </div>
            </div>
            <div class="simple-row simple-row-2">
                <div class="simple-field">
                    <label for="sig_officer_rank">Rank/Name/Designation of Police Officer</label>
                    <input type="text" id="sig_officer_rank" name="sig_officer_rank">
                </div>
                <div class="simple-field">
                    <label for="sig_officer_sig">Signature of Duty Investigator/Officer</label>
                    <input type="text" id="sig_officer_sig" name="sig_officer_sig" placeholder="(Signature)">
                </div>
            </div>
            <div class="simple-row simple-row-3">
                <div class="simple-field">
                    <label for="blotter_recorded_by">Incident Recorded in the Blotter By</label>
                    <input type="text" id="blotter_recorded_by" name="blotter_recorded_by">
                </div>
                <div class="simple-field">
                    <label for="desk_officer_name">Rank/Name of Desk Officer</label>
                    <input type="text" id="desk_officer_name" name="desk_officer_name">
                </div>
                <div class="simple-field">
                    <label for="blotter_entry_nr">Blotter Entry Nr</label>
                    <input type="text" id="blotter_entry_nr" name="blotter_entry_nr">
                </div>
            </div>
            <div class="simple-field">
                <label for="desk_officer_sig">Signature of Desk Officer</label>
                <input type="text" id="desk_officer_sig" name="desk_officer_sig" placeholder="(Signature)">
            </div>
            <div class="simple-row simple-row-2">
                <div class="simple-field">
                    <label for="station_name">Name of the Police Station</label>
                    <input type="text" id="station_name" name="station_name">
                </div>
                <div class="simple-field">
                    <label for="station_tel">Station Telephone</label>
                    <input type="text" id="station_tel" name="station_tel">
                </div>
            </div>
            <div class="simple-row simple-row-2">
                <div class="simple-field">
                    <label for="investigator_name">Investigator-on-Case</label>
                    <input type="text" id="investigator_name" name="investigator_name">
                </div>
                <div class="simple-field">
                    <label for="investigator_mobile">Investigator Mobile Phone</label>
                    <input type="text" id="investigator_mobile" name="investigator_mobile">
                </div>
            </div>
            <div class="simple-row simple-row-2">
                <div class="simple-field">
                    <label for="chief_name">Name of Chief/Head of Office</label>
                    <input type="text" id="chief_name" name="chief_name">
                </div>
                <div class="simple-field">
                    <label for="chief_mobile">Chief Mobile Phone</label>
                    <input type="text" id="chief_mobile" name="chief_mobile">
                </div>
            </div>
        </div>
    </details>

    <div class="simple-actions">
        <button type="button" class="btn-irf-next btn-irf-save" id="saveBtn">SAVE</button>
        <button type="button" class="btn-irf-generate" id="saveAndPrintBtn">SAVE &amp; VIEW PDF</button>
    </div>

</div>
</form>

@endsection

@push('scripts')
<script>
    window.IncidentRecordsConfig = {
        savedData: @json($savedData ?? null),
        pdfUrlTemplate: @json(route('investigation.incident-records.pdf', ':id')),
    };
</script>
<script src="{{ asset('js/investigation/incident-records.js') }}?v={{ filemtime(public_path('js/investigation/incident-records.js')) }}"></script>
<script>
    // Reopening a saved record shouldn't hide data the officer already
    // entered behind a collapsed section — expand Suspect/Signatures if
    // either actually has something in it once the savedData overlay (in
    // incident-records.js) has run.
    (function () {
        const savedData = window.IncidentRecordsConfig.savedData;
        if (!savedData) return;
        document.querySelectorAll('.simple-details[data-fields]').forEach((details) => {
            const fields = details.dataset.fields.split(',');
            const hasValue = fields.some((name) => {
                const value = savedData[name];
                return value !== null && value !== undefined && value !== '';
            });
            if (hasValue) details.open = true;
        });
    })();
</script>
@endpush
