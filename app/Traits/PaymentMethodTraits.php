<?php

namespace App\Traits;

use Illuminate\Validation\ValidationException;
use App\Http\Resources\PaymentMethod as PaymentMethodResource;
use App\Http\Resources\PaymentMethods as PaymentMethodsResource;

trait PaymentMethodTraits
{
    /*  convertToApiFormat() method:
     *
     *  Converts to the appropriate Api Response Format
     *
     */
    public function convertToApiFormat($locations = null)
    {
        if( $locations ){
                
            //  Transform the multiple instances
            return new PaymentMethodsResource($locations);

        }else{
            
            //  Transform the single instance
            return new PaymentMethodResource($this);

        }
    }
    
}
