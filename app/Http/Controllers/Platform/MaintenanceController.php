<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformMaintenanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MaintenanceController extends Controller
{
    public function __construct(private PlatformMaintenanceService $maintenance) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Maintenance/Index', [
            'server' => $this->maintenance->status(),
            'consoleResult' => $request->session()->get('maintenance_console'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'operation' => [
                'required',
                Rule::in(['pull', 'optimize_clear', 'migrate', 'clear_logs']),
            ],
        ]);

        try {
            $result = $this->maintenance->execute($validated['operation']);

            return back()
                ->with('status', 'Maintenance operation completed.')
                ->with('maintenance_console', $result);
        } catch (Throwable $exception) {
            return back()
                ->withErrors(['maintenance' => 'The maintenance operation failed. Review the console output.'])
                ->with('maintenance_console', [
                    'operation' => $validated['operation'],
                    'success' => false,
                    'output' => mb_substr($exception->getMessage(), -20000),
                    'ran_at' => now()->toIso8601String(),
                ]);
        }
    }
}
