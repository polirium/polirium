<?php

namespace Polirium\Modules\Task\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Polirium\Modules\Task\Models\Task;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-30 days', '+60 days');
        $endDate = (clone $startDate)->modify('+' . $this->faker->numberBetween(1, 30) . ' days');
        $estimatedHours = $this->faker->randomFloat(2, 1, 100);

        return [
            'uuid' => $this->faker->uuid(),
            'code' => 'TSK-' . $this->faker->unique()->numberBetween(10000, 99999),
            'project_id' => 1,
            'parent_id' => null,
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional(0.7)->sentence(8),
            'status' => $this->faker->randomElement(['backlog', 'todo', 'in_progress', 'review', 'done', 'cancelled']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'assigned_to' => $this->faker->boolean(80) ? 1 : null,
            'planned_start_date' => $startDate->format('Y-m-d'),
            'planned_end_date' => $endDate->format('Y-m-d'),
            'actual_start_date' => $this->faker->boolean(60) ? (clone $startDate)->modify('+' . $this->faker->numberBetween(0, $endDate->diff($startDate)->days) . ' days')->format('Y-m-d') : null,
            'actual_end_date' => $this->faker->boolean(40) ? (clone $endDate)->modify('+' . $this->faker->numberBetween(-5, 20) . ' days')->format('Y-m-d') : null,
            'estimated_hours' => $estimatedHours,
            'actual_hours' => $this->faker->randomFloat(2, 0, $estimatedHours),
            'progress_percentage' => $this->faker->randomFloat(2, 0, 100),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'branch_id' => 1,
            'created_by' => 1,
            'updated_by' => $this->faker->boolean(80) ? 1 : null,
            'note' => $this->faker->optional(0.6)->sentence(5),
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => $this->faker->randomElement(['todo', 'in_progress', 'review']),
        ]);
    }

    public function done(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'done',
            'progress_percentage' => 100,
            'actual_hours' => $attributes['estimated_hours'] ?? $this->faker->randomFloat(2, 1, 100),
        ]);
    }

    public function withParent(int $parentId): self
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId,
        ]);
    }
}
