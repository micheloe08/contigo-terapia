<?php

namespace Database\Factories;

use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'title'        => $this->faker->sentence(3),
            'description'  => $this->faker->sentence(),
            'content_type' => 'text',
            'text_content' => $this->faker->paragraph(),
            'order'        => $this->faker->numberBetween(1, 20),
        ];
    }
}
