<?php

namespace Polirium\Modules\Task\Database\Seeders;

use Illuminate\Database\Seeder;
use Polirium\Modules\Task\Database\Factories\ProjectFactory;
use Polirium\Modules\Task\Database\Factories\TaskFactory;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample projects
        $projects = ProjectFactory::new()
            ->count(5)
            ->active()
            ->create();

        // Create tasks for each project
        foreach ($projects as $project) {
            // Create root tasks
            $rootTasks = TaskFactory::new()
                ->count(3)
                ->active()
                ->create(['project_id' => $project->id]);

            // Create subtasks for some root tasks
            foreach ($rootTasks as $rootTask) {
                if (rand(0, 1)) {
                    TaskFactory::new()
                        ->count(rand(1, 3))
                        ->active()
                        ->create([
                            'project_id' => $project->id,
                            'parent_id' => $rootTask->id,
                        ]);
                }
            }
        }

        // Create a completed project
        ProjectFactory::new()
            ->completed()
            ->create(['name' => 'Completed Sample Project']);

        $this->command->info('Projects seeded successfully!');
    }
}
