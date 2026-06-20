@extends('investigation.layouts.app')

@section('title', 'Incident Record Form')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/investigation/incident-record.css') }}">
@endpush

@section('content')

<div class="irf-printable">

{{-- ══════════════════════════════════════════════════════
     PAGE 1 — IRF Main Form
══════════════════════════════════════════════════════ --}}
<div id="page1">
<div class="irf-page">

    {{-- Document Header --}}
    <div class="irf-doc-header mb-2">
        <img src="{{ asset('images/pnp-logo.png') }}" alt="PNP" onerror="this.style.display='none'">
        <div>
            <h2>Philippine National Police</h2>
            <h1>INCIDENT RECORD FORM</h1>
        </div>
        <img src="{{ asset('images/pnp-logo.png') }}" alt="PNP" onerror="this.style.display='none'">
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
                <input class="irf-input" type="text" name="type_of_incident" placeholder="(Operation) Manhunt Charlie/Arrest with Warrant">
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
                <input class="irf-input" type="datetime-local" name="date_reported">
            </td>
            <td>
                <span class="irf-label">Date and Time of Incident:</span>
                <input class="irf-input" type="datetime-local" name="date_incident">
            </td>
            <td>
                <span class="irf-label">Place of Incident: Barangay, Town/City, Province:</span>
                <input class="irf-input" type="text" name="place_incident">
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
            <td><span class="irf-label">First Name:</span><input class="irf-input" type="text" name="c_first_name"></td>
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
                    <div><span class="irf-label">Date of Birth:</span><input class="irf-input" type="date" name="c_dob"></div>
                    <div><span class="irf-label">Age:</span><input class="irf-input" type="number" name="c_age"></div>
                    <div><span class="irf-label">Place of Birth:</span><input class="irf-input" type="text" name="c_pob"></div>
                    <div><span class="irf-label">Phone Number:</span><input class="irf-input" type="text" name="c_phone"></div>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2"><span class="irf-label">Address (House Number/Street) Village Sitio:</span><input class="irf-input" type="text" name="c_address"></td>
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
            <td><span class="irf-label">Date and Time Reported:</span><input class="irf-input" type="datetime-local" name="d_date_reported"></td>
            <td><span class="irf-label">Date and Time of Incident:</span><input class="irf-input" type="datetime-local" name="d_date_incident"></td>
            <td><span class="irf-label">Place of Incident: Barangay, Town/City, Province:</span><input class="irf-input" type="text" name="d_place_incident"></td>
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
    <button class="btn-irf-next" onclick="goToPage(2)">NEXT</button>
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
                    <input type="date" style="width:100%; border:none; outline:none; background:transparent; font-size:.8rem;">
                </td>
                <td style="vertical-align:top; padding:6px;">
                    <textarea style="width:100%; height:480px; border:none; outline:none; background:transparent; font-size:.8rem; resize:none; font-family:'Segoe UI',sans-serif;"
                              placeholder="Describe progress of investigation..."></textarea>
                </td>
            </tr>
        </tbody>
    </table>

</div>

{{-- GENERATE / Back buttons --}}
<div class="irf-actions" style="gap:12px;">
    <button class="btn-irf-next" style="background:#6c757d;" onclick="goToPage(1)">BACK</button>
    <button class="btn-irf-generate" onclick="window.print()">GENERATE</button>
</div>
</div>{{-- end page2 --}}

</div>{{-- end irf-printable --}}

@endsection

@push('scripts')
<script>
    function goToPage(n) {
        document.getElementById('page1').style.display = n === 1 ? 'block' : 'none';
        document.getElementById('page2').style.display = n === 2 ? 'block' : 'none';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
</script>
@endpush
