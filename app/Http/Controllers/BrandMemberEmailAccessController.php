<?php

namespace App\Http\Controllers;

use App\Models\BrandMember;
use App\Models\RoadmapStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BrandMemberEmailAccessController extends Controller
{
    public function show(Request $request, RoadmapStep $step, BrandMember $member): View|RedirectResponse
    {
        $this->ensureValidAccess($step, $member);

        if ($request->user()?->id === $member->user_id) {
            return to_route('member.tasks.show', $step);
        }

        return view('auth.brand-member-task-login', [
            'member' => $member,
            'step' => $step,
            'accessUrl' => $request->fullUrl(),
        ]);
    }

    public function authenticate(Request $request, RoadmapStep $step, BrandMember $member): RedirectResponse
    {
        $this->ensureValidAccess($step, $member);
        $request->validate(['password' => ['required', 'string']]);

        if (! Auth::attempt(['email' => $member->email, 'password' => $request->string('password')], true)
            || Auth::id() !== $member->user_id) {
            throw ValidationException::withMessages([
                'password' => 'Le mot de passe ne correspond pas au compte collaborateur qui a reçu cet e-mail.',
            ]);
        }

        $request->session()->regenerate();

        return to_route('member.tasks.show', $step);
    }

    private function ensureValidAccess(RoadmapStep $step, BrandMember $member): void
    {
        abort_unless(
            $member->status === 'active'
            && $member->user_id
            && $step->brand_member_id === $member->id,
            403,
            'Cette invitation de tâche n’est plus valide.'
        );
    }
}
