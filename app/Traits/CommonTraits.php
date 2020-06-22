<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait CommonTraits
{
    /*
     *  Returns the resource type
     */
    public function getResourceTypeAttribute()
    {
        return strtolower(Str::snake(class_basename($this)));
    }
}
