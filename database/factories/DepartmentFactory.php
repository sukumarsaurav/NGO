<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'North Zone', 'South Zone', 'East Zone', 'West Zone',
            'Fundraising', 'Volunteer Coordination', 'Field Operations', 'Communications',
        ]).' '.fake()->unique()->numberBetween(1, 9999);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'parent_id' => null,
            'manager_user_id' => null,
            'is_active' => true,
        ];
    }
}
