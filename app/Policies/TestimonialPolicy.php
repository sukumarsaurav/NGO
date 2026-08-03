<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Testimonial;
use App\Models\User;

class TestimonialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_testimonials');
    }

    public function view(User $user, Testimonial $testimonial): bool
    {
        return $user->can('manage_testimonials');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_testimonials');
    }

    public function update(User $user, Testimonial $testimonial): bool
    {
        return $user->can('manage_testimonials');
    }

    public function delete(User $user, Testimonial $testimonial): bool
    {
        return $user->can('manage_testimonials');
    }
}
