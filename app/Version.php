<?php

namespace App;

use App\Traits\CommonTraits;
use App\Traits\VersionTraits;
use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    use VersionTraits, CommonTraits;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $casts = [
        'builder' => 'array',
        'number' => 'decimal:1',    //  1 represents the decimal precision to return e.g 1.0, 2.0, e.t.c
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'number', 'description', 'builder', 'project_id'
    ];

    /*
     *  Returns the project of this version
     */
    public function project()
    {
        return $this->belongsTo('App\Project', 'project_id');
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
