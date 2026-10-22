<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    protected $model = \App\Models\Article::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(6);

        return [
            'category_id' => Category::query()->inRandomOrder()->first()?->id ?? Category::factory(),
            'author_id' => User::query()->inRandomOrder()->first()?->id,
            'title' => rtrim($title, '.'),
            'slug' => str(rtrim($title, '.'))->slug(),
            'excerpt' => $this->faker->sentence(18),
            'content' => implode("\n\n", $this->faker->paragraphs(5)),
            'cover_image' => null,
            'published_at' => $this->faker->boolean(80) ? now()->subDays($this->faker->numberBetween(0, 60)) : null,
        ];
    }
}
