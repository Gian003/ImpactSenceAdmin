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
        return view('admin.users.index', [
            'tocOfficers' => TocPersonnel::latest()->get(),
            'invOfficers' => InvestigationOfficer::latest()->get(),
        ]);
    }

    public function toggleToc(TocPersonnel $officer)
    {
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

    public function toggleInvestigation(InvestigationOfficer $officer)
    {
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
