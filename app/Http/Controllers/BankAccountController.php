<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Resources\BankAccountResource;
use App\Models\BankAccount;
use App\Models\BankAddress;
use App\Models\DefaultWithdrawMethod;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Lanos\CashierConnect\Contracts\StripeAccount;

class BankAccountController extends Controller
{
    protected $user;

    function __construct()
    {
        $this->user = Auth::user();
    }
    public function index()
    {
        return BankAccountResource::collection($this->user->banks);
    }

    public function store(StoreBankAccountRequest $request)
    {
        $user = $this->user;
        $bank = new BankAccount;
        $bank->bank_name = $request->bank_name;
        $bank->account_holder_name = $request->account_holder_name;
        $bank->account_number = $request->account_number;
        $bank->routing_number = $request->routing_number;
        $bank->account_type = $request->account_type;
        $bank->currency = $request->currency;
        $bank->bank_type = $request->bank_type;
        if ($user->banks()->count() == 0) {
            $bank->is_default_payment_method = true;
        }

        $user->banks()->save($bank);

        $address = new BankAddress;
        $address->address = $request->address;
        $address->country = $request->country;
        $address->city = $request->city;
        $address->post_code = $request->post_code;

        $bank->address()->save($address);

        return new BankAccountResource($bank);
    }

    public function update(BankAccount $bank, StoreBankAccountRequest $request)
    {
        $bank->bank_name = $request->get('bank_name', $bank->bank_name);
        $bank->account_holder_name = $request->get('account_holder_name', $bank->account_holder_name);
        $bank->account_number = $request->get('account_number', $bank->account_number);
        $bank->routing_number = $request->get('routing_number', $bank->routing_number);
        $bank->account_type = $request->get('account_type', $bank->account_type);
        $bank->bank_type = $request->get('bank_type', $bank->bank_type);
        $bank->currency = $request->get('currency', $bank->currency);

        $this->user->banks()->save($bank);

        if ($request->is_default_payment_method == true) {
            $this->user->banks()->update(['is_default_payment_method' => false]);
            $bank->is_default_payment_method = true;
            $bank->save();
        }


        $address = $bank->address;
        $address->address = $request->get('address', $address->address);
        $address->country = $request->get('country', $address->country);
        $address->city = $request->get('city', $address->city);
        $address->post_code = $request->get('post_code', $address->post_code);

        $bank->address()->save($address);

        return new BankAccountResource($bank);
    }

    public function delete(BankAccount $bank)
    {
        $bank->delete();

        return response()->json([
            'message'   => 'Successfully deleted bank account'
        ]);
    }
}