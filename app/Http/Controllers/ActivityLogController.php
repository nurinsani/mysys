<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends BaseController
{
    public function index(Request $request)
    {
        $title = 'Activity Log';
        $menus = $this->getMenus();

        $logs = Activity::with('causer')
            ->when($request->subject_type, fn($q) => $q->where('subject_type', $request->subject_type))
            ->when($request->log_name, fn($q) => $q->where('log_name', $request->log_name))
            ->when($request->event, fn($q) => $q->where('event', $request->event))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $logNames = Activity::select('log_name')->distinct()->orderBy('log_name')->pluck('log_name');
        $events = Activity::whereNotNull('event')->select('event')->distinct()->orderBy('event')->pluck('event');

        return view('admin.activity_log.index', compact('menus', 'title', 'logs', 'logNames', 'events'));
    }
}
