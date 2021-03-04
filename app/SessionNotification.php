<?php

namespace App;

use App\Traits\CommonTraits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SessionNotification extends Model
{
    use CommonTraits;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $casts = [
        'test' => 'boolean',
        'showing_notification' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

        /*  Session Notification Details  */
        'msisdn', 'test', 'type', 'name', 'message', 'continue_text', 'showing_notification',

        /*  Meta Data  */
        'metadata',

        /*  Ownership Information  */
        'project_id'

    ];

    /* ATTRIBUTES */

    protected $appends = [
        'resource_type',
    ];

    public function setTestAttribute($value)
    {
        $this->attributes['test'] = (($value == 'true' || $value === '1') ? 1 : 0);
    }

}
