<?php

namespace Polirium\Modules\Task\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Polirium\Modules\Task\Models\Project;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-30 days', '+30 days');
        $endDate = $this->faker->dateTimeBetween($startDate, '+90 days');

        return [
            'uuid' => $this->faker->uuid(),
            'code' => 'PRJ-' . $this->faker->unique()->numberBetween(10000, 99999),
            'name' => $this->faker->company() . ' Project',
            'description' => $this->faker->sentence(10),
            'client_id' => null,
            'status' => $this->faker->randomElement(['planning', 'active', 'on_hold', 'completed', 'cancelled']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'planned_start_date' => $startDate->format('Y-m-d'),
            'planned_end_date' => $endDate->format('Y-m-d'),
            'actual_start_date' => $this->faker->optional(0.7)->dateTimeBetween($startDate, $endDate)?->format('Y-m-d'),
            'actual_end_date' => $this->faker->optional(0.5)->dateTimeBetween($startDate, '+120 days')?->format('Y-m-d'),
            'budget' => $this->faker->randomFloat(2, 1000, 100000),
            'progress_percentage' => $this->faker->randomFloat(2, 0, 100),
            'branch_id' => 1,
            'created_by' => 1,
            'updated_by' => $this->faker->boolean(80) ? 1 : null,
            'note' => $this->faker->optional(0.6)->sentence(5),
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => $this->faker->randomElement(['planning', 'active', 'on_hold']),
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'progress_percentage' => 100,
            'actual_end_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
        ]);
    }
}
