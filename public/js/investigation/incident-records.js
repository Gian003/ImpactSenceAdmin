// Investigation Incident Records (IRF) page — page navigation, reprint
// prefill, save-confirmation toasts, and the save/save+print flow. Extracted
// from resources/views/investigation/incident-records/index.blade.php.
// Server data comes in via window.IncidentRecordsConfig (set by a small
// inline <script> in that Blade file right before this one loads).

function goToPage(n) {
        document.getElementById('page1').style.display = n === 1 ? 'block' : 'none';
        document.getElementById('page2').style.display = n === 2 ? 'block' : 'none';
        document.querySelectorAll('.irf-step').forEach(function (el) {
            el.classList.toggle('active', Number(el.dataset.step) === n);
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Reprint (investigation.incident-records.reprint) — overlays everything
    // that was actually typed the first time on top of whatever the page
    // already prefilled from the incident, so reopening a past IRF restores
    // it rather than starting blank again. Done in JS rather than adding
    // value="" to every one of the ~60 inputs individually.
    const savedData = window.IncidentRecordsConfig.savedData;
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
    // persists (marking printed_at) then opens the generated PDF in a new
    // tab. Saving always happens first so a failed/cancelled PDF never loses
    // the data, and printed_at is only ever set by the print path, never
    // cleared, so re-saving a previously-printed record doesn't erase that
    // history.
    async function saveIncidentRecord(printed) {
        const form = document.getElementById('irfForm');
        const formData = new FormData(form);
        formData.set('printed', printed ? '1' : '0');
        // Optional chaining — not every view that reuses this script
        // necessarily has an incident_id field (e.g. one without a "Link to
        // Incident" picker); missing it should just mean "unlinked," not a
        // crash that silently kills both SAVE and SAVE & VIEW PDF.
        const linkedIncident = form.elements['incident_id']?.value ?? '';
        let savedId = null;

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
            if (result.id) {
                form.elements['record_id'].value = result.id;
                savedId = result.id;
            }

            const linkNote = linkedIncident ? ' and linked to the incident' : ' (not linked to an incident)';
            showToast(
                printed ? `Saved & sent to print${linkNote}.` : `Saved${linkNote}.`,
                'success'
            );
        } catch (err) {
            console.error('Failed to save incident record:', err);
            showToast(
                printed
                    ? 'Could not save the record, so there\'s no saved copy to generate a PDF from yet — try again.'
                    : 'Could not save the record. Check your connection and try again.',
                'error'
            );
        }

        // Only once we actually have a saved record id — the PDF route reads
        // the saved data back from the database, so there's nothing to
        // generate from if the save itself failed.
        if (printed && savedId) {
            window.open(
                window.IncidentRecordsConfig.pdfUrlTemplate.replace(':id', savedId),
                '_blank'
            );
        }
    }

    document.getElementById('saveBtn').addEventListener('click', () => saveIncidentRecord(false));
    document.getElementById('saveAndPrintBtn').addEventListener('click', () => saveIncidentRecord(true));
