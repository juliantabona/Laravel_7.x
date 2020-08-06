<?php

namespace App;

use App\Traits\CommonTraits;
use App\Traits\PaymentMethodTraits;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use PaymentMethodTraits, CommonTraits;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $casts = [
        'active' => 'boolean',              //  Return the following 1/0 as true/false
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'type', 'group', 'description', 'active'
    ];

    /*
     *  Returns the list of location that use this payment method
     */
    public function location()
    {
        return $this->belongsToMany('App\Location');
    }

    /** ATTRIBUTES
     * 
     *  Note that the "resource_type" is defined within CommonTraits
     * 
     */
    protected $appends = [
        'resource_type',
    ];

    public function setActiveAttribute($value)
    {
        $this->attributes['active'] = ( ($value == 'true' || $value === '1') ? 1 : 0);
    }

}
