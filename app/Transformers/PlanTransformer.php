<?php

namespace App\Transformers;

use App\Models\Plan;
use League\Fractal\TransformerAbstract;

class PlanTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected array $defaultIncludes = [
        //
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected array $availableIncludes = [
        //
    ];

    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(Plan $plan)
    {
        return [
            'id'              => $plan->id,
            'title'           => $plan->title,
            'slug'            => $plan->slug,
            'stripe_price_id' => $plan->stripe_price_id,
        ];
    }
}
