<?php

namespace App;

use DB;
use App\Traits\CommonTraits;
use App\Traits\UserAccountTraits;
use Illuminate\Database\Eloquent\Model;

class UserAccount extends Model
{
    use UserAccountTraits, CommonTraits;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $casts = [
        'test' => 'boolean',  //  Return the following 1/0 as true/false
        'metadata' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'mobile_number', 'test', 'metadata', 'user_id', 'project_id'
    ];

    /*
     *  Scope:
     *  Return test accounts
     */
    public function scopeTestAccount($query)
    {
        return $query->where('test', 1);
    }

    /*
     *  Scope:
     *  Return real accounts
     */
    public function scopeRealAccount($query)
    {
        return $query->where('test', 0);
    }

    /*
     *  Returns the owning project
     */
    public function project()
    {
        return $this->belongsTo('App\Project', 'project_id');
    }

    public function setTestAttribute($value)
    {
        $this->attributes['test'] = ( ($value == 'true' || $value === '1') ? 1 : 0);
    }

    /** ATTRIBUTES
     * 
     *  Note that the "resource_type" is defined within CommonTraits
     * 
     */
    protected $appends = [
        'resource_type',
    ];

}
