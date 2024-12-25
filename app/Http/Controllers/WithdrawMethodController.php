<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\ConnectStripe;
use App\Models\DefaultWithdrawMethod;
use App\Models\Wise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WithdrawMethodController extends Controller
{
    public function wiseConnect(Request $request){
        $rules = [
            'wise_email' => 'required|email',
            'currency' => 'string|size:3',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $user = auth()->user();
        $wise = Wise::firstOrNew(['user_id' => $user->id]);
        $wise->user_id = $user->id;
        $wise->wise_email  = $request->wise_email;
        $wise->currency = $request->currency;
        $wise->save();

        return response()->json([
            'message' => "Wise added successfully",
            "data" => [
                'wise_email' => $user->wise->wise_email ?? false
            ]
        ], 200);
    }

    public function getWiseDetails(){
        $user = auth()->user();
        if (!$user->wise) {
            return response()->json([
                'message' => "Wise not found",
                "data" => [
                    'is_connected' => false
                ]
            ], 200);
        }
        return response()->json([
            'message' => "Wise connected information",
            "data" => [
                'id' => $user->wise->id,
                'wise_email' => $user->wise->wise_email,
                'currency' => $user->wise->currency,
                'is_connected' => true
            ]
        ], 200);
    }

    public function wiseRemove(){

        $user = auth()->user();
        if ($user->wise) {
            $user->wise->delete();
        }

        return response()->json([
            'message' => "Wise Account Removed successfully",
        ], 200);
    }

    public function setDefaultWithdrawMethod(Request $request){

        $validated = $request->validate([
            'method_type' => 'required|in:bank,stripe,wise',
            'method_id' => 'required|integer',
        ]);

        $userId = auth()->user()->id;
        $methodType = $validated['method_type'];
        $methodId = $validated['method_id'];

        $validMethod = match ($methodType) {
            'bank' => BankAccount::where('id', $methodId)->exists(),
            'stripe' => ConnectStripe::where('id', $methodId)->exists(),
            'wise' => Wise::where('id', $methodId)->exists(),
            default => false,
        };

        if (!$validMethod) {
            return response()->json(['error' => 'Invalid payment method.'], 400);
        }
        // DefaultWithdrawMethod::where('user_id', $userId)->delete();

        DefaultWithdrawMethod::updateOrCreate(['user_id' => $userId],[
            // 'user_id' => $userId,
            'payable_type' => match ($methodType) {
                'bank' => BankAccount::class,
                'stripe' => ConnectStripe::class,
                'wise' => Wise::class,
            },
            'payable_id' => $methodId,
            'type' => $methodType,
        ]);
        return response()->json(['message' => 'Default payment method updated successfully.']);
    }

    public function getDefaultWithdrawMethod(){
        $user = auth()->user();
        $defaultMethod = $user->defaultWithdrawMethod;
        if ($defaultMethod) {
            return response()->json([
                'message' => 'Default withdraw method details',
                "data" => [
                    'details' => $defaultMethod->payable,
                    'type' => $defaultMethod->type
                ]
            ]);
        }
        else{
            return response()->json([
                'message' => 'No default method set yet'
            ]);
        }
    }
}
