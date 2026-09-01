<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manage-users');

        $query = ActivityLog::query()->with('actor')->latest('id');

        if ($action = $request->string('action')->toString()) {
            $query->where('action', $action);
        }

        if ($subject = $request->string('subject')->toString()) {
            $query->where('subject_type', $subject);
        }

        return view('admin.history.index', [
            'logs' => $query->paginate(40)->withQueryString(),
            'actions' => ActivityLog::ACTIONS,
            'subjects' => ActivityLog::SUBJECTS,
        ]);
    }
}
