<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Laravel\Cashier\Cashier;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stripe = Cashier::stripe();

        $plans = [
            [
                'title'    => 'Monthly',
                'slug'     => Str::slug('Monthly'),
                'interval' => 'month',
                'price'    => 200,
            ],
            [
                'title'    => 'Yearly',
                'slug'     => Str::slug('Yearly'),
                'interval' => 'year',
                'price'    => 400,
            ],
        ];

        foreach($plans as $plan) {
            $plan = (object) $plan;

            $price = $stripe->prices->create([
                'currency'     => config('cashier.currency'),
                'unit_amount'  => $plan->price,
                'recurring'    => ['interval' => $plan->interval],
                'product_data' => ['name'     => $plan->title],
            ]);

            Plan::create([
                'title'           => $plan->title,
                'slug'            => $plan->slug,
                'stripe_price_id' => $price->id
            ]);
        }
    }
}
