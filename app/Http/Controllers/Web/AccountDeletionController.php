<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use RuntimeException;

class AccountDeletionController extends Controller
{
    public function show(): View
    {
        return view('account-deletion');
    }

    public function destroy(Request $request): RedirectResponse|View
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'confirm' => ['accepted'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('site.account_deletion_invalid')]);
        }

        try {
            app(AccountDeletionService::class)->delete($user);
        } catch (RuntimeException $e) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $e->getMessage()]);
        }

        return view('account-deletion-done');
    }
}
