<?php

namespace App;

use DB;
use App\Traits\CommonTraits;
use App\Traits\ProjectTraits;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use ProjectTraits, CommonTraits;
    
    protected $with = ['shortCode', 'activeVersion'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $casts = [
        'online' => 'boolean',  //  Return the following 1/0 as true/false
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'online', 'offline_message', 'active_version_id', 'user_id'
    ];

    /*
     *  Returns the user that created this project
     */
    public function owner()
    {
        return $this->belongsTo('App\User', 'user_id');
    }

    /*
     *  Returns the user that have been assigned to this project
     */
    public function users()
    {
        return $this->belongsToMany('App\User')->withPivot('type');
    }

    /*
     *  Returns ussd short code details of this project
     */
    public function shortCode()
    {
        return $this->hasOne('App\ShortCode', 'project_id');
    }

    /*
     *  Returns versions of this project
     */
    public function versions()
    {
        return $this->hasMany('App\Version', 'project_id');
    }

    /*
     *  Returns the active version of this project
     */
    public function activeVersion()
    {
        return $this->belongsTo('App\Version', 'active_version_id');
    }

    /** ATTRIBUTES
     * 
     *  Note that the "resource_type" is defined within CommonTraits
     * 
     */
    protected $appends = [
        'resource_type',
    ];

    public function setOnlineAttribute($value)
    {
        $this->attributes['online'] = ( ($value == 'true' || $value === '1') ? 1 : 0);
    }

    //  ON DELETE EVENT
    public static function boot()
    {
        parent::boot();

        // before delete() method call this
        static::deleting(function ($project) {

            //  Delete all versions
            $project->versions()->delete();

            //  Delete short code
            $project->shortCode()->delete();

            //  Delete all records of users being assigned to this project
            DB::table('project_user')->where(['project_id' => $project->id])->delete();

            // do the rest of the cleanup...
        });
    }

}
