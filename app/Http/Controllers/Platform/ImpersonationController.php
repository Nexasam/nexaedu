<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Platform\UserImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ImpersonationController extends Controller
{
    public function store(Request $request, User $user, UserImpersonationService $impersonation): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($validated['password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'The platform administrator password is incorrect.',
            ]);
        }

        $impersonation->start($request, $user);

        return redirect()->route('dashboard')->with('status', "You are now viewing the portal as {$user->name}.");
    }

    public function destroy(Request $request, UserImpersonationService $impersonation): RedirectResponse
    {
        $impersonation->stop($request);

        return redirect()->route('platform.users.index')->with('status', 'Support session ended. You are back in the platform portal.');
    }
}
