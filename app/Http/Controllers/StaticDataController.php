<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaticDataController extends Controller
{
    public function getProductTypes(): JsonResponse {
        $productTypes = [
            [
                'type' => 'coaching',
                'title' => 'Coaching Call',
                'description' => 'Book Discovery Calls, Paid Coaching',
                'image_url' => asset('product/coaching.svg'),
                'is_hot_deal' => false
            ],
            [
                'type' => 'group-call',
                'title' => 'Live Event',
                'description' => 'Host exclusive coaching sessions or events with multiple customers',
                'image_url' => asset('product/group-call.svg'),
                'is_hot_deal' => false
            ],
            [
                'type' => 'digital',
                'title' => 'Digital Download',
                'description' => 'PDFs, Guides, Templates, Exclusive Content, eBooks etc.',
                'image_url' => asset('product/digital.svg'),
                'is_hot_deal' => false
            ],
            [
                'type' => 'community',
                'title' => 'Community Hub',
                'description' => 'Host a free or paid community/social group link',
                'image_url' => asset('product/community.svg'),
                'is_hot_deal' => false
            ],
            [
                'type' => 'service',
                'title' => 'Service',
                'description' => 'Sell your service and get paid easily',
                'image_url' => asset('product/service.svg'),
                'is_hot_deal' => false
            ],
            [
                'type' => 'affiliate',
                'title' => 'E-commerce Affiliate',
                'description' => 'Add e-commerce affiliate links or share any links',
                'image_url' => asset('product/affiliate.svg'),
                'is_hot_deal' => false
            ],
            [
                'type' => 'flexpoint-affiliate',
                'title' => 'Flexpoint Affiliate Link',
                'description' => 'Refer friend and receive 20% of their Subscription fee each month!',
                'image_url' => asset('product/flexpoint-affiliate.svg'),
                'is_hot_deal' => true
            ]
        ];

        return response()->json([
            'message' => 'All Product Types',
            'data' => $productTypes
        ]);
    }
}
