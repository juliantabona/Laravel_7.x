<?php

namespace App;

use App\Traits\CommonTraits;
use App\Traits\UssdSessionTraits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UssdSession extends Model
{
    use UssdSessionTraits;
    use CommonTraits;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $casts = [
        'user_response_durations' => 'array',
        'session_execution_times' => 'array',
        'estimated_record_sizes' => 'array',
        'allow_timeout' => 'boolean',       //  Return the following 1/0 as true/false
        'reply_records' => 'array',
        'fatal_error' => 'boolean',
        'metadata' => 'array',
        'test' => 'boolean',                //  Return the following 1/0 as true/false
        'logs' => 'array',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'timeout_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        /*  Session Details  */
        'session_id', 'service_code', 'type', 'msisdn', 'request_type',
        'text', 'reply_records', 'logs', 'test', 'fatal_error', 'fatal_error_msg', 
        'allow_timeout', 'timeout_at', 'estimated_record_sizes', 'total_session_duration', 
        'user_response_durations', 'session_execution_times',

        /*  Meta Data  */
        'metadata',

        /*  Ownership Information  */
        'project_id', 'version_id'
    ];

    /**
     *  Returns the query with select columns excluded.
     */
    public function scopeExclude($query, $value = [])
    {
        $columns = array_merge($this->getFillable(), ['id', 'created_at', 'updated_at']);
        return $query->select(array_diff($columns, (array) $value));
    }

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
        if ($this->allow_timeout) {
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

    public function getStatusAttribute()
    {
        //  If the test session failed
        if( $this->fatal_error ){

            $name = 'Fail';
            $desc = 'The session failed due to an error';

        //  If the session allows timeouts and the session has timed-out
        }elseif( (!$this->test && $this->has_timed_out) ||
                    ( $this->test && $this->allow_timeout && $this->has_timed_out) ){

            $name = 'Timeout';
            $desc = 'The session has timed out';

        }elseif( $this->request_type == '3' ){

            $name = 'Closed';
            $desc = 'The session has been closed';

        }elseif( $this->request_type == '2' || $this->request_type == '1' ){

            $name = 'Active';
            $desc = 'The session is still active';

        }

        $response =  [
            'name' => $name,
            'description' => $desc,

        ];

        if($this->fatal_error){

            $response['error_msg'] = $this->fatal_error_msg;

        }

        return $response;
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
        $this->attributes['test'] = (($value == 'true' || $value === '1') ? 1 : 0);
    }

    public function setAllowTimeoutAttribute($value)
    {
        $this->attributes['allow_timeout'] = (($value == 'true' || $value === '1') ? 1 : 0);
    }

    public function setFatalErrorAttribute($value)
    {
        $this->attributes['fatal_error'] = (($value == 'true' || $value === '1') ? 1 : 0);
    }
}
