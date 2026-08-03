<x-filament-panels::page>
    <div style="border-radius:0.75rem;border:1px solid #d9d4c6;background:#ffffff;padding:1.5rem;">
        <p style="margin-bottom:1rem;font-size:0.875rem;color:#55524a;">
            super-admin and admin bypass every permission check regardless of what's ticked here —
            this matrix only governs manager (and any future custom role).
        </p>

        <div style="overflow-x:auto;">
            <table style="width:100%;font-size:0.875rem;border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left;font-size:0.75rem;color:#55524a;">
                        <th style="padding-bottom:0.5rem;padding-right:1rem;">Permission</th>
                        @foreach ($this->roles() as $role)
                            <th style="padding-bottom:0.5rem;padding-left:0.5rem;padding-right:0.5rem;text-align:center;">{{ $role->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->permissions() as $permission)
                        <tr>
                            <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-right:1rem;border-bottom:1px solid #d9d4c6;">{{ $permission->name }}</td>
                            @foreach ($this->roles() as $role)
                                <td style="padding-top:0.5rem;padding-bottom:0.5rem;padding-left:0.5rem;padding-right:0.5rem;text-align:center;border-bottom:1px solid #d9d4c6;">
                                    <input
                                        type="checkbox"
                                        wire:model="grants.{{ $role->name }}"
                                        value="{{ $permission->id }}"
                                    >
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <button
            type="button"
            wire:click="save"
            style="margin-top:1.5rem;border-radius:0.375rem;background:#1f7a4d;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;color:#ffffff;"
        >
            Save
        </button>
    </div>
</x-filament-panels::page>
