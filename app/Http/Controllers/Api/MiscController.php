<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MiscController extends Controller
{
    private $user;

    public function __construct(Request $request)
    {
        //  Get the authenticated user
        $this->user = auth('api')->user();
    }

    public function getPaymentMehods()
    {
        //  Get the payment methods
        $payment_methods = \App\PaymentMethod::all();

        //  Check if the payment methods exists
        if ($payment_methods) {
                
            //  Return an API Readable Format of the PaymentMethod Instance
            return ( new \App\PaymentMethod() )->convertToApiFormat($payment_methods);
            
        } else {

            //  Not Found
            return help_resource_not_fonud();

        }
    }
    
}
