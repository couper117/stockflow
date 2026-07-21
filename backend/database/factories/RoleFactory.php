<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(array_keys(Role::ALL));

        return [
            'name' => $name,
            'label_key' => Role::ALL[$name],
        ];
    }

    /** Build a role by its fixed machine name (e.g. Role::SUPER_ADMIN). */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'label_key' => Role::ALL[$name] ?? "roles.{$name}",
        ]);
    }
}
