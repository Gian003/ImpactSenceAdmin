<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvestigationOfficer;
use App\Models\TocPersonnel;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index()
    {
        // withTrashed() is what makes deactivation reversible. Deactivating an
        // officer soft-deletes them, and these queries excluded soft-deleted
        // rows — so the officer vanished from the page the moment they were
        // deactivated, and the "Reactivate" button and the "Inactive" badge
        // below them were markup that could never render. There was no way
        // back through the interface at all.
        return view('admin.users.index', [
            'tocOfficers' => TocPersonnel::withTrashed()->latest()->get(),
            'invOfficers' => InvestigationOfficer::withTrashed()->latest()->get(),
        ]);
    }

    /**
     * Bound by id rather than by the implicit route model, because implicit
     * binding excludes soft-deleted rows: the route that reactivates a
     * deactivated officer could not resolve the very officer it exists to
     * reactivate, and returned 404.
     */
    public function toggleToc(string $officer)
    {
        $officer = TocPersonnel::withTrashed()->findOrFail($officer);

        // TocPersonnel has no is_active column by default — add it via migration if needed.
        // For now we soft-delete to deactivate and restore to reactivate.
        if ($officer->trashed()) {
            $officer->restore();
            $status = 'reactivated';
        } else {
            $officer->delete();
            $status = 'deactivated';
        }
        return back()->with('success', "{$officer->full_name} has been {$status}.");
    }

    /** Same binding fix as toggleToc above. */
    public function toggleInvestigation(string $officer)
    {
        $officer = InvestigationOfficer::withTrashed()->findOrFail($officer);

        if ($officer->trashed()) {
            $officer->restore();
            $status = 'reactivated';
        } else {
            $officer->delete();
            $status = 'deactivated';
        }
        return back()->with('success', "{$officer->full_name} has been {$status}.");
    }
}
