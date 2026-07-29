<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Members\UpdateMember;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('portal.profile.edit', [
            'member' => $request->user()->member,
        ]);
    }

    public function update(UpdateProfileRequest $request, UpdateMember $updateMember): RedirectResponse
    {
        $member = $request->user()->member;

        $data = $request->validated();

        if ($request->hasFile('photo_path')) {
            $data['photo_path'] = $request->file('photo_path')->store('members/photos', 'public');
        } else {
            unset($data['photo_path']);
        }

        $updateMember->handle($member, $data);

        return back()->with('status', 'profile-updated');
    }
}
