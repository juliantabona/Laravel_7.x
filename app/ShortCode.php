<?php

namespace App;

use App\Traits\CommonTraits;
use App\Traits\ShortCodeTraits;
use Illuminate\Database\Eloquent\Model;

class ShortCode extends Model
{
    use ShortCodeTraits, CommonTraits;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shared_code', 'dedicated_code', 'country', 'project_id'
    ];

    /*
     *  Returns the project of this shortcode
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
