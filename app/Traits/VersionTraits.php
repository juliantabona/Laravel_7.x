<?php

namespace App\Traits;

//  Resources
use App\Http\Resources\Version as VersionResource;
use App\Http\Resources\Versions as VersionsResource;

trait VersionTraits
{
    /*  convertToApiFormat() method:
     *
     *  Converts to the appropriate Api Response Format
     *
     */
    public function convertToApiFormat($versions = null)
    {
        if( $versions ){
                
            //  Transform the versions
            return new VersionsResource($versions);

        }else{
            
            //  Transform the version
            return new VersionResource($this);

        }
    }
}
