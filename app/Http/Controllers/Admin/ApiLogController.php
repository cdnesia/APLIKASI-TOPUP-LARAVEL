<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['provider', 'result', 'q']);

        return view('admin.api-logs.index', [
            'logs' => ApiLog::query()->filter($filters)->latest('id')->paginate(30)->withQueryString(),
            'failedToday' => ApiLog::query()->where('success', false)->where('created_at', '>=', today())->count(),
        ]);
    }
}
