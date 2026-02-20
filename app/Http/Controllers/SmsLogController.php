<?php

namespace App\Http\Controllers;

use App\Models\SmsLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsLogController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $smsProvider = config('services.twilio.sid') ? 'Twilio' : 'Semaphore PH';

        $logs = SmsLog::query()
            ->with(['student', 'guardian'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->limit(80)
            ->get();

        return view('sms-logs.index', [
            'logs' => $logs,
            'status' => $status,
            'smsProvider' => $smsProvider,
        ]);
    }
}
