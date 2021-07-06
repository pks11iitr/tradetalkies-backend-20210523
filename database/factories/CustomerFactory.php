<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'username' => $this->faker->unique()->userName,
            'email' => $this->faker->unique()->safeEmail,
            'mobile' => $this->faker->unique()->regexify('9[0-9]{9}'),
            'email_verified_at' => now(),
            'status'=>1,
            'push_likes'=>rand(0,1),
            'push_mentions'=>rand(0,1),
            'push_direct_messages'=>rand(0,1),
            'push_follows'=>rand(0,1),
            'push_watchlists'=>rand(0,1),
            'push_rooms'=>rand(0,1),
            'email_likes'=>rand(0,1),
            'email_mentions'=>rand(0,1),
            'email_direct_messages'=>rand(0,1),
            'email_follows'=>rand(0,1),
            'email_watchlist'=>rand(0,1),
            'email_rooms'=>rand(0,1),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ];
    }
}
