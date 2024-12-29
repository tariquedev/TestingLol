<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\BookingSlot;
use App\Models\Customer;
use App\Models\CustomerDynamicField;
use App\Models\Product;
use App\Models\TimeSlot;
use App\Traits\MeetingLinkGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stripe\Charge;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Illuminate\Support\Str;
use App\Traits\VisitorTimezoneTrait;
use Carbon\Carbon;
use Stripe\SetupIntent;

class AppointmentController extends Controller
{
    use MeetingLinkGenerator, VisitorTimezoneTrait;

    public function getPaymentIntent(Request $request)
    {
        Stripe::setApiKey(env("STRIPE_SECRET"));
        $setupIntent = SetupIntent::create([]);
        return response()->json([
            'message'   => 'Client Secret',
            'data'      => [
                'client_secret'  => $setupIntent->client_secret
            ]
        ]);
    }

    public function storeAppointment(Request $request){

        Stripe::setApiKey(env('STRIPE_SECRET'));
        $rules = [
            'name' => 'required|string',
            'email' => 'required|email',
            'payment_method_id' => 'required',
            'product_id' => 'required',
            // 'start_at' => 'required',
            // 'end_at' => 'required',
            // 'date' => 'required|date',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $visitorTimezone = $request->visitor_timezone ?? $this->getVisitorTimezone($request->ipAddress);

        $product = Product::find($request->product_id);

        $details = [
            'product_id' => $product->id,
            'title' => 'Meeting for ' . $product->title,
            'date' => $request->date,
            'start_time' => $request->start_at,
            'end_time' => $request->end_at,
            'timezone' => $product->productable->timezone,
            'visitor_timezone' => $visitorTimezone,
            'duration' => $product->productable->duration,
            'amount' => $request->amount,
            'currency' => $product->user->currency,
            'user_id' => $product->user->id ?? 5,
            'payment_method' => $request->payment_method_id,
            'attendee_email' => $request->email,
            'attendee_name' => $request->name,
        ];
        if ($request->type && $request->type != null) {
            $bookingSlot = BookingSlot::firstOrCreate([
                'product_id' => $product->id,
                'creator_id' => $product->user_id,
                'date' => $request->date,
                'start_at' => $request->start_at,
                'end_at' => $request->end_at,
            ]);

            $meetingData = $this->createMeeting($request->type, $details);
            $bookingSlot->meeting_link =  $meetingData['link'];
            $bookingSlot->event_id =  $meetingData['event_id'];
            $bookingSlot->save();
        }
        $customer = Customer::updateOrCreate([
            'email' => $request->email,
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        if (!$customer->stripe_id) {
            $customer->createAsStripeCustomer();
        }

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $request->amount * 100, // Convert to cents
                'currency' => $request->currency,
                'customer' => $customer->stripe_id,
                'payment_method' => $request->payment_method_id,
                'off_session' => true,
                'confirm' => true,
            ]);

            // return $chargeId = $paymentIntent;
            $appointment = Appointment::create([
                'customer_id' => $customer->id,
                'product_id' => $request->product_id,
                'booking_slot_id' => $bookingSlot->id ?? null,
                'payment_status' => 'completed',
                'charge_id' => $paymentIntent->latest_charge,
                'amount' => $request->amount,
                'currency_symbol' => $product->user->currency_symbol,
                'currency' => $product->user->currency,
            ]);
            if ($request->has('dynamic_fields')) {
                foreach ($request->dynamic_fields as $field) {
                    CustomerDynamicField::create([
                        'appointment_id' => $appointment->id,
                        'field_name' => $field['name'],
                        'field_value' => $field['value'],
                    ]);
                }
            }
            return response()->json([
                'message' => 'Appointment created successfully.',
                'appointment' => [
                    'customer' => $appointment->customer,
                    'schedule' => $appointment->bookingSlot,
                    'product' => $appointment->product,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getAppointments(){
        $userId = auth()->user()->id;

        $appointments = Appointment::with(['product:id,title', 'customer:id,email,name', 'bookingSlot:id,date,start_at,end_at'])
            ->whereHas('product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->get()
            ->groupBy(function ($appointment) {
                return $appointment->product_id . '_' . $appointment->booking_slot_id;
            })->map(function ($groupedAppointments) {
                $firstAppointment = $groupedAppointments->first();
                return [
                    'slot_id' => $firstAppointment->bookingSlot->id ?? 'N/A',
                    'product' => $firstAppointment->product->title ?? 'N/A',
                    'attendees' => $groupedAppointments->count() > 1
                        ? $groupedAppointments->count() . ' Attendees'
                        : $firstAppointment->customer->email ?? 'N/A',
                    'date' => $firstAppointment->bookingSlot->date ?? 'N/A',
                    'slot' => ($firstAppointment->bookingSlot->start_at ?? '') . ' - ' . ($firstAppointment->bookingSlot->end_at ?? ''),
                ];
            })->values();


        if (!$appointments) {
            return response()->json([
                'message' => 'No Appointments Available',
            ]);
        }
        return response()->json([
            'message' => 'All Appointments',
            'data' => [
                'appointments' => $appointments,
            ]
        ]);
    }

    public function singleAppointment($slot_id){
        $userId = auth()->user()->id;

        $bookingSlot = BookingSlot::with([
            'customers',
            'customers.appointments.dynamicFields',
            'product',
        ])
        ->where('id', $slot_id)
        ->where('creator_id', $userId)
        ->first();
        if (!$bookingSlot) {
            return response()->json([
                'message' => 'No data Available',
            ]);
        }
        $slotDetails = [
            'date' => $bookingSlot->date,
            'start_time' => $bookingSlot->start_at,
            'end_time' => $bookingSlot->end_at,
            'meeting_link' => $bookingSlot->meeting_link,
            'attendees' => $bookingSlot->customers->count() .' '. Str::plural('Attendee', $bookingSlot->customers->count()),
            'product_title' => $bookingSlot->product->title ?? 'N/A',
            'customers' => $bookingSlot->customers->map(function ($customer) {
                return [
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'dynamic_info' => $customer->appointments->flatMap(function ($appointment) {
                        return $appointment->dynamicFields->map(function ($field) {
                            return [
                                'field_name' => $field->field_name,
                                'field_value' => $field->field_value,
                            ];
                        });
                    })->toArray(),
                ];
            }),
        ];

        return response()->json([
            'message' => 'Get appointment details',
            'data' => [
                'appointments' => $slotDetails,
            ]
        ]);
    }

    public function myOrders(Request $request){
        $userId = auth()->user()->id;

        $timezone = config('app.timezone');

        $startDate = $request->start_date
        ? Carbon::parse($request->start_date, $timezone)->startOfDay()->format('Y-m-d H:i:s')
        : now($timezone)->subDays(30)->startOfDay()->format('Y-m-d H:i:s');

        $endDate = $request->end_date
        ? Carbon::parse($request->end_date, $timezone)->endOfDay()->format('Y-m-d H:i:s')
        : now($timezone)->endOfDay()->format('Y-m-d H:i:s');
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);

        $orders = Appointment::with(['product:id,title', 'customer:id,email,name'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Orders fetched successfully',
            'data' => $orders->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'title' => $order->product->title ?? null,
                    'name' => $order->customer->name ?? null,
                    'email' => $order->customer->email ?? null,
                    'price' => $order->currency_symbol . $order->amount ?? null,
                    'order_date' => $order->created_at,
                ];
            }),
            'pagination' => [
                'total_items' => $orders->total(),
                'items_per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'total_pages' => $orders->lastPage(),
            ],
        ]);
    }
    public function myCustomers(Request $request){
        $userId = auth()->user()->id;

        $timezone = config('app.timezone'); // Get the app's configured timezone

        $startDate = $request->start_date
        ? Carbon::parse($request->start_date, $timezone)->startOfDay()->format('Y-m-d H:i:s')
        : now($timezone)->subDays(30)->startOfDay()->format('Y-m-d H:i:s');

        $endDate = $request->end_date
        ? Carbon::parse($request->end_date, $timezone)->endOfDay()->format('Y-m-d H:i:s')
        : now($timezone)->endOfDay()->format('Y-m-d H:i:s');
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);

        $orders = Appointment::selectRaw(
            'customer_id, COUNT(*) as total_purchases, SUM(amount) as total_spent'
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->groupBy('customer_id')
            ->with(['customer:id,email,name']) // Load customer details
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Customers fetched successfully',
            'data' => $orders->map(function ($order) {
                return [
                    'customer_id' => $order->customer->id,
                    'customer_name' => $order->customer->name ?? 'Unknown',
                    'customer_email' => $order->customer->email ?? 'Unknown',
                    'total_purchases' => $order->total_purchases,
                    'total_spent' => $order->total_spent,
                        ];
            }),
            'pagination' => [
                'total_items' => $orders->total(),
                'items_per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'total_pages' => $orders->lastPage(),
            ],
        ]);
    }

}
