<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Receipt;
use App\Models\User;

/**
 * Admin-only — a manager gets no `view_receipts` grant at all. See
 * docs/modules/M11-manager-panel.md's capability table.
 */
class ReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_receipts');
    }

    public function view(User $user, Receipt $receipt): bool
    {
        return $user->can('view_receipts');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Receipt $receipt): bool
    {
        return false;
    }

    public function delete(User $user, Receipt $receipt): bool
    {
        return false;
    }

    public function cancel(User $user, Receipt $receipt): bool
    {
        return $user->can('cancel_receipts');
    }

    public function regenerate(User $user, Receipt $receipt): bool
    {
        return $user->can('regenerate_receipts');
    }
}
