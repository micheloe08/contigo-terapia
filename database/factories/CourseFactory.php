<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'title'        => $this->faker->sentence(3),
            'description'  => $this->faker->paragraph(),
            'thumbnail'    => null,
            'price'        => 0,
            'is_published' => true,
            'order'        => $this->faker->numberBetween(1, 100),
        ];
    }
}
