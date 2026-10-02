<?php

namespace Database\Factories;

use App\Models\CourseModule;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseModuleFactory extends Factory
{
    protected $model = CourseModule::class;

    public function definition(): array
    {
        return [
            'title'       => $this->faker->sentence(2),
            'description' => $this->faker->sentence(),
            'order'       => $this->faker->numberBetween(1, 10),
        ];
    }
}
