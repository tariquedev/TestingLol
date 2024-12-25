<?php
namespace App\Transformers;

use App\Models\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'username'   => $user->username,
            'email'      => $user->email,
            'store_name' => $user->store_name,
            'avatar'     => $user->image,
            'subscription' => $user->subscription('default'),
            'billing'      => $user->paymentMethods()->first(),
        ];
    }
}
