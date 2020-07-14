<?php

namespace App\Traits;

//  Resources
use App\Http\Resources\ShortCode as ShortCodeResource;

trait ShortCodeTraits
{
    private $shortCode;

    /*  convertToApiFormat() method:
     *
     *  Converts to the appropriate Api Response Format
     *
     */
    public function convertToApiFormat()
    {
        //  Transform the single instance
        return new ShortCodeResource($this);
    }

    /*  This method creates a new shortCode
     */
    public function initiateCreate( $request )
    {
        //  Set the template
        $template = [
            'project_id' => $request->input('project_id'),
            'country' => $request->input('country') ?? 'Botswana',
            'dedicated_code' => $request->input('dedicated_code') ?? null
        ];

        try {
            /*
             *  Create new a shortCode, then retrieve a fresh instance
             */
            $this->shortCode = $this->create($template);

            //  If the short code was created successfully
            if ($this->shortCode) {
            
                /** Generate the shared short code 
                 * 
                 *  Get the shared short code e.g *321#
                 *  Remove the ending # symbol e.g *321
                 *  Add the astrix symbol id e.g *321*
                 *  Add the shortcode id e.g *321*1
                 *  Add the # symbol  e.g *321*1#
                 * 
                 */
                $shared_short_code = str_replace('#', '', $request->input('shared_short_code'));
                $unique_shared_code = $shared_short_code.'*'.$this->shortCode->id.'#';

                //  Update the shared short code
                $this->shortCode->update([
                    'shared_code' => $unique_shared_code
                ]);

                return $this->shortCode->fresh();

            }

        } catch (\Exception $e) {

            //  Return the error
            return oq_api_notify_error('Query Error', $e->getMessage(), 404);
            
        }
    }

}
