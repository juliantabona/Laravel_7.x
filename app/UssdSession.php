<?php

namespace App;

use Illuminate\Support\Str;
use App\Traits\CommonTraits;
use App\Traits\UssdSessionTraits;
use Illuminate\Database\Eloquent\Model;

class UssdSession extends Model
{
    use UssdSessionTraits, CommonTraits;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $casts = [
        'test' => 'boolean',  //  Return the following 1/0 as true/false
        'allow_timeout' => 'boolean', //  Return the following 1/0 as true/false
        'reply_records' => 'array',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'timeout_at'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

        /*  Session Details  */
        'session_id', 'service_code', 'type', 'msisdn', 'request_type', 
        'text', 'reply_records', 'test', 'status', 'allow_timeout', 'timeout_at',

        /*  Meta Data  */
        'metadata',

        /*  Ownership Information  */
        'owner_id', 'owner_type'

    ];

    /*
     *  Returns the owner of the ussd session
     */
    public function owner()
    {
        return $this->morphTo();
    }

    /* ATTRIBUTES */

    protected $appends = [
        'status', 'has_timed_out', 'resource_type',
    ];

    /*
     *  Returns true or false if the session has timed out or expired
     */
    public function gethasTimedOutAttribute()
    {
        //  If the test uses a time limit
        if( $this->allow_timeout ){

            //  Get the session timeout date and time (as a timestamp)
            $timeout_at = $this->timeout_at->getTimestamp();

            //  Get the current date and time (as a timestamp)
            $now = \Carbon\Carbon::now()->getTimestamp();

            //  Compare to see if the session timeout date and time has been exceeded
            $result = ($now > $timeout_at) ? true : false;

            //  Return final result
            return $result;

        }

        //  Otherwise return false
        return false;
    }

    public function getStatusAttribute($status)
    {
        switch ($status) {
            case '0':
                $status_name = 'Incomplete';
                $status_description = 'The session was not completed';
                break;
            case '1':
                $status_name = 'Completed';
                $status_description = 'The session was completed successfully';
                break;
            case '2':
                $status_name = 'Failed';
                $status_description = 'The session failed. A problem was encountered';
                break;
            default:
                $status_name = 'Unknown';
                $status_description = 'Status is unknown';
        }

        return [
            'name' => $status_name,
            'description' => $status_description,
        ];
    }

    /*
     *  Returns the resource type
     */
    public function getResourceTypeAttribute()
    {
        return strtolower(Str::snake(class_basename($this)));
    }

    public function setTestAttribute($value)
    {
        $this->attributes['test'] = ( ($value == 'true' || $value === '1') ? 1 : 0);
    }

    public function setAllowTimeoutAttribute($value)
    {
        $this->attributes['allow_timeout'] = ( ($value == 'true' || $value === '1') ? 1 : 0);
    }

}
