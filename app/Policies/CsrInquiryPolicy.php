<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CsrInquiry;
use App\Models\User;

class CsrInquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_csr_inquiries');
    }

    public function view(User $user, CsrInquiry $csrInquiry): bool
    {
        return $user->can('manage_csr_inquiries');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_csr_inquiries');
    }

    public function update(User $user, CsrInquiry $csrInquiry): bool
    {
        return $user->can('manage_csr_inquiries');
    }

    public function delete(User $user, CsrInquiry $csrInquiry): bool
    {
        return $user->can('manage_csr_inquiries');
    }
}
