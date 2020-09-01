<?php

namespace App\Http\Controllers\Api;

use DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Ussd\Builder as UssdBuilder;

class UssdServiceController extends Controller
{
    public $msg;
    public $text;
    public $event;
    public $screen;
    public $msisdn;
    public $project;
    public $version;
    public $builder;
    public $display;
    public $screens;
    public $request;
    public $log = [];
    public $app_name;
    public $response;
    public $level = 1;
    public $test_mode;
    public $session_id;
    public $service_code;
    public $request_type;
    public $linked_screen;
    public $linked_display;
    public $display_actions;
    public $display_content;
    public $existing_session;
    public $user_account = null;
    public $chained_screens = [];
    public $pagination_index = 0;
    public $display_instructions;
    public $current_user_response;
    public $screen_repeats = false;
    public $ussd_service_code_type;
    public $navigation_step_number;
    public $navigation_request_type;
    public $dynamic_data_storage = [];
    public $navigation_target_screen_id;
    public $allow_dynamic_content_highlighting = true;
    public $default_no_select_options_message = 'No options available';  
    public $default_technical_difficulties_message = 'Sorry, we are experiencing technical difficulties';
    public $default_incorrect_option_selected_message = 'You selected an incorrect option. Go back and try again';  

    public function __construct(Request $request)
    {
        //  Set the request
        $this->request = $request;
    }

    /** Start setting up the USSD configurations,
     *  session and build process.
     */
    public function setup()
    {
        /* Example Request (From USSD Gateway)
         *
         *  <ussd>
         *      <msisdn>M</msisdn>
         *      <sessionid>S</sessionid>
         *      <type>T</type>
         *      <msg>MSG</msg>
         *  </ussd>
         *
         *  Example Response (From Third Party Application)
         *
         *  <ussd>
         *      <type>T</type>
         *      <msg>MSG</msg>
         *      <premium>
         *          <cost>C</cost>
         *          <ref>R</ref>
         *      </premium>
         *  </ussd>
         */

        /* Parameters description:
         *
         * ------|--------------------|---------------------------------------------------------------------|
         * CODE  |   PARAMETER  NAME  |   DESCRIPTION                                                       |
         * ------|--------------------|---------------------------------------------------------------------|
         *   M   |   Msisdn           |   Msisdn of USSD subscriber e.g 26776570551                         |
         * ------|--------------------|---------------------------------------------------------------------|
         *   S   |   Session ID       |   Session id Unique session id number                               |
         * ------|--------------------|---------------------------------------------------------------------|
         *   T   |   Request type     |   Request type Description in the next table                        |
         * ------|--------------------|---------------------------------------------------------------------|
         *   MSG |   Message          |   USSD message to be delivered to the subscriber                    |
         * ------|--------------------|---------------------------------------------------------------------|
         *   C   |   Cost             |   Cost Extra cost to be charged to the user                         |
         * ------|--------------------|---------------------------------------------------------------------|
         *   R   |   Cost reference   |   Cost reference Unique value as charge reference                   |
         * ------|--------------------|---------------------------------------------------------------------|
         */

        /* Message type codes:
         *
         * ------|----------|-------------------------|-----------------------------------------------------|
         * CODE  |   VALUE  |     VALUE SENT BY       |   DESCRIPTION                                       |
         * ------|----------|-------------------------|-----------------------------------------------------|
         *       |          | UMB | Service Provider  |                                                     |
         * ------|----------|-----|-------------------|-----------------------------------------------------|
         *   1   | REQUEST  |  x  |                   |  New USSD request                                   |
         * ------|----------|-----|-------------------|-----------------------------------------------------|
         *   2   | RESPONSE |  x  |        x          |  Response in already existing session               |
         * ------|----------|-----|-------------------|-----------------------------------------------------|
         *   3   | RELEASE  |  x  |        x          |  End of session.                                    |
         * ------|----------|-----|-------------------|-----------------------------------------------------|
         *   4   | TIMEOUT  |  x  |                   |  Session timeout – USSD subscriber failed to        |
         *       |          |     |                   |  provide answer within time limit                   |
         * ------|----------|-----|-------------------|-----------------------------------------------------|
         *   5   | REDIRECT |     |        x          |  Redirect the request to another service provider.  |
         *       |          |     |                   |  MSG field contains USSD code to redirect to.       |
         * ------|----------|-----|-------------------|-----------------------------------------------------|
         *  10   | CHARGE   |  x  |                   |  Premium rate charge failed. MSG part contains      |
         *       |          |     |                   |  error description                                  |
         * ------|----------|-----|-------------------|-----------------------------------------------------|
         */
        
        /*********************/
        /*  HANDLE REQUEST   */
        /*********************/

        //  Store the Ussd Gateway values
        $this->storeUssdGatewayValues();

        //  Handle the Ussd Session request
        $this->handleSessionRequest();

        //  Handle the Ussd Session response
        return $this->handleSessionResponse();
    }
    
    /** Store the USSD Gateway values required to perform the
     *  service. This includes the USSD message, phone number,
     *  session id, request type e.t.c
     */
    public function storeUssdGatewayValues()
    {
        //  Get the "Message"
        $this->msg = $this->request->get('msg');

        //  Get the "Msisdn"
        $this->msisdn = $this->request->get('msisdn');

        //  Get the "Session ID"
        $this->session_id = $this->request->get('sessionId');

        //  Get the "Request Type"
        $this->request_type = $this->request->get('requestType');

        //  Get the "TEST MODE" status
        $this->test_mode = ($this->request->get('testMode') == 'true' || $this->request->get('testMode') == '1') ? true : false;
    }

    /** Determine if this is a new or existing session, then execute
     *  the relevant methods to further handle the session
     */
    public function handleSessionRequest()
    {
        /** If the "Request Type" is equal to "1"
         *  This means a new session must be
         *  started
         */
        if ($this->request_type == '1') {

            //  Handle a new session
            $this->response = $this->handleNewSession();

        /** If the "Request Type" is equal to "2"
         *  This means a previous session must be
         *  continued
         */
        } elseif ($this->request_type == '2') {

            //  Handle existing session
            $this->response = $this->handleExistingSession();
        }
    }

    /** Start a brand new USSD session
     */
    public function handleNewSession()
    {
        /** When the "Request Type" is "1", the "Sevice Code" comes embedded
         *  within the "Message" value. When the "Request Type" is "2" the
         *  "Message" contains data from the user.
         */

        //  Get the "Sevice Code" from the "Message" value
        $this->getServiceCodeFromMessage();

        //  Get the USSD Builder for the given "Service Code"
        $this->getUssdBuilder();

        //  Get the text which represents responses from the user
        $this->text = $this->msg;

        //  If the session id was not provided
        if (is_null($this->session_id)) {
            //  Generate a unique session id
            $unique_session_id = uniqid('test_').'_'.(\Carbon\Carbon::now())->getTimestamp();

            //  Update the current session id with the generated session id
            $this->session_id = $unique_session_id;
        }

        //  Determine if we allow timeouts
        $allow_timeout = $this->builder['simulator']['settings']['allow_timeouts'];

        //  Get the timeout limit in seconds e.g "120" to mean "timeout after 120 seconds"
        $timeout_limit_in_seconds = $this->getTimeoutLimitInSeconds();

        //  Create new session
        $session = DB::table('ussd_sessions')->insert(
            [
                'text' => $this->text,
                'type' => $this->ussd_service_code_type,
                'msisdn' => $this->msisdn,
                'session_id' => $this->session_id,
                'allow_timeout' => $allow_timeout,
                'service_code' => $this->service_code,
                'request_type' => $this->request_type,
                'test' => $this->test_mode,
                'created_at' => (\Carbon\Carbon::now())->format('Y-m-d H:i:s'),
                'updated_at' => (\Carbon\Carbon::now())->format('Y-m-d H:i:s'),
                'timeout_at' => (\Carbon\Carbon::now())->addSeconds($timeout_limit_in_seconds)->format('Y-m-d H:i:s'),
            ]
        );

        //  Handle the current session
        return $this->handleSession();
    }

    /** Get the USSD service code embedded within the USSD message
     */
    public function getServiceCodeFromMessage()
    {

        /** Get the "Service Code" embbeded within the "Message" value 
         *  
         *  e.g *321*3*4*5#
         * 
         *  Depending on the scenerio the first value may be a Shared Ussd
         *  Code or a Dedicated Ussd Code.
         * 
         *  -------------------
         *  If this is a Dedicated Ussd Code:
         * 
         *  e.g *150*3*4*5#
         * 
         *  We need to extract the first value "150" to create "*150#"
         *  which will be used as the "Service Code". The rest of the
         *  value i.e "3*4*5" will be used as the "Message" value.
         * 
         *  Therefore
         * 
         *  $this->service_code = *150#
         * 
         *  $this->msg = 3*4*5
         * 
         *  -------------------
         *  If this is a Shared Ussd Code: 
         *  
         *  e.g *321*3*4*5# or 
         *  e.g *321*4*4*5# or 
         *  e.g *321*5*4*5#
         * 
         *  We need to extract the first value "321" and the second value
         *  to create "*321*3#" or "*321*4#" or "*321*5#" to be used as 
         *  the "Service Code". The rest of the value i.e "4*5" will 
         *  be used as the "Message" value.
         * 
         *  Therefore
         * 
         *  $this->service_code = *321*3#
         * 
         *  $this->msg = 4*5
         * 
         *  ---------------
         *  STEPS
         *  ---------------
         * 
         *  First we need to replace the "#" to "*"
         *  
         *  *321*3*4*5# becomes *321*3*4*5*
         * 
         *  Then we explode into an array using the "*" symbol
         * 
         *  $responses = [0=>"", 1=>"321", 2=>"3", 3=>"4", 4=>"5", 5=>""]
         * 
         *  Filter to remove any empty values
         * 
         *  $responses = [1=>"321", 2=>"3", 3=>"4", 4=>"5"]
         * 
         *  Use array_values to re-number the array keys properly
         * 
         *  $responses = [0=>"321", 1=>"3", 2=>"4", 3=>"5"]
         * 
         *  Use the first value as the service code (if Dedicated Ussd Code) or
         *  the first and second value as the service code (if Shared Ussd Code)
         * 
         *  $this->service_code = *150# or *321# or *321*3#
         * 
         *  To do this we use array_shift(). array_shift() shifts the first value of the array off 
         *  and returns it, shortening the array by one element and moving everything down. All 
         *  numerical array keys will be modified to start counting from zero while literal 
         *  keys won't be affected.
         * 
         *  Use the rest of the values as the message. We can do this using the implode() method,
         *  which joins the values using the "*" symbol
         * 
         *  $this->msg = 3*4*5
         */
        
        //  Replace "#" to "*"
        $message = str_replace('#', '*', $this->msg);

        //  Explode into an array using the "*" symbol
        $values = explode('*', $message);

        //  Remove empty values and reset the numerical array keys 
        $values = array_values(array_filter($values, function ($value) {
            return ($value !== '');
        }));

        //  Get the current Ussd Service Code e.g 321
        $current_service_code_number = $values[0];

        //  Remove the first value and assign it to the "$first_number" variable
        $first_number = array_shift($values);

        //  Get the Shared Service Codes
        $shared_short_codes = \App\SharedShortCode::all();

        //  Handle the Shared Service Code
        for ($x=0; $x < count( $shared_short_codes ); $x++) {
            
            //  Get the Shared Service Code e.g *321#, *432#, *543#
            $shared_service_code =  $shared_short_codes[$x]['code'];

            //  Remove the "*" and "#" symbol from the Shared Service Code of the Main Ussd Service Code e.g from "*321#" to "321"
            $shared_service_code_number = str_replace(['*', '#'], '', $shared_service_code);

            //  If the current Ussd Service Code is the same as the Shared Service Code
            if( $current_service_code_number == $shared_service_code_number ){

                //  Remove the first value and assign it to the "$second_number" variable
                $second_number = array_shift($values);
        
                //  Use the first and second value as the Ussd Service Code e.g *321*45#
                $this->service_code = '*'.$first_number.'*'.$second_number.'#';

                //  Indicate that this is a Shared Service Code
                $this->ussd_service_code_type = 'shared';

                //  Break out of the loop
                break 1;
            }

        }
        
        //  If the current Ussd Service Code is not a Shared Service Code (i.e This is a dedicated Service Code)
        if( !$this->ussd_service_code_type ){
    
            //  Use the first value as the service code e.g *150#
            $this->service_code = '*'.$first_number.'#';

            //  Indicate that this is a Dedicated Service Code
            $this->ussd_service_code_type = 'dedicated';

        }
    
        //  Use the rest of the values as the message e.g 3*4*5
        $this->msg = implode('*', $values);
        
    }

    /** Use the USSD Service Code to set the project,
     *  version and builder required
     */
    public function getUssdBuilder()
    {
        //  If we don't have the builder
        if (empty($this->builder)) {

            $ussd_service_code = null;

            //  If this is a Shared Ussd Service Code
            if( $this->ussd_service_code_type == 'shared' ){
            
                //  Get the Ussd Service Code Record from the database
                $ussd_service_code = \App\ShortCode::where('shared_code', $this->service_code)->first();

            //  If this is a Dedicated Ussd Service Code
            }elseif( $this->ussd_service_code_type == 'dedicated' ){
            
                //  Get the Ussd Service Code Record from the database
                $ussd_service_code = \App\ShortCode::where('dedicated_code', $this->service_code)->first();

            }
            
            //  If we have a matching Ussd Service Code
            if ($ussd_service_code) {

                //  Get the owning resource (i.e owning Project resource)
                $this->project = $ussd_service_code->project;
                
                //  If the project exists
                if ($this->project) {

                    //  If the project has an active version assigned
                    if( $this->project->active_version_id ){

                        //  Get the version
                        $this->version = \App\Version::find($this->project->active_version_id);
                        
                        //  If the version exists
                        if( $this->version ){

                            //  Get the version builder
                            $this->builder = $this->version->builder;

                        }
                    }

                }

            }

        }else{

            //  Return the current builder
            return $this->builder;

        }
    }

    /** Return the builder's allowed timeout limit in seconds
     */
    public function getTimeoutLimitInSeconds()
    {   
        //  Get the timeout limit in seconds e.g "120" to mean "timeout after 120 seconds"
        return $this->builder['simulator']['settings']['timeout_limit_in_seconds'];
    }

    /** Determine if we are on test mode or live mode, then execute
     *  the relevant approach to return the build response
     */
    public function handleSessionResponse()
    {
        //  If the "Request Type" is "2"
        if ($this->request_type == '2') {

            //  Continue session

        //  If the "Request Type" is "3"
        } elseif ($this->request_type == '3') {

            //  Close session

        //  If the "Request Type" is "4"
        } elseif ($this->request_type == '4') {

            //  Timeout session

        //  If the "Request Type" is "5"
        } elseif ($this->request_type == '5') {

            //  Redirect session

        }

        //  If we are on test mode
        if( $this->test_mode ){

            //  Return the response payload as json
            return response($this->response)->header('Content-Type', 'application/json');


        //  If we are on live mode
        }else{

            //  Restructure the response payload for XML conversion
            $data = [
                'ussd' => [
                    'type' => $this->response['request_type'],
                    'msg' => $this->response['msg'],
                ],
            ];

            //  Return the response data as XML
            return response()->xml($data);

        }
    }

    /** Continue existing USSD session
     */
    public function handleExistingSession($buildResponse = true)
    {
        //  Get the existing session record from the database
        $this->existing_session = $this->getExistingSessionFromDatabase();

        //  Update the current session service code
        $this->service_code = $this->existing_session->service_code;

        //  Update the current sesion service code type
        $this->ussd_service_code_type = $this->existing_session->type;

        //  Get the USSD Builder for the given "Service Code"
        $this->getUssdBuilder();

        //  If we are on TEST MODE and the existing session has timed out
        if ($this->test_mode && $this->existing_session->has_timed_out) {

            //  Prepare for timeout
            $this->request_type = '4';

            //  Use the already exising text as the current text
            $this->text = $this->existing_session->text;

        } else {

            //  If the text value has not been provided
            if( is_null( $this->text ) ){

                /** If the user provided any "Message" value, then merge it with the existing text,
                 *  otherwise return the existing text alone. The "text" value represents responses
                 *  from the user.
                 * 
                 *  $this->msg:
                 * 
                 *  Represets text currently provided by the user in this session e.g "1" or "John"
                 * 
                 *  $this->existing_session->text: 
                 * 
                 *  Represets text previously provided by the user in this session. Each response is 
                 *  separated using the "*" symbol "e.g 1*2*3"
                 * 
                 *  $this->text
                 * 
                 *  Represets the current text and the previous text responses combined e.g
                 *  "1*2*3*John"
                 */
    
                //  If we don't have existing session text
                if (trim($this->existing_session->text) == '') {
                    //  Add the user message as the first response
                    $this->text = $this->msg;
                } else {
                    //  Add the user message as additional response to the exising responses
                    $this->text = $this->existing_session->text.'*'.$this->msg;
                }

            }

        }

        //  Get the timeout limit in seconds e.g "120" to mean "timeout after 120 seconds"
        $timeout_limit_in_seconds = $this->getTimeoutLimitInSeconds();

        //  Update the current session
        $update = $this->updateExistingSessionFromDatabase([
                    'text' => $this->text,
                    'request_type' => $this->request_type,
                    'updated_at' => (\Carbon\Carbon::now())->format('Y-m-d H:i:s'),
                    'timeout_at' => (\Carbon\Carbon::now())->addSeconds($timeout_limit_in_seconds)->format('Y-m-d H:i:s')
                ]);

        //  If the existing session has timeout
        if ($this->existing_session->has_timed_out) {
            
            //  Handle timeout
            return $this->handleTimeout();
        
        } else {
            
            //  Handle the current session
            return $this->handleSession($buildResponse);

        }
    }

    /** Get the existing USSD session from the database
     */
    public function getExistingSessionFromDatabase()
    {
        //  If we don't have an existing session already set
        if( empty( $this->existing_session ) ){

            //  Get the session record that matches the given Session Id
            return \App\UssdSession::where('session_id', $this->session_id)->where('test', $this->test_mode)->first();

        }

        //  If we have an existing session already set
        return $this->existing_session;

    }

    /** Update the existing USSD session from the database
     */
    public function updateExistingSessionFromDatabase($data = [])
    {      
        //  Update the session record that matches the given Session Id
        return \App\UssdSession::where('session_id', $this->session_id)->where('test', $this->test_mode)->update($data);
    }

    /** Set the timeout message and return the timeout screen.
     *  We also log a warning as an indication of the resulting
     *  timeout.
     */
    public function handleTimeout()
    {
        //  Set the timeout message
        $this->msg = $this->builder['simulator']['settings']['timeout_message'];

        //  If the timeout message was not provided
        if (empty($this->msg)) {

            //  Get the default timeout message found in "UssdSessionTraits" within "UssdSession" 
            $default_timeout_msg = (new \App\UssdSession())->default_timeout_message;

            //  Set the timeout message
            $this->msg = $default_timeout_msg;

        }

        //  Get the timeout limit in seconds e.g "120" to mean "timeout after 120 seconds"
        $timeout_limit_in_seconds = $this->getTimeoutLimitInSeconds();

        //  Get the session timeout date and time
        $timeout_date_time = (\Carbon\Carbon::parse($this->existing_session->timeout_at))->format('Y-m-d H:i:s');

        //  Set a warning that the session timed out
        $this->logWarning('Session timed out after '.$timeout_limit_in_seconds.' seconds. The session timed out at exactly '.$timeout_date_time);

        $response = $this->showTimeoutScreen($this->msg);

        //  Build and return the final response
        return $this->buildResponse($response);
    }

    /** Determine the response type and build the response
     *  payload including the USSD properties and the final
     *  response message
     */
    public function buildResponse($response)
    {
        /* Get the response message for display to the user e.g
         *
         *  Extract "Welcome, Enter Username" from "CON Welcome, Enter Username"
         *  Extract "Payment Successful" from "END Payment Successful"
         */
        $this->msg = $this->getResponseMsg($response);

        //  If the response indicates a continuing screen
        if ($this->isContinueScreen($response)) {

            //  Continue response
            $this->request_type = '2';

        //  If the response indicates an ending screen
        } elseif ($this->isEndScreen($response)) {

            //  End response
            $this->request_type = '3';

        //  If the response indicates a timeout screen
        } elseif ($this->isTimeoutScreen($response)) {

            //  Redirect response
            $this->request_type = '4';

        //  If the response indicates a redirecting screen
        } elseif ($this->isRedirectScreen($response)) {

            //  Redirect response
            $this->request_type = '5';

        }

        //  Build the response payload
        $response = [
            'session_id' => $this->session_id,
            'service_code' => $this->service_code,
            'request_type' => $this->request_type,
            'msisdn' => $this->msisdn,
            'text' => $this->text,
            'msg' => $this->msg,
            'logs' => [],
        ];

        //  If we are on test mode
        if ($this->test_mode) {
            
            //  Include the logs if required
            if ($this->builder['simulator']['debugger']['return_logs']) {

                //  Set an info log of the ussd properties
                $this->logInfo(
                    'USSD Properties: ' .
                    '<div style="line-height:2.5em;margin:10px 0;">'.
                        $this->wrapAsDynamicDataHtml('{{ ussd.text }}') .' = '.$this->wrapAsSuccessHtml( $this->getDynamicData('ussd.text') ). "<br>" .
                        $this->wrapAsDynamicDataHtml('{{ ussd.msisdn }}') .' = '.$this->wrapAsSuccessHtml( $this->getDynamicData('ussd.msisdn', 'None') ). "<br>" .
                        $this->wrapAsDynamicDataHtml('{{ ussd.user_account }}') .' = '.$this->wrapAsSuccessHtml( $this->getDynamicData('ussd.user_account') ). "<br>" .
                        $this->wrapAsDynamicDataHtml('{{ ussd.request_type }}') .' = '.$this->wrapAsSuccessHtml( $this->getDynamicData('ussd.request_type') ). "<br>" .
                        $this->wrapAsDynamicDataHtml('{{ ussd.service_code }}') .' = '.$this->wrapAsSuccessHtml( $this->getDynamicData('ussd.service_code') ). "<br>" .
                        $this->wrapAsDynamicDataHtml('{{ ussd.user_response }}') .' = '.$this->wrapAsSuccessHtml( $this->getDynamicData('ussd.user_response') ). "<br>" .
                        $this->wrapAsDynamicDataHtml('{{ ussd.user_responses }}') .' = '.$this->wrapAsSuccessHtml( $this->convertToString( $this->getDynamicData('ussd.user_responses') ) ). "<br>" .
                        $this->wrapAsDynamicDataHtml('{{ ussd.session_id }}') .' = '.$this->wrapAsSuccessHtml( $this->getDynamicData('ussd.session_id') ) .
                    '</div>'
                );
                
                //  Set the logs on the response payload
                $response['logs'] = $this->log;
            
            }

        }

        return $response;
    }



    /** Extract the response message from the given text by removing the
     *  first four characters representing the words "CON ", "END " 
     *  "TIM " or "RED " from the begining of the text.
     */
    public function getResponseMsg($text)
    {
        //  Check if the text represents screen content 
        if( $this->shouldDisplayScreen($text) ){

            $text = substr($text, 4);

            //  If the text extracted is not empty
            if( !empty($text) ){

                //  Return the text
                return $text;

            //  Return an empty string to prevent returning "false" when the text is empty
            }else{
                
                return '';

            }

        }

        return $text;
    }

    /** Determine the response type and build the response
     *  payload including the USSD properties and the final
     *  response message
     */
    public function handleSession($buildResponse = true)
    {
        $this->manageGoBackRequests();

        //  Start the process of building the USSD Application
        $response = $this->startBuildingUssd();

        if($buildResponse == true){
    
            //  Build and return the final response
            return $this->buildResponse($response);

        }else{

            return $response;

        }
        
    }

    /*  Scan and remove any responses the user indicated to omit. This is to help
     *  simulate the ability for the user to go back to previous screens so that
     *  they can choose another option. This will help the appllication to focus
     *  on the important responses knowing that any irrelevant response was
     *  already removed.
     */
    public function manageGoBackRequests()
    {
        /*  Get the user's response text value.
         */
        $text = $this->text;

        /*  Assuming the $text value is as follows:
         *
         *  1*2*3*4*0*0*0
         *
         *  We can explode it into an array of responses to get
         *
         *  ["1", "2", "3", "4", "0", "0", "0"]
         *
         */
        $responses = explode('*', $this->text);

        /*  Lets count how many times the zero (0) value appears
         *  from the responses we have.
         */
        $count = 0;

        foreach ($responses as $response) {
            if ($response == '0') {
                $count = ++$count;
            }
        }

        /*  Since we now know the number of times the value zero (0) appears on the
         *  user responses, we can loop through each instance knowing that we will
         *  find a zero (0) value. Lets assume we have the following responses
         *
         *  ["1", "2", "3", "4", "0", "0", "0"]
         *
         *  At this point our application can count the number of times the zero (0)
         *  value appears which is 3 times in the above example. This means we need
         *  to setup a looping function that will loop three times where for each
         *  loop we will locate the corresponding zero (0) value. Once any zero (0)
         *  value is located we will remove that zero (0) value and the immediate
         *  value that appears before that zero (0). In our example above we want
         *  that foreach time we loop we create a new loop that we go through all
         *  the response values trying to find the zero (0) value. once the value
         *  is located, we will remove it and then remove the value before. This
         *  is like we are cancelling or making that value non-existent. This will
         *  simulate the idea of going back since we cancel or remove the users
         *  previous response. So for instance in first loop, we will make a loop
         *  go through all the responses and locate a zero (0) and then remove it
         *  and the value before it, we will have the following result
         *
         *  ["1", "2", "3", "4", "0", "0", "0"]
         *
         *  Once we locate that zero value and remove it along with the previous
         *  value, we need to update a special array called $updated_responses
         *  with the new updated responses. After the first loop we have:
         *
         *  $updated_responses Before = ["1", "2", "3", "4", "0", "0", "0"]
         *  $updated_responses After  = ["1", "2", "3", "0", "0"]
         *
         *  On the second loop we have
         *
         *  $updated_responses Before = ["1", "2", "3", "0", "0"]
         *  $updated_responses After  = ["1", "2", "0"]
         *
         *  $updated_responses Before = ["1", "2", "0"]
         *  $updated_responses After  = ["1"]
         *
         *  In the end the result will be:
         *
         *  $updated_responses After = ["1"]
         *
         *  This makes sense because we started with three zero (0) values. Each
         *  zero (0) value was meant to cancel out each previous response thereby
         *  simulating a go back functionality
         *
         */

        $updated_responses = $responses;

        for ($x = 0; $x < $count; ++$x) {
            for ($y = 0; $y < count($updated_responses); ++$y) {
                if ($updated_responses[$y] == '0') {
                    unset($updated_responses[$y]);

                    if (isset($updated_responses[$y - 1])) {
                        unset($updated_responses[$y - 1]);
                    }

                    $updated_responses = array_values($updated_responses);

                    break;
                }
            }
        }

        /*  Now since we have updated the responses, we need to update the
         *  actual text value so that future methods and functions can use
         *  the updated text responses without any zero (0) values and the
         *  omitted responses.
         */

        $updated_text = implode('*', $updated_responses);

        $this->text = $updated_text;
    }

    /*  Validate the existence of the builder and start the process of using 
     *  the builder to setup the screens and underlying screen processes.
     */
    public function startBuildingUssd()
    {
        //  Set the application name
        $this->app_name = $this->project->name;

        //  Set a log that the build process has started
        $this->logInfo('Building '.$this->wrapAsPrimaryHtml( $this->app_name ).' App');

        //  Check if the Builder exist
        $doesNotExistResponse = $this->handleNonExistentBuilder();

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($doesNotExistResponse)) return $doesNotExistResponse;

        //  Set the current session User Account (If Any)
        $this->setUserAccount();

        //  Locally store the current session details within a dynamic variable
        $this->storeUssdSessionValues();

        //  Locally store the global variables within a dynamic variable
        $outputResponse = $this->storeGlobalVariables();
        
        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Start building and showing the ussd screens
        return $this->startBuildingUssdScreens();
    }

    public function storeGlobalVariables()
    {
        $this->logInfo('Start processing and storing global variables');

        $global_variables = $this->builder['global_variables'] ?? [];

        //  Foreach global variable
        foreach($global_variables as $global_variable){

            $name = $global_variable['name'];
            $type = $global_variable['type'];
            $value = $global_variable['value'];

            if( $name ){
                
                if( $type == 'String' ){

                    /*************************
                     * BUILD STRING VALUE    *
                     ************************/

                    //  Process dynamic content embedded within the text
                    $outputResponse = $this->handleEmbeddedDynamicContentConversion($value['string']);

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                    //  Get the generated output - Convert to (String) otherwise default to empty string
                    $value = $this->convertToString($outputResponse) ?? '';

                }elseif( $type == 'Integer' ){

                    /*************************
                     * BUILD NUMBER VALUE    *
                     ************************/

                    //  Process dynamic content embedded within the text
                    $outputResponse = $this->handleEmbeddedDynamicContentConversion($value['number']);

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                    //  Get the generated output - Convert to (Integer) otherwise default to (0)
                    $value = $this->convertToInteger($outputResponse) ?? 0;

                }elseif( $type == 'Boolean' ){

                    $value = $value['boolean'];

                    if( $value == 'true' ){

                        $value = true;

                    }elseif( $value == 'false' ){

                        $value = false;

                    }

                }elseif( $type == 'Custom' ){

                    $code = $value['code'];

                    //  Process the PHP Code
                    $outputResponse = $this->processPHPCode("$code");
        
                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;
        
                    $value = $outputResponse;

                }

                //  Store the value data using the given item reference name
                $this->storeDynamicData($name, $value);

            }
        }
    }
    
    /*  Validate the existence of the builder. If the builder does not exist then
     *  return the technical difficulties screen. This screen will also cause the
     *  end of the current session since its an ending screen.
     */
    public function handleNonExistentBuilder()
    {
        //  If we don't have a builder
        if (empty($this->builder)) {

            //  Set a warning log that we could not find the application Builder
            $this->logWarning( $this->wrapAsPrimaryHtml( $this->app_name ) . ' App builder was not found');

            //  Show the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();

        }
    }

    /*  Use the MSISDN number to get the User Account of the current session.
     *  If this is a test session, then we must find a test account that matches
     *  the MSISDN number, however if this is not a test session then we must
     *  find a real account that matches the MSISDN number. If any account
     *  is found, then set it as the current session User Account
     */
    public function setUserAccount()
    {
        //  Get the users mobile number
        $mobile_number = $this->msisdn;

        //  If we are on test mode
        if( $this->test_mode ){

            //  Get the User Test Account (Check if we have an account matching the mobile number)
            $user_account = \App\UserAccount::where('mobile_number', $mobile_number)->testAccount()->first();

        //  If we are not on test mode
        }else{

            //  Get the User Real Account (Check if we have an account matching the mobile number)
            $user_account = \App\UserAccount::where('mobile_number', $mobile_number)->realAccount()->first();

        }

        if( $user_account ){

            $this->user_account = $this->getUserAccountDetails($user_account);
            
        }
    }

    /*  Set the public ussd property to the current session details. Also
     *  store this property information as dynamic data. This will ensure
     *  that the builder has access to the data when parsing dynamic
     *  variables into values.
     */
    public function storeUssdSessionValues()
    {        
        //  Set the ussd property key/values
        $this->ussd = [
            'text' => $this->text,
            'msisdn' => $this->msisdn,
            'session_id' => $this->session_id,
            'user_account' => $this->user_account,
            'request_type' => $this->request_type,
            'service_code' => $this->service_code,
            'user_responses' => $this->getUserResponses(),
            'user_response' => $this->msg,
            'project' => [
                'name' => $this->project->name,
                'description' => $this->project->description
            ],
            'version' => [
                'number' => $this->version->number,
                'description' => $this->version->description
            ]
        ];

        //  Store the ussd data using the given item reference name
        $this->storeDynamicData('ussd', $this->ussd);
    }
    
    /** Return an Array of all the user responses given to the
     *  current session e.g ['1', '2', '3'] 
     */
    public function getUserResponses($text = null)
    {
        /*  The text variable represent the responses from the user. The given text 
         *  variable contains the responses separated using the "*" sybmbol. We 
         *  need to explode the given responses to have access to each and every 
         *  response e.g
         * 
         *  $this->text = '1*2*3*4*5'
         * 
         *  After we explode:
         * 
         *  $responses = ['1', '2', '3', '4', '5']
         *
         *  $responses[0] = Response from screen 1 (Landing Screen / First Screen)
         *  $responses[1] = Response from screen 2 (Second Screen)
         *  e.t.c
         */
        $responses = explode('*', $text ?? $this->text);

        //  Remove empty responses
        $responses = array_filter($responses, function ($value) {
            return !is_null($value) && $value !== '';
        });

        //  Return the responses
        return array_values($responses);
    }

    /** Return the user response of a given Level. Assuming we have 3 responses:
     *  $responses = ['Johnathan', 'Miller', '25']. Then:
     * 
     *  Level 1 response = 'Johnathan'   (Response to Screen 1)
     *  Level 2 response = 'Miller'   (Response to Screen 2)
     *  Level 3 response = '25'   (Response to Screen 3)
     *   
     */
    public function getResponseFromLevel($levelNumber = null)
    {
        //  If we have a level number provided
        if ($levelNumber) {

            //  Get all the user responses
            $user_responses = $this->getUserResponses();

            /** We want to say if we have levelNumber = 1 we should get the landing screen data
             *  (since thats level 1) but technically $user_responses[0] = landing screen response.
             *  This means to get the response for the level we want we must decrement by one unit.
             *
             *  Use urldecode() to convert all encoded values to their decoded counterparts e.g
             *
             *  "%23" is an encoded value representing "#"
             */

            return isset($user_responses[$levelNumber - 1]) ? urldecode($user_responses[$levelNumber - 1]) : null;
        }
    }

    /** Return true or false whether the user has responded to a
     *  specific level e.g Return true if the user responded to
     *  Level 3
     */
    public function completedLevel($levelNumber = null)
    {
        //  If we have a level number provided
        if ($levelNumber) {

            //  Check if we have a response for this level number
            $level = $this->getResponseFromLevel($levelNumber);

            //  If the level specified is completed (Has a response from the user)
            return isset($level) && $level != '';

        }
    }

    /*  Receives a variable name and value for storage as dynamic
     *  key/values that can be initialized as valid PHP variables
     *  with data properties
     */
    public function storeDynamicData($name = null, $value = null, $log_status = true)
    {

        //  If the variable name is provided and is not empty 
        if (isset($name) && !empty($name)) {

            //  If the variable name already exists among the stored values
            if (isset($this->dynamic_data_storage[$name])) {

                //  Set a warning log that we are overiding existing data
                if ($log_status) {

                    $this->logInfo('Found existing data already stored within the reference name '.$this->wrapAsSuccessHtml( $name ).', overiding the information.');
                
                }
                
                //  Get the old data type wrapped in html tags
                $dataType = $this->wrapAsSuccessHtml( $this->getDataType($this->getDynamicData($name)) );

                //  Set an info log of the old data stored
                if ($log_status) {

                    //  Use json_encode($dataType) to show $dataType data instead of getDataType($dataType)
                    $this->logInfo('Old value: ['.$dataType.']');

                }

                //  Replace the dynamic data within our dynamic data storage
                $this->dynamic_data_storage[$name] = $value;

                //  Get the new data type wrapped in html tags
                $dataType = $this->wrapAsSuccessHtml( $this->getDataType($this->getDynamicData($name)) );

                //  Set an info log of the new data stored
                if ($log_status) {

                    //  Use json_encode($dataType) to show $dataType data instead of getDataType($dataType)
                    $this->logInfo('New value: ['.$dataType.']');

                }

            //  If the variable name does not already exist among the stored values
            } else {

                //  Add the value as additional dynamic data to our dynamic data storage
                $this->dynamic_data_storage[$name] = $value;

            }
        }
    }

    public function getDynamicData($name = null, $default_value = null)
    {
        //  Get the entire dynamic data storage
        $result = $this->dynamic_data_storage;

        //  If the dynamic property name has not been provided
        if($name != null){

            /** Note that the given $name can either be a simple reference name e.g "ussd"
             *  or a more complex reference name e.g "ussd.text". The final result must
             *  convert into any of the following:
             * 
             *  If $name = "ussd" then return $this->dynamic_data_storage['ussd']
             *  If $name = "ussd.text" then return $this->dynamic_data_storage['ussd']['text']
             *  ... e.t.c
             */
    
            /** STEP 1 
             * 
             *  Convert $name = "ussd" into ['ussd'] 
             * 
             *  or
             * 
             *  Convert $name = "ussd.text" into ['ussd', 'text']
             */
            $properties = explode('.', $name);
    
            /** STEP 2 
             * 
             *  Iterate over the properties
             */
            for ($i = 0; $i < count($properties); $i++) { 
                
                /** STEP 3
                 * 
                 *  Foreach property e.g "ussd" or "text"
                 *  
                 *  Get the $result then get the property value
                 *  from the $result e.g 
                 * 
                 *  $result = [ ... ] 
                 *  $properties = ['ussd', 'text']
                 *  $i = 0, 1, 2, 3 ...
                 * 
                 *  $properties[i] = 'ussd' or 'text'
                 *  
                 *  $result[$properties[i]] is the same as:
                 *  $result['ussd'] or $result['text']
                 */
    
                //  Make sure that the given property exists
                if( isset($result[$properties[$i]]) ){
    
                    /** Equate the $result to the property value. In the first loop $result is equal to the
                     *  data within $this->dynamic_data_storage. During this first loop we capture the value
                     *  of $result['text'] which is exactly the same as $this->dynamic_data_storage['ussd'], 
                     *  and then make that value the new value for the $result property. On the second loop 
                     *  we then capture the result of $result['text'] which will be exactly the same as
                     *  $this->dynamic_data_storage['ussd']['text']. This process keeps repeating over
                     *  and over until we get to the last property.
                     */
                    $result = $result[$properties[$i]];
    
                }else{
    
                    //  Set $result to the deafult value to indicate that the value of such a property does not exist
                    $result = $default_value;
    
                }
    
            }

        }

        return $result;
        
    }

    /** Return the given value data type e.g String, Array, Boolean, e.t.c */
    public function getDataType($value)
    {
        return ucwords(gettype($value));
    }

    /** Wrap in primary colored HTML Tags */
    public function wrapAsPrimaryHtml($value)
    {
        return $this->wrapWithinHtml('text-primary', $value);
    }

    /** Wrap in success colored HTML Tags */
    public function wrapAsSuccessHtml($value)
    {
        return $this->wrapWithinHtml('text-success', $value);
    }

    /** Wrap in warning colored HTML Tags */
    public function wrapAsWarningHtml($value)
    {
        return $this->wrapWithinHtml('text-warning', $value);
    }

    /** Wrap in error colored HTML Tags */
    public function wrapAsErrorHtml($value)
    {
        return $this->wrapWithinHtml('text-danger', $value);
    }

    /** Wrap in dynamic data HTML Tags */
    public function wrapAsDynamicDataHtml($value)
    {
        return $this->wrapWithinHtml('dynamic-content-label', $value);
    }

    /** Wrap within HTML Tags */
    public function wrapWithinHtml($type, $value)
    {
        return '<span class="'.$type.'">'.$value.'</span>';
    }

    /******************************************
     *  SCREEN METHODS                        *
     *****************************************/

    /** This method uses the application builder get all the ussd screens,
     *  locate the first screen and start building each screen to be 
     *  returned.
     */
    public function startBuildingUssdScreens()
    {
        //  Check if the builder screens exist
        $doesNotExistResponse = $this->handleNonExistentScreens();

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($doesNotExistResponse)) return $doesNotExistResponse;

        //  Get the first screen
        $this->getFirstScreen();

        //  Handle current screen
        $response = $this->handleCurrentScreen();

        /** Check if the display data returned is greater than 160 characters.
         *  If it is set a warning log. Subtract out the first four characters
         *  first to remove the "CON " and "END ".
         */
        $characters = (strlen($response) - 4);

        if ($characters > 160) {
            //  Set a warning log that the content received is too long
            $this->logWarning('The screen content exceeds the maximum allowed content length of 160 characters. Returned '.$this->wrapAsSuccessHtml($characters).' characters');
        } else {
            //  Set an info log of the content character length
            $this->logInfo('Content Characters: '.$this->wrapAsSuccessHtml($characters).' characters');
        }

        return $response;
    }

    /*  Validate the existence of the builder screens. If the screens do not exist then
     *  return the technical difficulties screen. This screen will also cause the
     *  end of the current session since its an ending screen.
     */
    public function handleNonExistentScreens()
    {
        //  Check if the screens exist
        if ($this->checkIfScreensExist() == false) {

            //  Set a warning log that we could not find the builder screens
            $this->logWarning( $this->wrapAsPrimaryHtml( $this->app_name ) . ' App does not have any screens to show');

            //  Show the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();

        }

        //  Return null if we have screens
        return null;
    }

    /** This method checks if the builder screens exist. It will return true if 
     *  we have screens to show and false if we don't have screens to show.
     */
    public function checkIfScreensExist()
    {
        //  Check if the builder has a non empty array of screens
        if (is_array($this->builder['screens']) && !empty($this->builder['screens'])) {

            //  Return true to indicate that the screens exist
            return true;

        }

        //  Return false to indicate that the screens do not exist
        return false;
    }

    /** This method gets the first screen that we should show. First we look
     *  for a screen indicated by the user. If we can't locate that screen, 
     *  we then default to the first available screen that we can display.
     */
    public function getFirstScreen()
    {
        //  Set an info log that we are searching for the first screen
        $this->logInfo('Searching for the first screen');
    
        //  Get all the screens available
        $this->screens = $this->builder['screens'];

        //  If we are using condi
        if( $this->builder['conditional_screens']['active'] ){

            $this->logInfo('Processing code to conditionally determine first screen to load');

            //  Get the PHP Code
            $code = $this->builder['conditional_screens']['code'];

            //  Process the PHP Code
            $outputResponse = $this->processPHPCode("$code", false);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the processed screen id
            $screen_id = $this->convertToString($outputResponse);

            if( $screen_id ){

                $this->logInfo('Searching for screen using the screen id: '.$this->wrapAsSuccessHtml($screen_id));

                //  Get the screen usign the screen id
                $this->screen = $this->getScreenById($screen_id);
                
            }

        }else{
    
            //  Get the first display screen (The one specified by the user)
            $this->screen = collect($this->screens)->where('first_display_screen', true)->first() ?? null;
    
            //  If we did not manage to get the first display screen specified by the user
            if (!$this->screen) {
    
                //  Set a warning log that the default starting screen was not found
                $this->logWarning('Default starting screen was not found');
    
                //  Set an info log that we will use the first available screen
                $this->logInfo('Selecting the first available screen as the default starting screen');
    
                //  Select the first screen on the ussd builder by default
                $this->screen = $this->builder['screens'][0];
            }

        }

        if( $this->screen ){
    
            //  Set an info log for the first selected screen
            $this->logInfo('Selected '.$this->wrapAsPrimaryHtml($this->screen['name']).' as the first screen');

        }
    }

    /** This method first checks if the screen we want to handle exists. This could be the
     *  first display screen or any linked screen. In either case if the screen does not
     *  exist we log a warning and display the technical difficulties screen. We then 
     *  check if the given screen is a reapeating or non-repeating screen. If it is
     *  a repeating screen we handle the before repeating events, then call the
     *  repeat screen looping logic and finally call the after repeating events.
     *  If this is not a repeating screen we simply go ahead and start building
     *  the nested displays
     */
    public function handleCurrentScreen()
    {
        //  Check if the current screen exists
        $doesNotExistResponse = $this->handleNonExistentScreen();

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($doesNotExistResponse)) return $doesNotExistResponse;

        //  Manage the screen requirements e.g Does this screen require an Account or Subscription?
        $manageScreenResponse = $this->manageScreenRequirements();

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($manageScreenResponse)) return $manageScreenResponse;

        //  If we are required to change the screen
        if( $manageScreenResponse == 'change_screen' ){

            //  Handle the current screen that we have switched to
            return $this->handleCurrentScreen();
            
        }

        $this->screen_repeats = $this->checkIfScreenRepeats();

        //  Check if the current screen repeats
        if ( $this->screen_repeats ) {

            //  Handle before repeat events
            $handleEventsResponse = $this->handleBeforeRepeatEvents();

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($handleEventsResponse)) return $handleEventsResponse;

            //  Handle the repeat screen
            $handleScreenResponse = $this->handleRepeatScreen();

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($handleScreenResponse)) return $handleScreenResponse;

            //  Handle after repeat events
            $handleEventsResponse = $this->handleAfterRepeatEvents();

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($handleEventsResponse)) return $handleEventsResponse;

        } else {

            //  Start building the current screen displays
            return $this->startBuildingDisplays();

        }
    }

    /** This method gets the current screen and checks if the screen has any
     *  specific requirements such as "Does the screen require a subscriber
     *  to have an account?" or "Does the screen require a subscriber to 
     *  have a subscription?". After this we handle the screen 
     *  requirement
     */
    public function manageScreenRequirements()
    {
        //  Set an info log that we are checking if the current screen has any requirements
        $this->logInfo('Checking if '.$this->wrapAsPrimaryHtml( $this->screen['name'] ).' has any requirements');

        $requires_account = $this->screen['requirements']['requires_account'];
        $requires_subscription = $this->screen['requirements']['requires_subscription'];
    
        //  Get the active state value
        $activeState = $this->processActiveState($requires_account['active']);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($activeState)) return $activeState;

        //  If the screen "Requires Account"
        if ( $activeState === true ) {

            //  Set an info log that this screen requires the subscriber to have an account
            $this->logInfo($this->wrapAsPrimaryHtml( $this->screen['name'] ).' requires the subscriber to have an account');

            //  If we don't have a User Accoutn
            if( $this->user_account ){
                
                //  Set an info log that this subscriber already has an account
                $this->logInfo('The current subscriber already has a User Account');

            }else{

                //  Get the existing session record from the database
                $this->existing_session = $this->getExistingSessionFromDatabase();

                //  Get the existing session metadata
                $metadata = $this->existing_session->metadata ?? [];

                //  If we have the "revisit_text"
                if( isset($metadata['revisit_text']) ){ 
                    
                    /** Get the "revisit_text" value. This is the actual initial "text" that was dialed to 
                     *  start the USSD Service which we must revisit after creating the account e.g *321*2*3#. 
                     * 
                     *  Because we allow the user to visit the "Account Creation" screen in order to create their 
                     *  account, we allow the user to add on the intial "text" e.g *321*2*3#" that was dialed. As 
                     *  the user provides details such as their names, preferences, e.t.c we end up with something 
                     *  like "*321*2*3*John*Doe*26*1#" as the user replies to create their account. 
                     * 
                     *  Now since we already have replies attached to "*321#" in the form of *321*2*3#, when trying 
                     *  to create the account the replies "2" and "3" will be used as responses to the "Account Creation" 
                     *  screen. This is not desirable. To avoid this we must store the initial text e.g "2*3#" within the 
                     *  current session metadata as a vairable called "revisit_text". Each time the user responds to the 
                     *  the "Create Account" screen get the "revisit_text" and eliminate the original text value from the 
                     *  responses used on the "Create Account".
                     * 
                     *  E.g We start with *321*2*3# as the initial response for the subscriber to launch the service, 
                     *  use "2" to select "Stores" and "3" to specify a specific store. While processing we realise the 
                     *  "Welcome Screen" needs to create an account first so we load up the "Account Creation" screen. At 
                     *  this moment we get the "$this->text" and store it as metadata information called "revisit_text".
                     *  Then we set the "$this->text" to nothing since when we start the "Account Creation" we should not 
                     *  have any replies. Now when the user replies to create their account we grab the "revisit_text" 
                     *  from the session metadata stored in the database. We use this to cut out the initial replies to 
                     *  leave only the account creation replies
                     */

                    /** Count the characters from the "revisit_text" e.g if we have 
                     *  "*321*2*3#" then "$text_length" must be equal to "9"
                     */
                    $text_length = strlen($metadata['revisit_text']);


                    /** Get only the text that is used for the "Account Creation" process 
                     *  by getting the current text and removing the intial text from the
                     *  "revisit_text" e.g 
                     * 
                     *  If we have "*321*2*3*John*Doe*26#" as the current text.
                     *  and "*321*2*3#" as the "revisit_text". The we must only
                     *  retrieve "John*Doe*26#" e.g
                     *  
                     *    $this->text           =  *321*2*3*John*Doe*26#
                     *    revisit_text          =  *321*2*3#
                     *                             ---------------------
                     *    $this->text (updated) =           John*Doe*26#
                     *                             ---------------------
                     * 
                     *  Note that "John*Doe*26#" must become the update $this->text value
                     *  to use during the "Account Creation" process.
                     */

                    $this->text = substr($this->text, $text_length);

                }else{

                    /** Lets assume that the user dials "*321*2*3#". In this, we assume that
                     *  *321# launches the USSD Service e.g: 
                     * 
                     *  Welcome Screen
                     *  *****************************************************
                     *  *   Welcome to our service                          *
                     *  *   1) My Profile                                   *
                     *  *   2) Stores                                       *
                     *  *   3) T&Cs                                         *
                     *  *****************************************************
                     * 
                     *  Then "2" and "3" are the USSD replies. Lets assume that "2" is used to 
                     *  select an option called "stores" which then leads to a screen where 
                     *  the user must specify the store to visit e.g 
                     * 
                     *  Stores Screen
                     *  *****************************************************
                     *  *   Select a store to visit                         *
                     *  *   1) Store 1                                      *
                     *  *   2) Store 2                                      *
                     *  *   3) Store 3                                      *
                     *  *****************************************************
                     * 
                     *  Then "3" is used to specify the store to visit "store 3". This leads the 
                     *  user to the store home screen.
                     * 
                     *  Store Homepage Screen
                     *  *****************************************************
                     *  *   Welcome to our Store 3                          *
                     *  *   1) Buy Groceries                                *
                     *  *   2) View Order                                   *
                     *  *   3) Contacts                                     *
                     *  *****************************************************
                     * 
                     *  Now lets also assume that the first screen launched  i.e "Welcome Screen" 
                     *  requires the user to have a user account. However we quickly also realise
                     *  that upon finishing to create an account we need to redirect the user to
                     *  their initial request which is to visit "Store 3". Then we must store the
                     *  "revisit_text" as the initial "text" so that we can later attempt to run
                     *  it again and try access "Store 3" but this time with an account.
                     * 
                     */

                    //  Overide the existing session metadata
                    $metadata = array_merge($metadata, [
                        'revisit_text' => $this->text
                    ]);
    
                    $this->logError('$metadata');
                    $this->logError($metadata);
    
                    //  Update the current session
                    $ussd_session = $this->updateExistingSessionFromDatabase([
                        'metadata' => $metadata
                    ]);
                    
                    //  Reset the text so that we don't have any responses for the "Account Creation" screen
                    $this->text = '';

                }




                
                //  Set an info log that this subscriber already has an account
                $this->logInfo('The current subscriber does not have a User Account');

                $link = $requires_account['link'];

                //  Get the screen matching the given link and set it as the current screen
                $screen = $this->getScreenById($link);

                //  If the screen to link to was found
                if ( $screen ) {

                    $this->screen = $screen;

                    //  Set an info log that we are redirecting
                    $this->logInfo('Redirecting to '. $this->wrapAsPrimaryHtml($this->screen['name'] ).' to handle account creation' );
                    
                    //  Stop here
                    return '';

                }

                //  Set an info log that we are redirecting
                $this->logWarning($this->wrapAsPrimaryHtml($this->screen['name'] ).' could not link to account creation screen as it does not exist.');

            }

        }

        //  Get the active state value
        $activeState = $this->processActiveState($requires_subscription['active']);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($activeState)) return $activeState;

        //  If the screen "Requires Subscription"
        if ( $activeState === true ) {

            //  Set an info log that this screen requires the subscriber to have an active subscription
            $this->logInfo($this->wrapAsPrimaryHtml( $this->screen['name'] ).' requires the subscriber to have an active subscription');

            $link = $requires_subscription['link'];

            //  Get the screen matching the given link and set it as the current screen
            $screen = $this->getScreenById($link);

            //  If the screen to link to was found
            if ( $screen ) {

                $this->screen = $screen;

                //  Set an info log that we are redirecting
                $this->logInfo('Redirecting to '. $this->wrapAsPrimaryHtml($this->screen['name'] ).' to handle subscription' );
                
                //  Stop here
                return '';

            }

            //  Set an info log that we are redirecting
            $this->logWarning($this->wrapAsPrimaryHtml($this->screen['name'] ).' could not link to subscription screen as it does not exist.');

        }
    }

    /*  Validate the existence of the current screen. If the current screen does not exist 
     *  then we return the technical difficulties screen. This screen will also cause the
     *  end of the current session since its an ending screen.
     */
    public function handleNonExistentScreen()
    {
        //  If the linked screen exists
        if (empty($this->screen)) {

            //  Set a warning log that the linked screen could not be found
            $this->logWarning('The linked screen could not be found');

            //  Show the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();
            
        }

        return null;
    }

    /*  Check if the current screen repeats
     */
    public function checkIfScreenRepeats()
    {
        //  Set an info log that we are checking if the current screen repeats
        $this->logInfo('Checking if '.$this->wrapAsPrimaryHtml( $this->screen['name'] ).' repeats');

        //  Get the active state value
        $activeState = $this->processActiveState($this->screen['repeat']['active']);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($activeState)) return $activeState;

        //  If the screen is set to repeats
        if ( $activeState === true ) {

            //  Set an info log that the current screen does repeat
            $this->logInfo($this->wrapAsPrimaryHtml( $this->screen['name'] ).' does repeat');

            //  Return true to indicate that the screen does repeat
            return true;

        }

        //  Set an info log that the current screen does not repeat
        $this->logInfo($this->wrapAsPrimaryHtml( $this->screen['name'] ).' does not repeat');

        //  Return false to indicate that the screen does not repeat
        return false;

    }

    /*  Determine the type of repeating screen that has been indicated.
     *  e.g "Repeat On Items", "Repeat On Number", e.t.c
     */
    public function handleRepeatScreen()
    {
        //  Get the repeat type e.g "repeat_on_number" or "repeat_on_items"
        $repeatType = $this->screen['repeat']['selected_type'];

        //  If the screen is set to repeats
        if ($repeatType == 'repeat_on_number') {

            //  Set an info log that the current screen repeats on a given number
            $this->logInfo($this->wrapAsPrimaryHtml( $this->screen['name'] ).' repeats on a given number');

        } elseif ($repeatType == 'repeat_on_items') {

            //  Set an info log that the current screen repeats on a set of items
            $this->logInfo($this->wrapAsPrimaryHtml( $this->screen['name'] ).' repeats on a group of items');

        }

        //  Start the repeat screen process
        return $this->startRepeatScreen($repeatType);
    }

    /*  Start the screen repeat process based on the specified
     *  type of repeating strategy e.g "Repeat On Items", 
     *  "Repeat On Number", e.t.c
     */
    public function startRepeatScreen($type)
    {
        if ($type == 'repeat_on_items') {

            $repeat_data = $this->screen['repeat']['repeat_on_items'];

            //  Get the group reference value e.g mustache tag or PHP Code
            $group_reference = $repeat_data['group_reference'];

            //  Get the current item reference name e.g "product"
            $item_reference_name = $repeat_data['item_reference_name'];

            //  Convert the group reference value into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($group_reference);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output e.g "An Array of products"
            $items = $outputResponse;

        } elseif ($type == 'repeat_on_number') {

            $repeat_data = $this->screen['repeat']['repeat_on_number'];

            $repeat_number = $repeat_data['value'];

            //  Convert the repeat number into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($repeat_number);

            //  Get the generated number otherwise default to zero (0)
            $repeat_number_value = $this->convertToInteger($outputResponse) ?? 0;

            //  If the number is equal to zero
            if ($repeat_number_value == 0) {
                
                //  Set a warning log that we are converting the dynamic property to its associated value
                $this->logWarning('The repeat number has a value = '.$this->wrapAsSuccessHtml('0').', therefore we won\'t be able to loop and repeat the screen');
            
            }

            /** Fill the $items with an array of values starting with Index = 0. Add items equal to the 
             *  number of the $repeat_number_value. Example results:
             *
             *  array_fill(0, 5, 'item') = ['item', 'item', 'item', 'item', 'item'];
             */
            $items = array_fill(0, $repeat_number_value, 'item');
        }

        //  Get the total items reference name e.g "total_products"
        $total_loops_reference_name = $repeat_data['total_loops_reference_name'];

        //  Get the current loop index reference name e.g "product_index"
        $loop_index_reference_name = $repeat_data['loop_index_reference_name'];

        //  Get the current loop number reference name e.g "product_number"
        $loop_number_reference_name = $repeat_data['loop_number_reference_name'];

        //  Get the reference name for confirming if the current item is the first item e.g "is_first_product"
        $is_first_loop_reference_name = $repeat_data['is_first_loop_reference_name'];

        //  Get the reference name for confirming if the current item is the last item e.g "is_last_product"
        $is_last_loop_reference_name = $repeat_data['is_last_loop_reference_name'];

        //  Check if the given items are of type Array
        if (is_array($items)) {

            //  Check if we have any items
            if (count($items) > 0) {

                //  Foreach item
                for ($x = 0; $x < count($items); ++$x) {
                    
                    //  Set an info log of the current repeat instance
                    $this->logInfo($this->wrapAsPrimaryHtml( $this->screen['name'] ).' repeat instance ['.$this->wrapAsPrimaryHtml($x + 1).']');

                    //  If we are repeating on a set of items
                    if ($type == 'repeat_on_items') {

                        //  If the item reference name is provided
                        if (!empty($item_reference_name)) {

                            //  Store the current item using the given item reference name
                            $this->storeDynamicData($item_reference_name, $items[$x]);
                        
                        }
                    }

                    //  If the total items reference name is provided
                    if (!empty($total_loops_reference_name)) {
                        
                        //  Store the current total items using the given reference name
                        $this->storeDynamicData($total_loops_reference_name, count($items));
                    
                    }

                    //  If the item index reference name is provided
                    if (!empty($loop_index_reference_name)) {

                        $this->logInfo('Item Index: ['.$this->wrapAsPrimaryHtml($x).']');

                        //  Store the current item index using the given item reference name
                        $this->storeDynamicData($loop_index_reference_name, $x);

                    }

                    //  If the item number reference name is provided
                    if (!empty($loop_number_reference_name)) {

                        $this->logInfo('Item Number: ['.$this->wrapAsPrimaryHtml($x + 1).']');

                        //  Store the current item number using the given item reference name
                        $this->storeDynamicData($loop_number_reference_name, ($x + 1));
                    }

                    //  If the first item reference name is provided
                    if (!empty($is_first_loop_reference_name)) {

                        $this->logInfo('Item Is First: ['.$this->wrapAsPrimaryHtml(($x == 0)).']');

                        //  Store the true/false result for first item using the given item reference name
                        $this->storeDynamicData($is_first_loop_reference_name, ($x == 0));

                    }

                    //  If the last item reference name is provided
                    if (!empty($is_last_loop_reference_name)) {

                        $this->logInfo('Item Is Last: ['.$this->wrapAsPrimaryHtml((($x + 1) == count($items))).']');

                        //  Store the true/false result for last item using the given item reference name
                        $this->storeDynamicData($is_last_loop_reference_name, (($x + 1) == count($items)));

                    }

                    //  Start building the current screen displays
                    $buildResponse = $this->startBuildingDisplays();

                    /** If we must navigate forward / backward then we must determine where the navigation must occur.
                     *  Remember that it is possible to have multiple nested screens using the repeat logic e.g
                     *  
                     *  Screen 1 (Repeat logic 1) 
                     *      Screen 2 (Repeat logic 2) 
                     *          Screen 3 (Repeat logic 3) 
                     *              ... e.t.c
                     * 
                     *  It can happen that while we are using the repeat logic in "Screen 3" that the user indicates
                     *  that they want to navigate i.e (iterate forward/backward). In that instance we need to inspect
                     *  where exactly does the user want to perform the navigation i.e (At Screen 1, Screen 2 or at 
                     *  Screen 3). We can use the specified screen link to determine the target screen e.g
                     * 
                     *  $this->navigation_target_screen_id = "specified screen id"
                     * 
                     *  The "$this->navigation_target_screen_id" represents the ID of the screen that must be targeted to 
                     *  perform the navigation action. We must match each linked Screen using the repeat logic to determine 
                     *  if it is the target screen.
                     */
                    if($this->navigation_request_type == 'navigate-forward' || $this->navigation_request_type == 'navigate-backward'){
                        
                        //  If the current screen id does not match the navigation target screen id
                        if($this->screen['id'] != $this->navigation_target_screen_id){

                            /** Since the current screen does not match the navigation target, we need to go back to
                             *  the previous linked screen if any and run the same logic to see if it matches up as
                             *  the target screen. To do this we access the history of chained screens. This is a
                             *  list of screens that were recorded each time we linked from one screen to another.
                             *  This will allow us to check if we have any previous chaining screens
                             */
                            if( count( $this->chained_screens ) ){

                                //  Get the last chained screen and set it as the current screen
                                $this->screen = $this->chained_screens[ count($this->chained_screens) - 1 ];

                                //  Remove the last chained screen from the list
                                array_pop($this->chained_screens);

                            }

                            /** Return the build response to the previous screen for processing.
                             *  If the previous linked screen uses the repeat logic, then it will
                             *  also run this logic to determine if it should navigate forward or
                             *  backward otherwise it will also return the build response to its
                             *  previous linked screen. 
                             */
                            return $buildResponse;

                        }
                            
                        //  Continue navigation processs below
                    }

                    //  If we must navigate forward then proceed to next iteration otherwise continue
                    if ($this->navigation_request_type == 'navigate-forward') {

                        //  If this is not the last item then we can navigate forward
                        if (($x + 1) != count($items)) {

                            /** Use the forward navigation step number to decide which next iteration to target. For instance if
                             *  the number we receive equals 1 it means target the first next item. If the number we receive
                             *  equals 2 it means target the second next item. This is of course we assume the item in that
                             *  requested position exists. If it does not exist we work backwards to target the closest
                             *  available item. For instance lets assume we have items in position 1, 2, 3 and 4. We are
                             *  currently in position 1. If the step number equals "1" we target item in position "2".
                             *  If the step number equals "2" we target item in position "3" and so on. Now lets
                             *  assume we have number equals "4", this means we target item in position "5" but
                             *  such an item does not exist. This means we work backwards to target item in
                             *  position "4" instead.
                             *
                             *  $this->navigation_step_number = 1, 2, 3 ... e.t.c
                             */

                            $step = $this->navigation_step_number;

                            /** Assume $step = 5, this means we want to skip to every 5th item.
                             *
                             *  If $y = 0 ; This means we are currently targeting [Item 1].
                             *
                             *  If $step = 5; This means we want to target item of index number "5" [Item 6] (if it exists).
                             *  Note that item of index "5" is actually [Item 6]. A simple way to see this
                             *  is in this manner:
                             *
                             *  [Item 1] + 5 steps = [Item 6]
                             *
                             *  Visual example with $step = 5
                             *  --------------------------------------------------------
                             *  From    [1] 2  3  4  5  6  7  8  9  10  11  12 ...
                             *  To       1  2  3  4  5 [6] 7  8  9  10  11  12 ...
                             *  ...      1  2  3  4  5  6  7  8  9  10 [11] 12 ...
                             *           .  .  .  .  .  .  .  .  .   .   .   .
                             *           .  .  .  .  .  .  .  .  .   .   .   .
                             *  --------------------------------------------------------
                             *  Indexes: 0  1  2  3  4  5  6  7  8   9  10  11
                             *  --------------------------------------------------------
                             *
                             *  Translated into index format:
                             *
                             *  [Item Index 0] + 5 steps = [Item Index 5]
                             */

                            for ($y = $step; $y >= 1; --$y) {

                                // Example: For $y = 5 ... 4 ... 3 ... 2 ... 1

                                /** Note $items[$x] targets the current item and $items[$x + $y] targets the next item.
                                 *  If the item we want to target does not exist, then we attempt to target the item
                                 *  before it. We repeat this until we can get an existing item to target.
                                 *
                                 *  Example: If we wanted to target [item 6] but it does not exist, then we try to
                                 *  target [item 5], then [item 4] and so on... If we reach a point where no items
                                 *  after [item 1] can be found then we do not iterate anymore.
                                 */
                                if (isset($items[$x + $y])) {

                                    $this->logInfo('Navigating to '.$this->wrapAsPrimaryHtml('Item #'.($x + $y + 1)));

                                    /** If the item exists then we need to alter the parent for($x){ ... } method to target
                                     *  the item we want.
                                     *
                                     *  Lets assume [item 6] was found 5 steps after [item 1]. Since normally the for($x){ ... }
                                     *  would increment the $x value by only (1), we need to alter its bahaviour to increment
                                     *  based on the $y value we have. Basically to target the item we want we will use:
                                     *
                                     *  $items[index] where index = ($x + $y)
                                     *
                                     *  However on the next iteration the index value will be incremented by (1) and the result
                                     *  will be:
                                     *
                                     *  $items[index] where index = ($x + $y + 1)
                                     *
                                     *  To counteract this result we must make sure that the index value is decremented by (1)
                                     *  i.e index = ($x + $y - 1) so that on next iteration index = ($x + $y - 1 + 1) giving
                                     *  us the final output of index = ($x + $y) to target the item we want
                                     */
                                    $x = ($x + $y - 1);

                                    //  Stop the current loop
                                    break 1;
                                }
                            }
                        } else {

                            $this->logInfo($this->wrapAsPrimaryHtml( $this->screen['name'] ) . ' has reached the last item of the repeat loop');

                            //  Get the "After Last Loop Behaviour Type" e.g "do_nothing", "link"
                            $after_last_loop = $repeat_data['after_last_loop']['selected_type'];

                            //  Do nothing else
                            if ($after_last_loop == 'do_nothing') {

                                $this->logInfo($this->wrapAsPrimaryHtml( $this->screen['name'] ).' is defaulting to building and showing its first display');

                            //  Link to screen
                            } elseif ($after_last_loop == 'link') {

                                $this->logInfo($this->wrapAsPrimaryHtml( $this->screen['name'] ).' is attempting to link to another screen');

                                //  Hold reference to the current screen name
                                $current_screen_name = $this->screen['name'];

                                //  Get the provided link (The display or screen we must link to after the last loop of this screen)
                                $link = $repeat_data['after_last_loop']['link'];

                                //  Get the screen matching the given link and set it as the current screen
                                $screen = $this->getScreenById($link);

                                //  If the screen to link to was found
                                if ( $screen ) {

                                    $this->screen = $screen;

                                    $this->logInfo($this->wrapAsPrimaryHtml( $current_screen_name ).' is linking to '.$this->wrapAsPrimaryHtml( $this->screen['name'] ));

                                    //  Start building the current screen displays
                                    return $this->startBuildingDisplays();

                                }
                            }

                            return $buildResponse;

                        }

                        //  Do nothing else so that we iterate to the next specified item on the list
                    } elseif ($this->navigation_request_type == 'navigate-backward') {

                        /** Use the forward navigation step number to decide which next iteration to target. For instance if
                         *  the number we receive equals 1 it means target the first previous item. If the number we receive
                         *  equals 2 it means target the second previous item. This is of course we assume the item in that
                         *  requested position exists. If it does not exist we work forward to target the closest available
                         *  item. For instance lets assume we have items in position 1, 2, 3 and 4. We are currently in
                         *  position 4. If the step number equals "1" we target item in position "3". If the step number
                         *  equals "2" we target item in position "2" and so on. Now lets assume we have number equals "4",
                         *  this means we target item in position "0" but such an item does not exist. This means we work
                         *  forward to target item in position "1" instead.
                         *
                         *  $this->navigation_step_number = 1, 2, 3 ... e.t.c
                         */
                        $step = $this->navigation_step_number;

                        /** Assume $step = 5, this means we want to skip to every previous 5th item.
                         *
                         *  If $y = 10 ; This means we are currently targeting [Item 11].
                         *
                         *  If $step = 5; This means we want to target item of index number "5" [Item 6] (if it exists).
                         *  Note that item of index "5" is actually [Item 6]. A simple way to see this
                         *  is in this manner:
                         *
                         *  [Item 11] - 5 steps = [Item 6]
                         *
                         *  Visual example with $step = 5
                         *  --------------------------------------------------------
                         *  From     1  2  3  4  5  6  7  8  9  10 [11] 12 ...
                         *  To       1  2  3  4  5 [6] 7  8  9  10  11  12 ...
                         *  ...     [1] 2  3  4  5  6  7  8  9  10  11  12 ...
                         *           .  .  .  .  .  .  .  .  .   .   .   .
                         *           .  .  .  .  .  .  .  .  .   .   .   .
                         *  --------------------------------------------------------
                         *  Indexes: 0  1  2  3  4  5  6  7  8   9  10  11
                         *  --------------------------------------------------------
                         *
                         *  Translated into index format:
                         *
                         *  [Item Index 10] - 5 steps = [Item Index 5]
                         */
                        for ($y = $step; $y >= 0; --$y) {
                            // Example: For $y = 5 ... 4 ... 3 ... 2 ... 1 ... 0

                            /** Note $items[$x] targets the current item and $items[$x - $y] targets the previous item.
                             *  If the item we want to target does not exist, then we attempt to target the item
                             *  after it. We repeat this until we can get an existing item to target.
                             *
                             *  Example: If we wanted to target [item -1] but it does not exist, then we try to
                             *  target [item 0], then [item 1] and so on... If we reach a point where no items
                             *  after [item -1] can be found then we do not iterate anymore.
                             */
                            if (isset($items[$x - $y])) {

                                $this->logInfo('Navigating to '.$this->wrapAsPrimaryHtml('Item #'.($x - $y + 1)));

                                /** If the item exists then we need to alter the parent for($x){ ... } method to target
                                 *  the item we want.
                                 *
                                 *  Lets assume [item 6] was found 5 steps before [item 11]. Since normally the for($x){ ... }
                                 *  would increment the $x value by only (1), we need to alter its bahaviour to increment
                                 *  based on the $y value we have. Basically to target the item we want we will use:
                                 *
                                 *  $items[index] where index = ($x - $y)
                                 *
                                 *  However on the next iteration the index value will be incremented by (1) and the result
                                 *  will be:
                                 *
                                 *  $items[index] where index = ($x - $y + 1)
                                 *
                                 *  To counteract this result we must make sure that the index value is decremented by (1)
                                 *  i.e index = ($x - $y - 1) so that on next iteration index = ($x - $y - 1 + 1) giving
                                 *  us the final output of index = ($x - $y) to target the item we want
                                 */

                                //return 'CON $x = '.$x.' $y = '.$y;

                                $x = ($x - $y - 1);

                                //return 'CON Final $x = '.$x;

                                //  Stop the current loop
                                break 1;
                            }
                        }

                        //  If we reached this area, then we could not find any

                        //  Do nothing else so that we iterate to the next specified item on the list
                    } else {

                        return $buildResponse;

                    }
                }

            } else {

                $this->logWarning($this->wrapAsPrimaryHtml( $this->screen['name'] ).' has '.$this->wrapAsPrimaryHtml('0').' loops. For this reason we cannot repeat over the screen displays');

                //  Get the "No Loop Behaviour Type" e.g "do_nothing", "link"
                $on_no_loop_type = $repeat_data['on_no_loop']['selected_type'];

                //  Do nothing
                if ($on_no_loop_type == 'do_nothing') {

                    $this->logInfo($this->wrapAsPrimaryHtml( $this->screen['name'] ).' is defaulting to building and showing its first display');

                //  Do nothing else
                } elseif ($on_no_loop_type == 'link') {

                    $this->logInfo($this->wrapAsPrimaryHtml( $this->screen['name'] ).' is attempting to link to another screen');

                    //  Hold reference to the current screen name
                    $current_screen_name = $this->screen['name'];

                    //  Get the provided link (The display or screen we must link to if we don't have loops for this screen)
                    $link = $repeat_data['on_no_loop']['link'];

                    //  Get the screen matching the given link and set it as the current screen
                    $screen = $this->getScreenById($link);

                    //  If the screen to link to was found
                    if ( $screen ) {

                        $this->screen = $screen;

                        $this->logInfo($this->wrapAsPrimaryHtml( $current_screen_name ).' is linking to '.$this->wrapAsPrimaryHtml( $this->screen['name'] ));

                    }
                }

                //  Start building the current screen displays
                return $this->startBuildingDisplays();

            }
        } else {

            //  Get the items type wrapped in html tags
            $dataType = $this->wrapAsSuccessHtml( $this->getDataType($items) );

            //  Set a warning log that the dynamic property is not an array
            $this->logWarning('The looping items provided must be of type ['.$this->wrapAsSuccessHtml('Array').'] however we received type of ['.$dataType.']. For this reason we cannot repeat the screen');
        }
    }

    /******************************************
     *  DISPLAY METHODS                        *
     *****************************************/

    /** This method uses the current screen get all the screen displays,
     *  locate the first display and start building each display to be 
     *  returned.
     */
    public function startBuildingDisplays()
    {
        //  Check if the current screen displays exist
        $doesNotExistResponse = $this->handleNonExistentDisplays();

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($doesNotExistResponse)) return $doesNotExistResponse;

        //  Get the first display
        $this->getFirstDisplay();

        //  Handle current display
        return $this->handleCurrentDisplay();
    }

    /*  Validate the existence of the screen displays. If the displays do not exist then
     *  return the technical difficulties screen. This screen will also cause the
     *  end of the current session since its an ending screen.
     */
    public function handleNonExistentDisplays()
    {
        //  Check if the displays exist
        if ($this->checkIfDisplaysExist() != true) {

            //  Set a warning log that we could not find the displays
            $this->logWarning( $this->wrapAsPrimaryHtml( $this->screen['name'] ).' does not have any displays to show');

            //  Show the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();

        }

        //  Return null if we have displays
        return null;
    }

  /** This method checks if the screen displays exist. It will return true if 
    *  we have displays to show and false if we don't have displays to show.
    */
    public function checkIfDisplaysExist()
    {
        //  Check if the screen has a non empty array of displays
        if (is_array($this->screen['displays']) && !empty($this->screen['displays'])) {
            
            //  Return true to indicate that the displays exist
            return true;

        }

        //  Return false to indicate that the displays do not exist
        return false;
    }

    /** This method gets the first display that we should show. First we look
     *  for a display indicated by the user. If we can't locate that display,
     *  we then default to the first available display that we can display.
     */
    public function getFirstDisplay()
    {
        //  Set an info log that we are searching for the first display
        $this->logInfo('Searching for the first display');

        //  Get all the displays available
        $this->displays = $this->screen['displays'];

        //  If we are using condi
        if( $this->screen['conditional_displays']['active'] ){

            $this->logInfo('Processing code to conditionally determine first display to load');

            //  Get the PHP Code
            $code = $this->screen['conditional_displays']['code'];

            //  Process the PHP Code
            $outputResponse = $this->processPHPCode("$code", false);

            //  If we have a display to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the processed screen id
            $display_id = $this->convertToString($outputResponse);

            if( $display_id ){

                $this->logInfo('Searching for display using the display id: '.$this->wrapAsSuccessHtml($display_id));

                //  Get the display usign the screen id
                $this->display = $this->getDisplayById($display_id);
                
            }

        }else{

            //  Get the first display (The one specified by the user)
            $this->display = collect($this->displays)->where('first_display', true)->first() ?? null;

            //  If we did not manage to get the first display specified by the user
            if (!$this->display) {

                //  Set a warning log that the default starting display was not found
                $this->logWarning('Default starting display was not found');

                //  Set an info log that we will use the first available display
                $this->logInfo('Selecting the first available display as the default starting display');

                //  Select the first display on the available displays by default
                $this->display = $this->displays[0];
            }

        }

        if( $this->display ){
    
            //  Set an info log for the first selected display
            $this->logInfo('Selected '.$this->wrapAsPrimaryHtml($this->display['name']).' as the first display');

        }
    }

    /** This method first checks if the display we want to handle exists. This could be the
     *  first display or any linked display. In either case if the display does not exist 
     *  we log a warning and show the technical difficulties screen. We then check if the 
     *  user has already responded to the current display. If (No) then we build and 
     *  return the current display. If (Yes) then we need to validate, format and 
     *  store the users response respectively if specified and handle any 
     *  additional logic such as linking to respective displays/displays
     */
    public function handleCurrentDisplay()
    {
        //  Reset pagination
        $this->resetPagination();

        //  Reset navigation
        $this->resetNavigation();

        //  Reset incorrect option selected
        $this->resetIncorrectOptionSelected();

        //  Check if the current display exists
        $doesNotExistResponse = $this->handleNonExistentDisplay();

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($doesNotExistResponse)) return $doesNotExistResponse;

        //  Handle before display events
        $handleEventsResponse = $this->handleBeforeResponseEvents();

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($handleEventsResponse)) return $handleEventsResponse;

        //  Build the current screen display
        $builtDisplay = $this->buildCurrentDisplay();

        //  Check if the user has already responded to the current display screen
        if ($this->completedLevel($this->level)) {

            //  Get the user response (Input provided by the user) for the current display screen
            $this->getCurrentScreenUserResponse();

            //  Store the user response (Input provided by the user) as a named dynamic variable
            $storeInputResponse = $this->storeCurrentDisplayUserResponseAsDynamicVariable();

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($storeInputResponse)) return $storeInputResponse;

            //  Handle after display events
            $handleEventsResponse = $this->handleAfterResponseEvents();

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($handleEventsResponse)) return $handleEventsResponse;

            //  Handle linking to screen or display
            $handleLinkingResponse = $this->handleLinkingDisplay();

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($handleLinkingResponse)) return $handleLinkingResponse;

            //  Handle forward navigation
            $handleForwardNavigationResponse = $this->handleNavigation('forward');

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($storeInputResponse)) return $storeInputResponse;

            //  Handle backward navigation
            $handleBackwardNavigationResponse = $this->handleNavigation('backward');

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($storeInputResponse)) return $storeInputResponse;

            /** If the current display intends to navigate or if the linked display intends to
             *  navigate then return the current builtDisplay. We return the current builtDisplay
             *  incase the navigation logic cannot find the screen to navigate, then we can atleast
             *  show the last build display information
             */
            if ( ($handleLinkingResponse || $this->navigation_request_type) == 'navigate-forward' ||
                 ($handleLinkingResponse || $this->navigation_request_type) == 'navigate-backward' ) {

                return $builtDisplay;

            }
                
            // If we have the "incorrect option selected message"
            if (!empty($this->incorrect_option_selected)) {

                /** Get the "incorrect option selected message" and return screen
                 *  (with go back option) to notify the user of the issue
                 */
                return $this->showCustomGoBackScreen($this->incorrect_option_selected);

            }
        }

        //  Determine whether to remove dynamic content highlighting
        if( $this->allow_dynamic_content_highlighting == false ){

            //  Remove any HTML or PHP tags
            $builtDisplay = strip_tags($builtDisplay);

        }

        return $builtDisplay;
    }

    /*  Validate the existence of the current display. If the current display does not exist 
     *  then we return the technical difficulties screen. This screen will also cause the
     *  end of the current session since its an ending screen.
     */
    public function handleNonExistentDisplay()
    {
        //  If the linked display exists
        if (empty($this->display)) {

            //  Set a warning log that the linked display could not be found
            $this->logWarning('The linked display could not be found');

            //  Show the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();
            
        }

        return null;
    }

    /** Build the current display viewport. This means that we start
     *  building the display instruction and actions that are 
     *  required to be shown on the screen
     */
    public function buildCurrentDisplay()
    {
        //  Set an info log that we are building the display
        $this->logInfo('Building display: '.$this->wrapAsPrimaryHtml($this->display['name']));

        //  Build the display instruction
        $instructionsBuildResponse = $this->buildDisplayInstruction();

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($instructionsBuildResponse)) return $instructionsBuildResponse;

        //  Set the instruction
        $this->display_instructions = $this->convertToString($instructionsBuildResponse);

        //  Build the display actions (E.g Select options)
        $actionBuildResponse = $this->buildDisplayActions();

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($actionBuildResponse)) return $actionBuildResponse;

        //  Set the action
        $this->display_actions = $this->convertToString($actionBuildResponse);

        //  Combine the display instruction and action as the display content
        $this->display_content = $this->display_instructions.$this->display_actions;

        //  Handle the display pagination
        $outputResponse = $this->handlePagination();

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  If the display content is not empty
        if (!empty($this->display_content)) {

            //  Set an info log of the final result
            $this->logInfo('Final result: <br />'.$this->wrapAsSuccessHtml($this->display_content));
            
        }

        //  Return the display content
        return $this->showCustomScreen($this->display_content);
    }


    /** Build the current display instruction
     */
    public function buildDisplayInstruction()
    {
        //  Get the display instruction value
        $instruction = $this->display['content']['description'];

        //  Convert the instruction value into its associated dynamic value
        return $this->convertValueStructureIntoDynamicData($instruction);
    }

    /** Build the current display action e.g Static select option,
     *  dynamic select options or code select options. We first
     *  determine the type of action the display uses, then
     *  build accordinly
     */
    public function buildDisplayActions()
    {
        //  Get the current display expected action type
        $displayActionType = $this->getDisplayActionType();

        //  If the action is to select an option e.g 1, 2 or 3
        if ($displayActionType == 'select_option') {

            //  Get the current display expected select action type e.g static_options
            $displaySelectOptionType = $this->getDisplaySelectOptionType();

            //  If the select options are basic static options
            if ($displaySelectOptionType == 'static_options') {

                return $this->getStaticSelectOptions('string');

            //  If the select option are dynamic options
            } elseif ($displaySelectOptionType == 'dynamic_options') {

                return $this->getDynamicSelectOptions('string');

            //  If the select option are generated via the code editor
            } elseif ($displaySelectOptionType == 'code_editor_options') {

                return $this->getCodeSelectOptions('string');
            }
        }
    }

    /** This method gets the type of action to build for the current display
     */
    public function getDisplayActionType()
    {
        //  Available type: "no_action", "input_value" and "select_option"
        return $this->display['content']['action']['selected_type'] ?? '';
    }

    /** This method gets the type of "Select Option" action to build for the current display
     */
    public function getDisplaySelectOptionType()
    {
        //  Available type: "static_options", "dynamic_options" and "code_editor_options"
        return $this->display['content']['action']['select_option']['selected_type'] ?? '';
    }

    /** This method gets the type of "Input" action to build for the current display
     */
    public function getDisplayInputType()
    {
        //  Available type: "single_value_input" and "multi_value_input"
        return $this->display['content']['action']['input_value']['selected_type'] ?? '';
    }

    /** This method builds the static select options
     */
    public function getStaticSelectOptions($returnType = 'array')
    {
        /** Get the available static options
         *
         *  Example Structure:
         *
         *  [
         *      options => [
         */
        /**
         *          [
         *              name => [
         *                   text => '1. My Option',
         *                 code_editor_text => '',
         *                   code_editor_mode => false
         *               ],
         *               active => [
         *                   text => true,
         *                   code_editor_text => '',
         *                   code_editor_mode => false
         *               ],
         *               value => [
         *                   text => '',
         *                   code_editor_text => '',
         *                   code_editor_mode => false
         *               ],
         *               input => [
         *                   text => '1',
         *                   code_editor_text => '',
         *                   code_editor_mode => false
         *               ],
         *               separator => [
         *                   top => [
         *                       text => '',
         *                       code_editor_text => '',
         *                       code_editor_mode => false
         *                   ],
         *                   bottom => [
         *                       text => '',
         *                       code_editor_text => '',
         *                       code_editor_mode => false
         *                   ]
         *               ],
         *               link =>[
         *                   text => '',
         *                   code_editor_text => '',
         *                   code_editor_mode => false
         *               ],
         *               hexColor => '#CECECE',
         *               comment => ''
         *           ]
         */
        /**
         *      ],
         *      reference_name => '',
         *      no_results_message => [
         *           text => 'No options found',
         *           code_editor_text => '',
         *           code_editor_mode => false
         *      ],
         *      incorrect_option_selected_message => [
         *           text => 'You selected an incorrect option. Go back and try again',
         *           code_editor_text => '',
         *           code_editor_mode => false
         *      ]
         *  ]
         */
        /**
         *  Structure Definition
         *
         *  name:   Represents the display name of the option (What the user will see)
         *  value:  Represents the actual value of the option (What will be stored)
         *  link:   The screen or display to link to when this option is selected
         *  separator: The top and bottom characters to use as a separator
         *  input:  What the user must input to select this option
         */
        $options = $this->display['content']['action']['select_option']['static_options']['options'] ?? [];

        //  Get the custom "no results message"
        $no_results_message = $this->display['content']['action']['select_option']['static_options']['no_results_message'] ?? null;

        //  Check if we have options to display
        $optionsExist = count($options) ? true : false;

        //  If we have options to display
        if ($optionsExist) {

            $text = "\n";
            $collection = [];

            //  Foreach option
            for ($x = 0; $x < count($options); ++$x) {
                
                //  Get the current option
                $curr_option = $options[$x];                
                $curr_option_number = ($x + 1);
                $curr_option_name = $options[$x]['name'];
                $curr_option_link = $options[$x]['link'];
                $curr_option_value = $options[$x]['value'];
                $curr_option_input = $options[$x]['input'];
                $curr_option_active_state = $options[$x]['active'];
                $curr_option_top_separator = $options[$x]['separator']['top'];
                $curr_option_bottom_separator = $options[$x]['separator']['bottom'];

                //  Get the active state value
                $activeState = $this->processActiveState($curr_option_active_state);
        
                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($activeState)) return $activeState;

                //  If the option is active
                if ( $activeState === true ) {

                    /*************************
                     * BUILD OPTION NAME     *
                     ************************/

                    //  Convert the "option name" into its associated dynamic value
                    $outputResponse = $this->convertValueStructureIntoDynamicData($curr_option_name);

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                    //  Get the generated output
                    $option_name = $this->convertToString($outputResponse);

                    //  Set an info log of the option name
                    $this->logInfo('Option name: '.$this->wrapAsSuccessHtml($option_name));

                    /*************************
                     * BUILD OPTION LINK     *
                     ************************/

                    //  Convert the "option link" into its associated dynamic value
                    $outputResponse = $this->convertValueStructureIntoDynamicData($curr_option_link);

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                    //  Get the generated output
                    $option_link = $this->convertToString($outputResponse);

                    //  Set an info log of the option link
                    $this->logInfo('Option link: '.$this->wrapAsSuccessHtml($option_link));

                    /*************************
                     * BUILD OPTION VALUE    *
                     ************************/

                    //  Convert the "option value" into its associated dynamic value
                    $outputResponse = $this->convertValueStructureIntoDynamicData($curr_option_value);

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                    //  Get the generated output
                    $option_value = $outputResponse;

                    //  Set an info log of the option value
                    $this->logInfo('Option value: '.$this->wrapAsSuccessHtml( $this->convertToString($option_value) ));

                    /*************************
                     * BUILD OPTION INPUT    *
                     ************************/

                    //  Convert the "option input" into its associated dynamic value
                    $outputResponse = $this->convertValueStructureIntoDynamicData($curr_option_input);

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                    //  Get the generated output
                    $option_input = $this->convertToString($outputResponse);

                    //  Set an info log of the option input
                    $this->logInfo('Option input: '.$this->wrapAsSuccessHtml($option_input));

                    /*********************************
                     * BUILD OPTION TOP SEPARATOR    *
                     ********************************/

                    //  Convert the "option top separator" into its associated dynamic value
                    $outputResponse = $this->convertValueStructureIntoDynamicData($curr_option_top_separator);

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                    //  Get the generated output
                    $option_top_separator = $this->convertToString($outputResponse);

                    //  Set an info log of the option top separator
                    $this->logInfo('Option top separator: '.$this->wrapAsSuccessHtml($option_top_separator));

                    /************************************
                     * BUILD OPTION BOTTOM SEPARATOR    *
                     ***********************************/

                    //  Convert the "option bottom separator" into its associated dynamic value
                    $outputResponse = $this->convertValueStructureIntoDynamicData($curr_option_bottom_separator);

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                    //  Get the generated output
                    $option_bottom_separator = $this->convertToString($outputResponse);

                    //  Set an info log of the option top separator
                    $this->logInfo('Option bottom separator: '.$this->wrapAsSuccessHtml($option_bottom_separator));

                    /*****************
                     * ADD OPTION    *
                     *****************/

                    //  If the return type is an array format
                    if ($returnType == 'array') {

                        //  Build the option as an array
                        $option = [
                            'name' => $option_name,
                            'input' => $option_input,
                            'value' => (is_null($option_value))
                                    //  Use the entire option data as the value
                                    ? $options[$x]
                                    //  Otherwise use the converted version of the value provided
                                    : $option_value,
                            'link' => $option_link,
                            'separator' => [
                                'top' => $option_top_separator,
                                'bottom' => $option_bottom_separator,
                            ]
                        ];
    
                        //  Add the option to the rest of our options
                        array_push($collection, $option);
    
                    //  If the return type is a string format
                    } elseif ($returnType == 'string') {

                        //  If we have a top separator
                        if (!empty($option_top_separator)) {
                            
                            $text .= $option_top_separator."\n";
                        
                        }
    
                         //  If we have the option name
                         if (!empty($option_name)) {
    
                            //  Build the option as a string
                            $text .= $option_name."\n";

                        }
    
                        //  If we have a bottom separator
                        if (!empty($option_bottom_separator)) {
                            
                            $text .= $option_bottom_separator;

                        }
                    }
                }
            }

            if ($returnType == 'array') {

                //  Return the collection of options as an array
                return $collection;

            } elseif ($returnType == 'string') {

                //  Return the options as text
                return $text;

            }

            //  If we don't have options to display
        } else {

            //  If we have instructions to be displayed then add break lines
            $text = (!empty($this->display_instructions) ? "\n\n" : '');
            
            //  Convert the "no results message" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($no_results_message);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output e.g "No options available"
            $no_results_message = $outputResponse;

            //  Get the custom "no results message" otherwise use the default message
            $text .= ($no_results_message ?? $this->default_no_select_options_message);

            //  Return the custom or default "No options available"
            return $text;

        }

    }

    /** This method builds the dynamic select options
     */
    public function getDynamicSelectOptions($returnType = 'array')
    {
        /** Get the dynamic select options data
         *
         *  Example Structure:.
         *
         *  [
         *        group_reference => [
         *           text => '{{}} items }}', 
         *           code_editor_text => '',
         *           code_editor_mode => false
         *       ],
         *       template_reference_name => 'item',
         *       template_display_name => [
         *           text => '',
         *           code_editor_text => '',
         *           code_editor_mode => false
         *       ],
         *       template_value => [
         *           text => '',
         *           code_editor_text => '',
         *           code_editor_mode => false
         *       ],
         *       reference_name => 'selected_item',
         *       no_results_message => [
         *           text => 'No items found',
         *           code_editor_text => '',
         *           code_editor_mode => false
         *       ],
         *       incorrect_option_selected_message => [
         *           text => 'You selected an incorrect option. Go back and try again',
         *           code_editor_text => '',
         *           code_editor_mode => false
         *       ],
         *       link =>[
         *           text => '',
         *           code_editor_text => '',
         *           code_editor_mode => false
         *       ]
         *  ]
         */

        /*********************************
         * BUILD DYNAMIC OPTIONS DATA    *
         *********************************/

        $data_structure = $this->display['content']['action']['select_option']['dynamic_options'] ?? null;
        $group_reference = $data_structure['group_reference'] ?? null;
        $template_reference_name = $data_structure['template_reference_name'] ?? null;
        $template_display_name = $data_structure['template_display_name'] ?? null;
        $template_value = $data_structure['template_value'] ?? null;
        $link = $data_structure['link'] ?? null;

        //  Get the custom "no results message"
        $no_results_message = $data_structure['no_results_message'] ?? null;

        
        /************************************
         * BUILD DYNAMIC GROUP REFERENCE    *
         ************************************/

        //  Convert the "group reference" value into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($group_reference);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output e.g "An Array of products"
        $options = $outputResponse;

        //  Check if the dynamic options is an array
        if (!is_array($options)) {

            //  Get the options type wrapped in html tags
            $dataType = $this->wrapAsSuccessHtml( $this->getDataType($options) );

            //  Set a warning log that the dynamic property is not an array
            $this->logWarning('The dynamic options must be of type ['.$this->wrapAsSuccessHtml('Array').'] however we received type of ['.$dataType.']. For this reason we cannot build the select options');

            //  Show the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();

        }

        //  Use the try/catch handles incase we run into any possible errors
        try {

            //  Set an info log that we are starting to build the dynamic options
            $this->logInfo('Building dynamic options');

            $optionsExist = count($options);

            //  If we have options to display
            if ($optionsExist == true) {

                $text = "\n";
                $collection = [];

                /*************************
                 * BUILD OPTION LINK     *
                 ************************/

                //  Convert the "template display link" into its associated dynamic value
                $outputResponse = $this->convertValueStructureIntoDynamicData($link);

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                //  Get the generated output
                $option_link = $this->convertToString($outputResponse);

                //  Foreach option
                for ($x = 0; $x < count($options); ++$x) {
                    
                    //  Generate the option number
                    $option_number = ($x + 1);

                    /** Add the current item using our custom template reference name as additional 
                     *  dynamic data to our dynamic data storage
                     */
                    $this->storeDynamicData($template_reference_name, $options[$x]);

                    /*************************
                     * BUILD OPTION NAME     *
                     ************************/

                    //  Convert the "template display name" into its associated dynamic value
                    $outputResponse = $this->convertValueStructureIntoDynamicData($template_display_name);

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                    //  Get the generated output
                    $option_name = $this->convertToString($outputResponse);

                    //  Set an info log of the option name
                    $this->logInfo('Option name: '.$this->wrapAsSuccessHtml($option_name));

                    /*************************
                     * BUILD OPTION VALUE     *
                     ************************/

                    //  Convert the "template display value" into its associated dynamic value
                    $outputResponse = $this->convertValueStructureIntoDynamicData($template_value);

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                    //  Get the generated output
                    $option_value = $outputResponse;

                    //  Set an info log of the option value
                    $this->logInfo('Option value: '.$this->wrapAsSuccessHtml( $this->convertToString($option_value) ));

                    //  Set an info log of the option link
                    $this->logInfo('Option Link: '.$this->wrapAsSuccessHtml($option_link));
                    
                    /*****************
                     * ADD OPTION    *
                     *****************/

                    //  If the return type is an array format
                    if ($returnType == 'array') {

                        //  Build the option as an array
                        $option = [
                            'name' => $option_name,
                            'input' => $option_number,
                            'value' => (is_null($option_value))
                                    //  Use the entire option data as the value
                                    ? $options[$x]
                                    //  Otherwise use the converted version of the value provided
                                    : $option_value,
                            'link' => $option_link,
                            'separator' => [
                                'top' => null,
                                'bottom' => null,
                            ]
                        ];
    
                        //  Add the option to the rest of our options
                        array_push($collection, $option);
    
                    //  If the return type is a string format
                    } elseif ($returnType == 'string') {
                        
                        if( $option_name ){

                            //  Build the option as a string
                            $text .= $option_number.'. '.$option_name."\n";

                        }
                        
                    }
                    
                }

                if ($returnType == 'array') {

                    //  Return the collection of options as an array
                    return $collection;

                } elseif ($returnType == 'string') {

                    //  Return the options as text
                    return $text;

                }

            //  If we don't have options to display
            } else {

                //  If we have instructions to be displayed then add break lines
                $text = (!empty($this->display_instructions) ? "\n\n" : '');
                
                //  Convert the "no results message" into its associated dynamic value
                $outputResponse = $this->convertValueStructureIntoDynamicData($no_results_message);

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                //  Get the generated output e.g "No options available"
                $no_results_message = $outputResponse;

                //  Get the custom "no results message" otherwise use the default message
                $text .= ($no_results_message ?? $this->default_no_select_options_message);

                //  Return the custom or default "No options available"
                return $text;

            }

        } catch (\Throwable $e) {
            //  Handle try catch error
            return $this->handleTryCatchError($e);
        } catch (Exception $e) {
            //  Handle try catch error
            return $this->handleTryCatchError($e);
        }
    }

    /** This method builds the code select options
     */
    public function getCodeSelectOptions($returnType = 'array')
    {
        //  Get the PHP Code
        $code = $this->display['content']['action']['select_option']['code_editor_options']['code_editor_text'] ?? 'return null;';

        //  Get the custom "no results message"
        $no_results_message = $this->display['content']['action']['select_option']['code_editor_options']['no_results_message'] ?? null;

        //  Use the try/catch handles incase we run into any possible errors
        try {

            //  Set an info log that we are starting to build the dynamic options
            $this->logInfo('Building code options');

            //  Process the PHP Code
            $outputResponse = $this->processPHPCode("$code");

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the options
            $options = $outputResponse;

            if (is_array($options)) {

                //  Check if we have options to display
                $optionsExist = count($options) ? true : false;

                //  If we have options to display
                if ($optionsExist) {

                    $text = "\n";
                    $collection = [];

                    //  Foreach option
                    for ($x = 0; $x < count($options); ++$x) {

                        //  Get the current option
                        $option = $options[$x];

                        //  If the option name was not provided
                        if (!isset($option['name']) || empty($option['name'])) {

                            //  Set a warning log that the option name was not provided
                            $this->logWarning('The '.$this->wrapAsSuccessHtml('Option name').' is not provided');

                        //  If the option name is not a type of [String]
                        } elseif (!is_string($option['name'])) {

                            //  Get the option name type wrapped in html tags
                            $dataType = $this->wrapAsSuccessHtml( $option['name'] );

                            //  Set a warning log that the option name must be of type [String].
                            $this->logWarning('The given '.$this->wrapAsSuccessHtml('Option name').' must return data of type ['.$this->wrapAsSuccessHtml('String').'] or ['.$this->wrapAsSuccessHtml('Integer').'] however we received a value of type ['.$dataType.']');

                        //  If the option input was not provided
                        } elseif (!isset($option['input']) || empty($option['input'])) {

                            //  Set a warning log that the option input was not provided
                            $this->logWarning('The '.$this->wrapAsSuccessHtml('Option input').' is not provided');

                        //  If the option input is not a type of [String] or [Integer]
                        } elseif ( !( is_string($option['input']) || is_integer($option['input']) )) {

                            //  Get the option input type wrapped in html tags
                            $dataType = $this->wrapAsSuccessHtml( $option['input'] );

                            //  Set a warning log that the option name must be of type [String] or [Integer]
                            $this->logWarning('The given '.$this->wrapAsSuccessHtml('Option input').' must return data of type ['.$this->wrapAsSuccessHtml('String').'] or ['.$this->wrapAsSuccessHtml('Integer').'] however we received a value of type ['.$dataType.']');

                        //  If the option link was set but is not of type [Array]
                        } elseif (isset($option['link']) && !is_string($option['link'])) {

                            //  Get the option link type wrapped in html tags
                            $dataType = $this->wrapAsSuccessHtml( $option['link'] );

                            //  Set a warning log that the option name must be of type [String].
                            $this->logWarning('The given '.$this->wrapAsSuccessHtml('Option link').' must return data of type ['.$this->wrapAsSuccessHtml('String').'] however we received a value of type ['.$dataType.']');

                        //  If the option top separator was set but is not of type [String]
                        } elseif (isset($option['separator']['top']) && !is_string($option['separator']['top'])) {

                            //  Get the option link type wrapped in html tags
                            $dataType = $this->wrapAsSuccessHtml( $option['separator']['top'] );

                            //  Set a warning log that the option op separator must be of type [String].
                            $this->logWarning('The given '.$this->wrapAsSuccessHtml('Option top separator').' must return data of type ['.$this->wrapAsSuccessHtml('String').'] however we received a value of type ['.$dataType.']');

                        //  If the option bottom separator was set but is not of type [String]
                        } elseif (isset($option['separator']['bottom']) && !is_string($option['separator']['bottom'])) {

                            //  Get the option link type wrapped in html tags
                            $dataType = $this->wrapAsSuccessHtml( $option['separator']['bottom'] );

                            //  Set a warning log that the option op separator must be of type [String].
                            $this->logWarning('The given '.$this->wrapAsSuccessHtml('Option bottom separator').' must return data of type ['.$this->wrapAsSuccessHtml('String').'] however we received a value of type ['.$dataType.']');

                        }

                        //  Set the top separator
                        if( isset($option['separator']['top']) && !empty($option['separator']['top']) ){
                            $option_top_separator = $option['separator']['top'];
                        }else{
                            $option_top_separator = '';
                        }

                        //  Set the bottom separator
                        if( isset($option['separator']['bottom']) && !empty($option['separator']['bottom']) ){
                            $option_bottom_separator = $option['separator']['bottom'];
                        }else{
                            $option_bottom_separator = '';
                        }

                        //  If the return type is an array format
                        if ($returnType == 'array') {

                            //  Build the option as an array
                            $option = [
                                //  Get the option name
                                'name' => $this->convertToString($option['name']) ?? null,
                                //  Get the option input
                                'input' => $this->convertToString($option['input']) ?? null,
                                //  Get the option value
                                'value' => $option['value'] ?? null,
                                //  Get the option link
                                'link' => $this->convertToString($option['link']) ?? null,
                                'separator' => [
                                    'top' => $this->convertToString($option_top_separator),
                                    'bottom' => $this->convertToString($option_bottom_separator)
                                ]
                            ];

                            //  Add the option to the rest of our options
                            array_push($collection, $option);

                        //  If the return type is a string format
                        } elseif ($returnType == 'string') {

                            //  If we have a top separator
                            if (!empty($option_top_separator)) {
                                
                                $text .= $option_top_separator."\n";
                            
                            }

                            //  If we have the option name
                            if (!empty($option['name'])) {
        
                                //  Build the option as a string
                                $text .= $option['name']."\n";

                            }
        
                            //  If we have a bottom separator
                            if (!empty($option_bottom_separator)) {
                                
                                $text .= $option_bottom_separator;

                            }

                        }
                    }

                    if ($returnType == 'array') {

                        //  Return the options
                        return $collection;

                    } elseif ($returnType == 'string') {

                        //  Return the options
                        return $text;

                    }

                    //  If we don't have options to display
                } else {

                    //  If we have instructions to be displayed then add break lines
                    $text = (!empty($this->display_instructions) ? "\n\n" : '');
                    
                    //  Convert the "no results message" into its associated dynamic value
                    $outputResponse = $this->convertValueStructureIntoDynamicData($no_results_message);
    
                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;
    
                    //  Get the generated output e.g "No options available"
                    $no_results_message = $outputResponse;
    
                    //  Get the custom "no results message" otherwise use the default message
                    $text .= ($no_results_message ?? $this->default_no_select_options_message);
    
                    //  Return the custom or default "No options available"
                    return $text;
                }
            } else {

                //  Get the options type wrapped in html tags
                $dataType = $this->wrapAsSuccessHtml( $this->getDataType($options) );

                //  Set a warning log that the dynamic property is not an array
                $this->logWarning('The given '.$this->wrapAsSuccessHtml('Code').' must return data of type ['.$this->wrapAsSuccessHtml('Array').'] however we received type of ['.$dataType.']. For this reason we cannot build the select options');

                //  Show the technical difficulties error screen to notify the user of the issue
                return $this->showTechnicalDifficultiesErrorScreen();

            }
        } catch (\Throwable $e) {
            //  Handle try catch error
            return $this->handleTryCatchError($e);
        } catch (Exception $e) {
            //  Handle try catch error
            return $this->handleTryCatchError($e);
        }
    }

    /** This method collects the the current display content and 
     *  splits it into chunks that can be viewed separately
     */
    public function handlePagination()
    {
        $pagination = $this->display['content']['pagination'];

        //  Get the active state value
        $activeState = $this->processActiveState($pagination['active']);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($activeState)) return $activeState;

        //  If the pagination is active
        if ( $activeState === true ) {
            
            //  Set an info log that we are handling pagination
            $this->logInfo('Paginating display: '.$this->wrapAsPrimaryHtml($this->display['name']));

            //  Get the pagination content target
            $content_target = $pagination['content_target']['selected_type'];

            //  Get the trail for showing we have more content e.g "..."
            $paginate_by_line_breaks = $pagination['paginate_by_line_breaks'];

            //  Get the pagination separation type e.g separate by "words" or "characters"
            $separation_type = $pagination['slice']['separation_type'];

            //  Get the pagination start slice
            $start_slice = $pagination['slice']['start'];

            //  Get the pagination end slice
            $end_slice = $pagination['slice']['end'];

            //  Get the pagination show more visibility
            $show_scroll_down_text = $pagination['scroll_down']['visible'];

            //  Get the pagination show more text
            $scroll_down_name = $pagination['scroll_down']['name'];

            //  Get the pagination scroll down input
            $scroll_down_input = $pagination['scroll_down']['input'];

            //  Get the pagination show more visibility
            $show_scroll_up_text = $pagination['scroll_up']['visible'];

            //  Get the pagination show more text
            $scroll_up_name = $pagination['scroll_up']['name'];

            //  Get the pagination scroll up input
            $scroll_up_input = $pagination['scroll_up']['input'];

            //  Get the trail for showing we have more content e.g "..."
            $trailing_characters = $pagination['trailing_end'];

            //  Get the break line before trail
            $break_line_before_trail = $pagination['break_line_before_trail'];

            //  Get the break line after trail
            $break_line_after_trail = $pagination['break_line_after_trail'];

            /*****************************
             * BUILD START SLICE VALUE   *
             ****************************/

            //  Convert the "start slice" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($start_slice);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output
            $start_slice = $this->convertToInteger($outputResponse) ?? 0;

            //  Make sure the start slice is no less than 0
            $start_slice = ($start_slice < 0) ? 0 : $start_slice;

            //  Make sure the start slice is no greater than 155
            $start_slice = ($start_slice > 155) ? 155 : $start_slice;

            /***************************
             * BUILD END SLICE VALUE   *
             **************************/

            //  Convert the "end slice" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($end_slice);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output
            $end_slice = $this->convertToInteger($outputResponse) ?? 160;

            //  Make sure the end slice is no greater than 160
            $end_slice = ($end_slice > 160) ? 160 : $end_slice;

            //  Make sure the end slice is greater than the start slice
            $end_slice = ($end_slice < $start_slice) ? 160 : $end_slice;

            /*****************************
             * BUILD SCROLL DOWN NAME   *
             ****************************/

            //  Convert the "scroll down name" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($scroll_down_name);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output
            $scroll_down_name = $this->convertToString($outputResponse);

            /******************************
             * BUILD SCROLL DOWN INPUT   *
             *****************************/

            //  Convert the "scroll down input" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($scroll_down_input);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output
            $scroll_down_input = $this->convertToString($outputResponse);

            /**************************
             * BUILD SCROLL UP NAME   *
             **************************/

            //  Convert the "scroll up name" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($scroll_up_name);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output
            $scroll_up_name = $this->convertToString($outputResponse);

            /***************************
             * BUILD SCROLL UP INPUT   *
             ***************************/

            //  Convert the "scroll up input" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($scroll_up_input);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output
            $scroll_up_input = $this->convertToString($outputResponse);

            /*******************************
             * BUILD TRAILING CHARACTERS   *
             *******************************/

            //  Convert the "trailing characters" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($trailing_characters);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output
            $trailing_characters = $this->convertToString($outputResponse);

            /***************************************
             * DETERMINE THE CONTENT TO PAGINATE   *
             **************************************/

             // Paginate only the instruction
            if ($content_target == 'instruction') {

                $content = $this->display_instructions ?? '';

             // Paginate only the actions
            } elseif ($content_target == 'action') {
                
                $content = $this->display_actions ?? '';

                
            // Paginate both the instruction and actions
            } elseif ($content_target == 'both') {

                $content = $this->display_content ?? '';

            }

            /***************************************************
             * DETERMINE FIXED CONTENT AND PAGINATED CONTENT   *
             **************************************************/

            //  Get the content that must always be at the top
            $fixed_content = substr($content, 0, $start_slice);

            //  Get the rest of the content as the content to paginate
            $pagination_content = substr($content, $start_slice);

            /***********************************************
             * MERGE TRAILING CHARACTERS AND BREAK LINES   *
             **********************************************/

            //  If the break line before trail is set
            if ($break_line_before_trail) {

                //  Add a break line before the trailing characters
                $trailing_characters = "\n".$trailing_characters;
                
            }

            //  If the break line after trail is set
            if ($break_line_after_trail) {

                //  Add a break line after the trailing characters
                $trailing_characters = $trailing_characters."\n";

            }

            /**********************************
             * ADD SCROLL UP AND DOWN NAMES   *
             **********************************/

            //  If the show more text is set to be visible and its not empty
            if ($show_scroll_up_text == true && !empty($scroll_up_name)) {

                //  Combine the trail and the scroll up text e.g "..." and "88.Prev"
                $trailing_characters .= "\n".$scroll_up_name;

            }

            //  If the show scroll down text is set to be visible and its not empty
            if ($show_scroll_down_text == true && !empty($scroll_down_name)) {

                //  Combine the trail and the scroll down text e.g "..." and "99.Next"
                $trailing_characters .= "\n".$scroll_down_name;

            }

            /** Pagination by line breaks works as best as possible to avoid cutting words
             *  of select options of paragraphs of content separated by line breaks
             *  e.g If we have:
             *  ---------------------------------------
             *  Hello guys i want to make sure that we can always hang out no matter what.
             *  1. Send Message
             *  2. Edit Message
             *  3. Cancel Message
             *  ---------------------------------------
             *
             *  This will slice the content without cutting the select options or any line break.
             *  Note that the character limit in this example is 40 characters
             *
             *  Slice 1:
             *  ---------------------------------------
             *  Hello guys i want to make sure that      = 39 characters (including line-break and trailing characters)
             *  ...
             *  ---------------------------------------
             *
             *  Slice 2:
             *  ---------------------------------------
             *  we can always hang out no matter         = 36 characters (including line-break and trailing characters)
             *  ...
             *  ---------------------------------------
             *
             *  Slice 3:
             *  ---------------------------------------
             *  what                                     = 40 characters (including line-break and trailing characters)
             *  1. Send Message
             *  2. Edit Message
             *  ...
             *  ---------------------------------------
             *
             *  Slice 4:
             *  ---------------------------------------
             *  3. Cancel Message                        = 17 characters (including line-break and trailing characters)
             *  ---------------------------------------
             */

            if ($paginate_by_line_breaks) {

                /** Separate the pagination content into individual paragraphs using the line break.
                 *  This helps separate the instruction content and each select option to stand alone.
                 */
                $pagination_content_paragraphs = explode("\n", $pagination_content);

                /*  Remove empty paragraphs  */
                $pagination_content_paragraphs = collect($pagination_content_paragraphs)->filter()->values()->toArray();

                $content_groups = [];

                foreach ($pagination_content_paragraphs as $index => $pagination_content_paragraph) {

                    //  If we have another paragraph after the current one, add the trailing characters to the current paragraph
                    if (isset($pagination_content_paragraphs[$index + 1])) {

                        $pagination_content_paragraph .= $trailing_characters;

                    }

                    //  Get the content slices
                    $slices = $this->getPaginationContentSlices($pagination_content_paragraph, $trailing_characters, $start_slice, $end_slice, $separation_type);

                    array_push($content_groups, $slices);
                }

                $content_slices = [];

                //  Get the trail character length e.g "..." = 3 while "... 99.More" = 11
                $trail_length = strlen($trailing_characters);

                foreach ($content_groups as $grouped_slices) {

                    foreach ($grouped_slices as $slice) {

                        $curr_slice_length = strlen($slice);

                        //  If we don't have any content slices yet
                        if (empty($content_slices)) {

                            //  Add the first slice
                            array_push($content_slices, $slice);

                        //  If we already have content slices
                        } else {

                            //  Get the total number of slices we have
                            $total_slices = count($content_slices);

                            $last_slice = $content_slices[$total_slices - 1];

                            $last_slice_length = strlen($last_slice);

                            /** Check if its possible to get the last slice, remove the trailing characters
                             *  and add the current slice with a line break (character = 1) without exceeding
                             *  the allowed character limit ($end_slice - $start_slice).
                             */
                            if ($last_slice_length - $trail_length + $curr_slice_length + 1 <= ($end_slice - $start_slice)) {

                                //  Remove the trailing characters from the last slice
                                $last_slice_without_trail = substr($last_slice, 0, ($last_slice_length - $trail_length));

                                //  Combine the last slice without the trail with the current slice
                                $last_slice_with_current_slice = $last_slice_without_trail."\n".$slice;

                                //  Update the stored last slice
                                $content_slices[$total_slices - 1] = $last_slice_with_current_slice;

                            } else {

                                /* Add the current slice as a new slice. This slice cannot be combined with
                                 *  the previous inserted slice without exceeeding the limit), therefore it
                                 *  must be added alone.
                                 */
                                array_push($content_slices, $slice);

                            }
                        }
                    }
                }
            } else {

                //  Get the content slices
                $content_slices = $this->getPaginationContentSlices($pagination_content, $trailing_characters, $start_slice, $end_slice, $separation_type);
            
            }

            //  If we have the input
            if (!empty($scroll_down_input) || !empty($scroll_up_input)) {

                //  Start slicing the content
                while ($this->completedLevel($this->level)) {

                    $userResponse = $this->getResponseFromLevel($this->level) ?? '';   //  99

                    //  If the user response matches the pagination scroll up or scroll down input
                    if ($userResponse == $scroll_down_input || $userResponse == $scroll_up_input) {

                        if ($userResponse == $scroll_up_input) {

                            //  Set an info log that we are scrolling on the content
                            $this->logInfo('Scrolling up display: '.$this->wrapAsPrimaryHtml($this->display['name']));

                            if ($this->pagination_index > 0) {

                                //  Decrement the pagination index so that we target the previous pagination content slice
                                --$this->pagination_index;

                            }
                            
                        } elseif ($userResponse == $scroll_down_input) {

                            //  Set an info log that we are scrolling on the content
                            $this->logInfo('Scrolling down display: '.$this->wrapAsPrimaryHtml($this->display['name']));

                            //  Increment the pagination index so that next time we target the next pagination content slice
                            ++$this->pagination_index;

                        }

                        // Increment the current level so that we target the next display response
                        ++$this->level;

                    } else {

                        //  Stop the loop
                        break 1;

                    }
                }
            }

            //  Get the pagination content
            $paginated_content_slice = isset($content_slices[$this->pagination_index]) ? $content_slices[$this->pagination_index] : '';
            
            //  Set the current paginated content as the display content
            $this->display_content = $fixed_content.$paginated_content_slice;
        }
    }

    public function getPaginationContentSlices($pagination_content = '', $trailing_characters = '...', $start_slice = 0, $end_slice = 160, $separation_type = 'words')
    {
        /** To stop any potential infinite loops, lets limit the cycles to 100 loops.
         *  This means we can only loop 100 times and also means that if we have
         *  long content we can only return 100 content slices. If each content
         *  slice is 160 characters then the maximum characters to return will
         *  be (100 cycles * 160 characters) = 16,000 characters. For now this
         *  seems like a good limit to stop if the content is either too long
         *  of we are stuck in a loop that keeps repeating forever.
         */
        $cycles = 0;

        //  Set an array to store all the content slices
        $content_slices = [];

        //  Start slicing the content
        while (!empty($pagination_content) && ($cycles <= 100)) {

            if ($cycles == 100) {
                
                //  Log a warning that its possible we have an infinite loop (since its rare to reach 100 cycles)
                $this->logWarning('Possible infinite loop detected while handling pagination.');

            }

            //  Increment the cycle
            $cycles = $cycles + 1;

            //  Get the trail character length e.g "..." = 3 while "... 99.More" = 11
            $trail_length = strlen($trailing_characters);

            /* If we are separating based on characters then this means we can cut the
                *  content at any point since the user does not mind word characters being
                *  separated
                */
            if ($separation_type == 'characters') {
                /* If we slice the content and don't have any left overs (Remaining characters)
                    *  This takes care of the last paginated content. On the last paginated content
                    *  We don't add any trailing content or the show more text.
                    */
                if (empty(substr($pagination_content, $end_slice))) {
                    //  Get the content slice without the trail
                    $content_slice = substr($pagination_content, 0, $end_slice);

                    //  Update the pagination content left after slicing
                    $pagination_content = substr($pagination_content, $end_slice);

                /* If we slice the content and we have left overs (Remaining characters)
                    *  This takes care of the first paginated content and any other content
                    *  after that except the last paginated content. We add any trailing
                    *  content and the show more text if its provided.
                    */
                } else {
                    //  Get the content slice with the trail
                    $content_slice = substr($pagination_content, 0, $end_slice - $trail_length).$trailing_characters;

                    //  Update the pagination content left after slicing
                    $pagination_content = substr($pagination_content, $end_slice - $trail_length);
                }

                /* If we are separating based on words then this means we cannot cut the
                    *  content at any point since the user does mind word characters being
                    *  separated
                    */
            } elseif ($separation_type == 'words') {
                //  If the character length of the content is less than or exactly the allowed maximum limit set
                if (strlen($pagination_content) <= ($end_slice - $start_slice)) {
                    //  Get the pagination content as the current slice
                    $content_slice = $pagination_content;

                    //  Set the paginated content to nothing
                    $pagination_content = '';
                } else {
                    $content_slice = '';
                    $words = explode(' ', $pagination_content);    // string to array

                    foreach ($words as $key => $word) {
                        /** If the current content and the current word and the trailing characters and the extra
                         *  joining space " " of string length = 1 can be added without exceeding the limit then add
                         *  the word. Note that the string length for the empty space " " does not apply for the first
                         *  word added. However every other word will have the " " character when appending to the content.
                         *
                         *  This means we can add this current word now, then on the next iteration if we can't add that
                         *  following word we can finish off by adding the trailing characters since we had made room for
                         *  them on the last word that was inserted. By adding the trailing characters we indicate the
                         *  end of the maximum content  we could get for the current content slice.
                         */

                        /** If this is the first word then we dont have an empty space to add so use 0 as the string length.
                         *  However if this is not the first word then we have an empty space to add so use 1 as the string
                         *  length.
                         */
                        $empty_space_length = ($key == 0) ? 0 : 1;

                        /* We need to first make sure that the given word is not longer than the allowed character limit e.g
                            *  if the word is 200 characters long but the allowed character limit is 160 then we need to figure
                            *  out how to handle this
                            */
                        if (!(strlen($word) <= ($end_slice - $start_slice))) {
                            /** Slice the word in this way:
                             *
                             *  Get the character limit allowed by calculating:.
                             *
                             *  $limit = ($end_slice - $start_slice)
                             *
                             *  After that we need to count the content we already have using strlen( $content_slice )
                             *  We need to subtract that from the character limit since the content slice already has
                             *  content occupying space.
                             *
                             *  $limit = ($end_slice - $start_slice) - strlen( $content_slice )
                             *
                             *  Now we need to add the trailing information. This means we need to subtract that from
                             *  the character limit so that we can fit the trailing information content
                             *
                             *  $limit = ($end_slice - $start_slice) - strlen( $content_slice ) - $trail_length
                             */
                            $existing_content_length = strlen($content_slice);

                            $limit = ($end_slice - $start_slice) - $existing_content_length - $trail_length;

                            /* If this is the first word don't add the empty space but
                                *  if this is not the first word then add the empty space.
                                */
                            if ($key != 0) {
                                $word = ' '.$word;
                            }

                            //  Trim the word and add it result to the content slice
                            $content_slice .= substr($word, 0, $limit);

                            //  Add the trailing characters at the end of the result
                            $content_slice .= $trailing_characters;

                            /* Stop getting content (We will continue again on the next While Loop Iteration)
                                *  That is when we will continue reducing the extremely long word if its still
                                *  too long
                                */
                            break 1;
                        } elseif ((strlen($content_slice) + strlen($word) + $trail_length + $empty_space_length) <= ($end_slice - $start_slice)) {
                            /* If this is the first word don't add the empty space but trim the word for left and right spaces.
                                *  If this is not the first word then add the empty space.
                                */
                            if ($key == 0) {
                                $content_slice .= $word;
                            } else {
                                $content_slice .= ' '.$word;
                            }
                        } else {
                            //  Add the trailing characters after the last inserted word
                            $content_slice .= $trailing_characters;

                            //  Stop adding content
                            break 1;
                        }
                    }

                    //  Update the pagination content left after slicing
                    $pagination_content = trim(substr($pagination_content, strlen($content_slice) - $trail_length));
                }
            }

            //  Add the slice to the content slices
            array_push($content_slices, $content_slice);
        }

        //  Return the content slices
        return $content_slices;
    }

    public function resetNavigation()
    {
        $this->navigation_request_type = null;
    }
    
    public function resetPagination()
    {
        $this->pagination_index = 0;
    }

    /** This method gets the users response for the display screen if it exists otherwise
     *  returns an empty string if it does not exist. We also log an info message to
     *  indicate the display name associated with the provided response.
     */
    public function getCurrentScreenUserResponse()
    {
        $this->current_user_response = $this->getResponseFromLevel($this->level) ?? '';   //  John Doe

        //  Update the ussd data
        $this->ussd['user_response'] = $this->current_user_response;

        //  Store the ussd data using the given item reference name
        $this->storeDynamicData('ussd', $this->ussd, false);

        //  Set an info log that the user has responded to the current screen and show the input value
        $this->logInfo('User has responded to '.$this->wrapAsPrimaryHtml($this->display['name']).' with '.$this->wrapAsSuccessHtml($this->current_user_response));

        //  Return the current screen user response
        return $this->current_user_response;
    }

    /** This method gets the current display action details to determine the type of action that the
     *  display requested. We use the type of action e.g "Input a value" or "Select an option" to
     *  determine the approach we must use in order to get the value and reference name required
     *  to create dynamic data variables e.g.
     *
     *  1) Storing the input value into a variable referenced as "first_name"
     *
     *  $first_name = "John";
     *
     *  2) Storing the details of a selected option into a variable referenced as "product"
     *
     *  $product = [ "name" => "Product 1", "value" => "1", input => "1" ];
     *
     *  ... e.t.c
     *
     *  These dynamic data variables can then be reference by other displays using mustache tags
     *  e.g {{ first_name }} or {{ product.name }}
     */
    public function storeCurrentDisplayUserResponseAsDynamicVariable()
    {
        //  Get the current screen expected action type
        $screenActionType = $this->getDisplayActionType();

        //  If the action is to select an option e.g 1, 2 or 3
        if ($screenActionType == 'select_option') {

            //  Get the current screen expected select action type e.g static_options
            $screenSelectOptionType = $this->getDisplaySelectOptionType();

            //  If the select options are basic static options
            if ($screenSelectOptionType == 'static_options') {

                return $this->storeSelectedStaticOptionAsDynamicData();

            //  If the select option are dynamic options
            } elseif ($screenSelectOptionType == 'dynamic_options') {

                return $this->storeSelectedDynamicOptionAsDynamicData();

            //  If the select option are generated via the code editor
            } elseif ($screenSelectOptionType == 'code_editor_options') {

                return $this->storeSelectedCodeOptionAsDynamicData();

            }

            //  If the action is to input a value e.g John
        } elseif ($screenActionType == 'input_value') {

            //  Get the current screen expected input action type e.g input_value
            $screenInputType = $this->getDisplayInputType();

            /** If the input is a single value input e.g
             *  Q: Enter your first name
             *  Ans: John
            */
            if ($screenInputType == 'single_value_input') {
                return $this->storeSingleValueInputAsDynamicData();

            /** If the input is a multi-value input e.g
             *  Q: Enter your first name, last name and age separated by spaces
             *  Ans: John Doe 25
            */
            } elseif ($screenInputType == 'multi_value_input') {
                return $this->storeMultiValueInputAsDynamicData();
            }

        }
    }

    /** This method gets the value from the selected static option and stores it within the
     *  specified reference variable if provided. It also determines if the next display or
     *  screen link has been provided, if (yes) we fetch the specified display or screen
     *  and save it for linking in future methods.
     */
    public function storeSelectedStaticOptionAsDynamicData()
    {
        $outputResponse = $this->getStaticSelectOptions('array');

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the options
        $options = $outputResponse;

        $staticOptions = $this->display['content']['action']['select_option']['static_options'];

        //  Get the reference name (The name used to store the selected option value for ease of referencing)
        $reference_name = $staticOptions['reference_name'] ?? null;

        //  Get the custom "no results message"
        $no_results_message = $staticOptions['no_results_message'] ?? null;

        //  Get the custom "incorrect option selected message"
        $incorrect_option_selected_message = $staticOptions['incorrect_option_selected_message'] ?? null;

        return $this->storeSelectedOption($options, $reference_name, $no_results_message, $incorrect_option_selected_message);
    }

    /** This method gets the value from the selected dynamic option and stores it within the
     *  specified reference variable if provided. It also determines if the next display or
     *  screen link has been provided, if (yes) we fetch the specified display or screen
     *  and save it for linking in future methods.
     */
    public function storeSelectedDynamicOptionAsDynamicData()
    {
        $outputResponse = $this->getDynamicSelectOptions('array');

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the options
        $options = $outputResponse;

        $dynamicOptions = $this->display['content']['action']['select_option']['dynamic_options'];

        //  Get the reference name (The name used to store the selected option value for ease of referencing)
        $reference_name = $dynamicOptions['reference_name'] ?? null;

        //  Get the custom "no results message"
        $no_results_message = $dynamicOptions['no_results_message'] ?? null;

        //  Get the custom "incorrect option selected message"
        $incorrect_option_selected_message = $dynamicOptions['incorrect_option_selected_message'] ?? null;

        return $this->storeSelectedOption($options, $reference_name, $no_results_message, $incorrect_option_selected_message);
    }

    /** This method gets the value from the selected code option and stores it within the
     *  specified reference variable if provided. It also determines if the next display or
     *  screen link has been provided, if (yes) we fetch the specified display or screen
     *  and save it for linking in future methods.
     */
    public function storeSelectedCodeOptionAsDynamicData()
    {
        $outputResponse = $this->getCodeSelectOptions('array');

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the options
        $options = $outputResponse;

        $codeOptions = $this->display['content']['action']['select_option']['code_editor_options'];

        //  Get the reference name (The name used to store the selected option value for ease of referencing)
        $reference_name = $codeOptions['reference_name'] ?? null;

        //  Get the custom "no results message"
        $no_results_message = $codeOptions['no_results_message'] ?? null;

        //  Get the custom "incorrect option selected message"
        $incorrect_option_selected_message = $codeOptions['incorrect_option_selected_message'] ?? null;

        return $this->storeSelectedOption($options, $reference_name, $no_results_message, $incorrect_option_selected_message);
    }

    public function storeSelectedOption($options = [], $reference_name = null, $no_results_message = null, $incorrect_option_selected_message = null)
    {
        /** $options represents a set of action options
         *
         *  Example Structure:.
         *
         *  [
         *      [
         *          "name": "1. My Messages ({{ messages.total }})",
         *          "value" => [ ... ],
         *          "input" => "1"
         *          "link" => "screen_1592486781723"
         *      ],
         *      ...
         *  ]
         *
         *  Structure Definition
         *
         *  name:   Represents the display name of the option (What the user will see)
         *  value:  Represents the actual value of the option (What will be stored)
         *  link:   The screen or display to link to when this option is selected
         *  input:  What the user must input to select this option
         */

        //  Check if we have options to display
        $optionsExist = count($options) ? true : false;

        //  Get option matching user response
        $selectedOption = collect(array_filter($options, function ($option) {

            //  If the user response matches the option's input
            return $this->current_user_response == $option['input'];

        }))->first() ?? null;

        //  If we have options to display
        if ($optionsExist) {

            //  If the user selected an option that exists
            if (!empty($selectedOption)) {

                //  Get the selected option link (The display or screen we must link to after the user selects this option)
                $link = $selectedOption['link'] ?? null;

                //  Setup the link for the next display or screen
                $this->setupLink($link);

                //  If we have the reference name provided
                if ( !empty($reference_name) ) {

                    //  Get the option value only
                    $dynamic_data = $selectedOption['value'];

                    //  Store the select option as dynamic data
                    $this->storeDynamicData($reference_name, $dynamic_data);

                }

            //  If the user did not select an option that exists
            } else {
                
                //  Convert the "incorrect option selected message" into its associated dynamic value
                $outputResponse = $this->convertValueStructureIntoDynamicData($incorrect_option_selected_message);

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                //  Get the generated output e.g "You selected an incorrect option" otherwise use the default message
                $this->incorrect_option_selected = $outputResponse ?? $this->default_incorrect_option_selected_message;

            }

        //  If we don't have options to display
        } else {

            //  If we have instructions to be displayed then add break lines
            $text = (!empty($this->display_instructions) ? "\n\n" : '');
            
            //  Convert the "no results message" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($no_results_message);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output e.g "No options available"
            $no_results_message = $outputResponse;

            //  Get the custom "no results message" otherwise use the default message
            $text .= ($no_results_message ?? $this->default_no_select_options_message);

            //  Return the custom or default "No options available"
            return $text;
            
        }
    }

    /** This method gets the single value from the input and stores it within the specified
     *  reference variable if provided. It also determines if the next screen has been 
     *  provided, if (yes) we fetch the specified screen and save it as a screen that 
     *  we must link to in future.
     */
    public function storeSingleValueInputAsDynamicData()
    {
        //  Get the users current response
        $user_response = $this->current_user_response;

        //  Get the reference name (The name used to store the input value for ease of referencing)
        $reference_name = $this->display['content']['action']['input_value']['single_value_input']['reference_name'] ?? null;

        //  Get the single input link (The display or screen we must link to after the user inputs a value)
        $link = $this->display['content']['action']['input_value']['single_value_input']['link'] ?? null;

        /******************
         * SETUP LINK     *
         ******************/

        //  Setup the link for the next display or screen
        $this->setupLink($link);

        //  If we have the reference name provided
        if (!empty($reference_name)) {

            //  Store the input value as dynamic data
            $this->storeDynamicData($reference_name, $user_response);

        }
    }

    /** This method gets the multiple values from the input and stores them within the specified
     *  reference variables if provided. It also determines if the next screen has been provided,
     *  if (yes) we fetch the specified screen and save it as a screen that we must link to in 
     *  future.
     */
    public function storeMultiValueInputAsDynamicData()
    {
        /** Get the users current response. This represents a string of multiple inputs
         *
         *  Example: "John Doe 24".
         */
        //  Get the users current response
        $user_response = $this->current_user_response;

        /** Get the reference names (The names used to store the input values for ease of referencing) e.g
         *
         *  Example: ['first_name', 'last_name', 'age'].
         */
        $reference_names = $this->display['content']['action']['input_value']['multi_value_input']['reference_names'] ?? [];

        /** Get the separator (The character used to separate the user input values).
         *  Default to spaces if not set.
         *
         *  Example: ","
         *
         *  Default: " "
         */
        $separator = $this->display['content']['action']['input_value']['multi_value_input']['separator'] ?? ' ';
        $separator = 'spaces' ? ' ' : $separator;

        //  Get the multi input link (The display or screen we must link to after the user inputs a value)
        $link = $this->display['content']['action']['input_value']['multi_value_input']['link'] ?? null;

        /******************
         * SETUP LINK     *
         ******************/

        //  Setup the link for the next display or screen
        $this->setupLink($link);

        //  If we have the reference names provided
        if (!empty($reference_names)) {

            //  Separate the multiple user responses using the separator
            $user_responses = explode($separator, $user_response);

            // Foreach ['first_name', 'last_name', 'age']
            foreach ($reference_names as $key => $reference_name) {

                // Check if the current reference name has a corresponding user response value
                if (isset($user_responses[$key])) {

                    //  Get the provided response value e.g John
                    $user_response = $user_responses[$key];

                } else {

                    //  Default to an empty string
                    $user_response = '';

                }

                //  Store the input value as dynamic data
                $this->storeDynamicData($reference_name, $user_response);
            }

        }
    }

    /** This method will find the screen or display that matches the
     *  link given and sets it for later access
    */
    public function setupLink($link = null)
    {
        //  If the link provided is in Array format
        if( is_array($link) ){

            //  Convert the "step" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($link);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the processed link value - Convert to [String] - Default to empty string if anything goes wrong
            $link = $this->convertToString($outputResponse) ?? '';

        }

        //  If we have a link
        if ( !empty($link) ) {

            //  Return True/False if the first characters match the value "screen"
            $isScreen = (substr($link, 0, 6) == 'screen') ? true : false;

            //  Return True/False if the first characters match the value "display"
            $isDisplay = (substr($link, 0, 7) == 'display') ? true : false;

            //  If we should link to a display
            if ( $isDisplay ) {

                //  Get the screen matching the given name and set it as the linked screen
                $this->linked_display = $this->getDisplayById($link);

            //  If we should link to a screen
            } elseif ( $isScreen ) {

                //  Get the screen matching the given name and set it as the linked screen
                $this->linked_screen = $this->getScreenById($link);

            }

        }
    }

    /** This method returns a display if it exists by searching based on
     *  the display name provided
     */
    public function getDisplayById($link = null)
    {
        //  If the link provided is in Array format
        if( is_array($link) ){

            //  Convert the "step" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($link);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the processed link value - Convert to [String] - Default to empty string if anything goes wrong
            $link = $this->convertToString($outputResponse) ?? '';

        }

        //  If the display name has been provided
        if (!empty($link)) {

            //  Get the first display that matches the given link
            return collect($this->screen['displays'])->where('id', $link)->first() ?? null;

        }
    }

    /** This method returns a screen if it exists by searching based on
     *  the screen name provided
     */
    public function getScreenById($link = null)
    {
        //  If the link provided is in Array format
        if( is_array($link) ){

            //  Convert the "step" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($link);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the processed link value - Convert to [String] - Default to empty string if anything goes wrong
            $link = $this->convertToString($outputResponse) ?? '';
            
        }

        //  If the screen name has been provided
        if ($link) {

            //  Get the first screen that matches the given link
            return collect($this->screens)->where('id', $link)->first() ?? null;

        }
    }

    public function handleNavigation($type)
    {
        //  If the screen is set to repeats
        if ( $this->screen_repeats === true ) {

            //  Set an info log that we are checking if the display can navigate forward
            $this->logInfo('Checking if '.$this->wrapAsPrimaryHtml( $this->display['name'] ).' can navigate '.$type);

            if($type == 'forward'){

                $navigations = $this->display['content']['screen_repeat_navigation']['forward_navigation'];

            }elseif($type == 'backward'){

                $navigations = $this->display['content']['screen_repeat_navigation']['backward_navigation'];

            }

            foreach ($navigations as $navigation) {

                //  Get the navigation step settings
                $step = $navigation['custom']['step'];

                /******************
                 * BUILD STEP     *
                 ******************/

                //  Convert the "step" into its associated dynamic value
                $outputResponse = $this->convertValueStructureIntoDynamicData($step);

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                //  Get the processed step value (Convert from [String] to [Number]) - Default to 1 if anything goes wrong
                $step_number = $this->convertToInteger($outputResponse) ?? 1;
                
                //  If the processed navigation step number is not an integer or a number greater than 1
                if (!is_integer($step_number) || !($step_number >= 1)) {

                    //  Set an warning log that the step number must be of type array.
                    if (!is_integer($step_number)) {

                        //  Get the step type wrapped in html tags
                        $dataType = $this->wrapAsSuccessHtml( $this->getDataType($step_number) );
    
                        //  Set a warning log that the dynamic property is not an array
                        $this->logWarning('The given '.$type.' navigation step number must be of type ['.$this->wrapAsSuccessHtml('Array').'] however we received type of ['.$dataType.'].');
    
                    }

                    if ( !($step_number >= 1) ) {
                        
                        $this->logWarning('The given '.$type.' navigation step number equals ['.$this->wrapAsSuccessHtml($step_number).']. The expected value must equal ['.$this->wrapAsSuccessHtml('1').'] or an integer greater than ['.$this->wrapAsSuccessHtml('1').'].For this reason we will use the default value of ['.$this->wrapAsSuccessHtml('1').']');
                    
                    }

                    //  Default the navigation step number to 1
                    $this->navigation_step_number = 1;

                } else {

                    $this->navigation_step_number = $step_number;

                }

                if ( $navigation['selected_type'] == 'custom' ) {

                    //  Set an info log that we are checking if the display can navigate
                    $this->logInfo($this->wrapAsSuccessHtml($this->display['name']).' supports custom '.$type.' navigation');

                    //  Get the custom inputs e.g "1, 2, 3"
                    $inputs = $navigation['custom']['inputs'];

                    /********************
                     * BUILD INPUTS     *
                     *******************/

                    //  Convert the "inputs" into its associated dynamic value
                    $outputResponse = $this->convertValueStructureIntoDynamicData($inputs);

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                    //  Get the processed step value (Convert from [String] to [Number]) - Default to 1 if anything goes wrong
                    $inputs = $this->convertToString($outputResponse);

                    //  If we have inputs
                    if (!empty($inputs)) {

                        //  Seprate the inputs by comma ","
                        $valid_inputs = explode(',', $inputs);

                        foreach ($valid_inputs as $key => $input) {

                            //  Make sure each input has no left and right spaces
                            $valid_inputs[$key] = trim($input);

                        }

                        if( count($valid_inputs) == 1 ){
                        
                            $this->logInfo('The user input must match the following value '.$this->wrapAsPrimaryHtml( implode(', ', $valid_inputs) ).' to navigate '.$type);

                        }else{
                            
                            $this->logInfo('The user input must match any of the the following values '.$this->wrapAsPrimaryHtml( implode(', ', $valid_inputs) ).' to navigate '.$type);

                        }

                        //  If the user response matches any valid navigation input
                        if (in_array($this->current_user_response, $valid_inputs)) { 


                            if( count($valid_inputs) == 1 ){
                        
                                $this->logInfo('The user input '.$this->wrapAsPrimaryHtml($this->current_user_response).' matched the following value '.$this->wrapAsPrimaryHtml( implode(', ', $valid_inputs) ));
    
                            }else{

                                $this->logInfo('The user input '.$this->wrapAsPrimaryHtml($this->current_user_response).' matched one of the following values '.$this->wrapAsPrimaryHtml( implode(', ', $valid_inputs) ));
    
                            }
                            
                            //  Set an info log that user response has been allowed for navigation
                            $this->logInfo($this->wrapAsSuccessHtml($this->display['name']).' user response allowed for '.$type.' navigation');
                            
                            /***************************************
                             * SET NAVIGAITON TARGET SCREEN ID     *
                             **************************************/
                            $link = $navigation['custom']['link'];

                            //  Process the link and return the matching screen
                            $outputResponse = $this->getScreenById($link);
            
                            //  If we have a screen to show return the response otherwise continue
                            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                            $this->navigation_target_screen_id = ($outputResponse['id'] ?? null);

                            /* Increment the current level so that we target the next repeat display
                             *  (This means we are targeting the same display but different instance)
                             */
                            ++$this->level;

                            if($type == 'forward'){

                                /* Return an indication that we want to navigate forward (i.e Go to the next iteration)
                                 *
                                 *  Refer to: startRepeatScreen()
                                 *
                                 */
                                $this->navigation_request_type = 'navigate-forward';
                
                            }elseif($type == 'backward'){

                                /* Return an indication that we want to navigate backward (i.e Go to the previous iteration)
                                 *
                                 *  Refer to: startRepeatScreen()
                                 *
                                 */
                                $this->navigation_request_type = 'navigate-backward';

                            }

                        }else{

                            if( count($valid_inputs) == 1 ){
                                  
                                $this->logInfo('Cannot navigate '.$type.' since the user input '.$this->wrapAsPrimaryHtml($this->current_user_response).' does not match the following value '.$this->wrapAsPrimaryHtml( implode(', ', $valid_inputs) ));
    
                            }else{
                                  
                                $this->logInfo('Cannot navigate '.$type.' since the user input '.$this->wrapAsPrimaryHtml($this->current_user_response).' does not match any of the following values '.$this->wrapAsPrimaryHtml( implode(', ', $valid_inputs) ));
    
                            }
                        }
                    }
                }
            }
        }
    }

    public function resetIncorrectOptionSelected()
    {
        $this->incorrect_option_selected = null;
    }

    public function handleLinkingDisplay()
    {
        //  Check if the current display must link to another display or screen
        if ($this->checkIfDisplayMustLink()) {

            /** Increment the current level so that we target the next screen or display 
             *  (This means we are targeting the linked screen)
             */
            ++$this->level;

            //  If we have a display we can link to
            if (!empty($this->linked_display)) {

                //  Set the linked display as the current display
                $this->display = $this->linked_display;

                //  Reset the linked display to nothing
                $this->linked_display = null;

                //  Handle the current display (This means we are handling the linked display)
                return $this->handleCurrentDisplay();

            //  If we have a screen we can link to
            } elseif (!empty($this->linked_screen)) {

                //  Add the current screen to the list of chained screens
                array_push($this->chained_screens, $this->screen);

                //  Set the linked screen as the current screen
                $this->screen = $this->linked_screen;

                //  Reset the linked screen to nothing
                $this->linked_screen = null;

                //  Handle the current screen (This means we are handling the linked screen)
                $response = $this->handleCurrentScreen();

                return $response;

            }
        }
    }

    /** This method checks if the current display has a screen or display 
     *  it can link to. If (yes) we return true, if (no) we return false.
     */
    public function checkIfDisplayMustLink()
    {
        //  If we have a display or screen we can link to
        if (!empty($this->linked_display) || !empty($this->linked_screen)) {

            //  Return true to indicate that we must link to another display or screen
            return true;

        }

        //  Return false to indicate that we must not link to another screen
        return false;
    }




    /******************************************
     *  REPEAT EVENT METHODS                *
     *****************************************/

    public function handleBeforeRepeatEvents()
    {
        //  Check if the screen has before repeat events
        if (count($this->screen['repeat']['events']['before_repeat'])) {

            //  Get the events to handle
            $events = $this->screen['repeat']['events']['before_repeat'];

            //  Set an info log that the current screen has before repeat events
            $this->logInfo($this->wrapAsPrimaryHtml( $this->screen['name'] ).' has '.$this->wrapAsSuccessHtml( count($events) ).') before repeat events');

            //  Start handling the given events
            return $this->handleEvents($events);

        } else {

            //  Set an info log that the current screen does not have before repeat events
            $this->logInfo($this->wrapAsPrimaryHtml( $this->screen['name'] ).' does not have before repeat events.');

            return null;

        }
    }

    public function handleAfterRepeatEvents()
    {
        //  Check if the screen has after repeat events
        if (count($this->screen['repeat']['events']['after_repeat'])) {

            //  Get the events to handle
            $events = $this->screen['repeat']['events']['after_repeat'];

            //  Set an info log that the current screen has after repeat events
            $this->logInfo($this->wrapAsPrimaryHtml( $this->screen['name'] ).' has '.$this->wrapAsSuccessHtml( count($events) ).') after repeat events');

            //  Start handling the given events
            return $this->handleEvents($events);

        } else {

            //  Set an info log that the current screen does not have after repeat events
            $this->logInfo($this->wrapAsPrimaryHtml( $this->screen['name'] ).' does not have after repeat events.');

            return null;

        }
    }

    /******************************************
     *  DISPLAY EVENT METHODS                *
     *****************************************/

    public function handleBeforeResponseEvents()
    {
        //  Check if the display has before user response events
        if (count($this->display['content']['events']['before_reply'])) {

            //  Get the events to handle
            $events = $this->display['content']['events']['before_reply'];

            //  Set an info log that the current screen has before user response events
            $this->logInfo('Display '.$this->wrapAsPrimaryHtml($this->display['name']).' has ('.$this->wrapAsSuccessHtml( count($events) ).') before user response events.');

            //  Start handling the given events
            return $this->handleEvents($events);
            
        } else {
            //  Set an info log that the current display does not have before user response events
            $this->logInfo('Display '.$this->wrapAsPrimaryHtml($this->display['name']).' does not have before user response events.');

            return null;
        }
    }

    public function handleAfterResponseEvents()
    {
        //  Check if the display has after user response events
        if (count($this->display['content']['events']['after_reply'])) {

            //  Get the events to handle
            $events = $this->display['content']['events']['after_reply'];

            //  Set an info log that the current screen has after user response events
            $this->logInfo('Display '.$this->wrapAsPrimaryHtml($this->display['name']).' has (<span class="text-success">'.count($events).'</span>) after user response events.');

            //  Start handling the given events
            return $this->handleEvents($events);

        } else {

            //  Set an info log that the current display does not have after user response events
            $this->logInfo('Display '.$this->wrapAsPrimaryHtml($this->display['name']).' does not have after user response events.');

            return null;

        }
    }

    /******************************************
     *  EVENT METHODS                         *
     *****************************************/

    public function handleEvents($events = [])
    {
        //  If we have events to handle
        if (count($events)) {

            //  Foreach event
            foreach ($events as $event) {

                //  Handle the current event
                $handleEventResponse = $this->handleEvent($event);

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($handleEventResponse)) {

                    //  Set an info log that the current event wants to display information
                    $this->logInfo('Event: '.$this->wrapAsSuccessHtml($event['name']).', wants to display information, we are not running any other events or processes, instead we will return information to display.');

                    //  Return the screen information
                    return $handleEventResponse;

                }
                
            }

        }
    }

    public function handleEvent($event = null)
    {
        //  Get the active state value
        $activeState = $this->processActiveState($event['active']);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($activeState)) return $activeState;

        //  If the pagination is active
        if ( $activeState === true ) {

            //  Set an info log that we are preparing to handle the given event
            $this->logInfo('Display: '.$this->wrapAsSuccessHtml($this->display['name']).' preparing to handle the '.$this->wrapAsSuccessHtml($event['name']).' event');

            //  Get the current event
            $this->event = $event;

            if ($event['type'] == 'CRUD API') {
                return $this->handle_CRUD_API_Event();
            } elseif ($event['type'] == 'SMS API') {
                return $this->handle_SMS_API_Event();
            } elseif ($event['type'] == 'Email API') {
                return $this->handle_Email_API_Event();
            } elseif ($event['type'] == 'Location API') {
                return $this->handle_Location_API_Event();
            } elseif ($event['type'] == 'Billing API') {
                return $this->handle_Billing_API_Event();
            } elseif ($event['type'] == 'Subcription API') {
                return $this->handle_Subcription_API_Event();
            } elseif ($event['type'] == 'Validation') {
                return $this->handle_Validation_Event();
            } elseif ($event['type'] == 'Formatting') {
                return $this->handle_Formatting_Event();
            } elseif ($event['type'] == 'Local Storage') {
                return $this->handle_Local_Storage_Event();
            } elseif ($event['type'] == 'Custom Code') {
                return $this->handle_Custom_Code_Event();
            } elseif ($event['type'] == 'Revisit') {
                return $this->handle_Revisit_Event();
            } elseif ($event['type'] == 'Redirect') {
                return $this->handle_Redirect_Event();
            } elseif ($event['type'] == 'Create/Update Account') {
                return $this->handle_Create_Or_Update_Account_Event();
            }

        } else {
            
            //  Set an info log that the current event is not activated
            $this->logInfo('Event: '.$this->wrapAsSuccessHtml($event['name']).' is not activated, therefore will not be executed.');

        }
    }

    /******************************************
     *  CRUD API EVENT METHODS                *
     *****************************************/
    public function handle_CRUD_API_Event()
    {
        if ($this->event) {
            
            /** Run the CRUD API Call. This will render as: $this->get_CRUD_Api_URL()
             *  while being called within a try/catch handler
             */
            $apiCallResponse = $this->tryCatch('run_CRUD_Api_Call');

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($apiCallResponse)) return $apiCallResponse;

            return $this->handle_CRUD_Api_Response($apiCallResponse);
            
        }
    }

    public function run_CRUD_Api_Call()
    {
        /** Set the CRUD API URL. This will render as: $this->get_CRUD_Api_URL()
         *  while being called within a try/catch handler
         */
        $url = $this->tryCatch('get_CRUD_Api_URL');

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($url)) return $url;

        /** Set the CRUD API METHOD. This will render as: $this->get_CRUD_Api_Method()
         *  while being called within a try/catch handler
         */
        $method = $this->tryCatch('get_CRUD_Api_Method');

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($method)) return $method;

        /** Set the CRUD API HEADERS. This will render as: $this->get_CRUD_Api_Headers()
         *  while being called within a try/catch handler
         */
        $headers = $this->tryCatch('get_CRUD_Api_Headers');

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($headers)) return $headers;

        /** Set the CRUD API FORM DATA. This will render as: $this->get_CRUD_Api_Form_Data()
         *  while being called within a try/catch handler
         */
        $form_data = $this->tryCatch('get_CRUD_Api_Form_Data');

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($form_data)) return $form_data;

        /** Set the CRUD API QUERY PARAMS. This will render as: $this->get_CRUD_Api_Query_Params()
         *  while being called within a try/catch handler
         */
        $query_params = $this->tryCatch('get_CRUD_Api_Query_Params');

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($query_params)) return $query_params;

        $request_options = [];

        //  Check if the CRUD Url and Method has been provided
        if (empty($url) || empty($method)) {

            //  Check if the CRUD Url has been provided
            if (empty($url)) {

                //  Set a warning log that the CRUD API Url was not provided
                $this->logWarning('API Url was not provided');

                //  Show the technical difficulties error screen to notify the user of the issue
                return $this->showTechnicalDifficultiesErrorScreen();

            }

            //  Check if the CRUD Method has been provided
            if (empty($method)) {

                //  Set a warning log that the CRUD API Method was not provided
                $this->logWarning('API Method was not provided');

                //  Show the technical difficulties error screen to notify the user of the issue
                return $this->showTechnicalDifficultiesErrorScreen();
                
            }
        } else {

            //  Set an info log of the CRUD API Url provided
            $this->logInfo('API Url: '.$this->wrapAsSuccessHtml($url));

            //  Set an info log of the CRUD API Method provided
            $this->logInfo('API Method: '.$this->wrapAsSuccessHtml(strtoupper($method)));

        }

        //  Check if the provided url is correct
        if (!$this->isValidUrl($url)) {

            //  Set a warning log that the CRUD API Url provided is incorrect
            $this->logWarning('API Url provided is incorrect ('.$this->wrapAsSuccessHtml($url).')');

            //  Show the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();

        }

        //  If we have the headers
        if (!empty($headers) && is_array($headers)) {

            //  Add the headers to the headers attribute of our API options
            $request_options['headers'] = $headers;

            foreach ($headers as $key => $value) {

                //  Set an info log of the CRUD API header attribute
                $this->logInfo('Headers: '.$this->wrapAsSuccessHtml($key).' = '.$this->wrapAsSuccessHtml($value));
                
            }

        }

        //  If we have the query params
        if (!empty($query_params) && is_array($query_params)) {

            //  Add the query params to the query attribute of our API options
            $request_options['query'] = $query_params;

            foreach ($query_params as $key => $value) {

                //  Set an info log of the CRUD API query param attribute
                $this->logInfo('Query Params: '.$this->wrapAsSuccessHtml($key).' = '.$this->wrapAsSuccessHtml($value));

            }

        }

        //  If we have the form data
        if (!empty($form_data)) {
            
            $convert_to_json_object = $this->event['event_data']['form_data']['convert_to_json'];
    
            //  If we should convert the data to a JSON Object
            if( $convert_to_json_object ){

                //  Add the form data to the json attribute of our API options
                $request_options['json'] = $form_data;
    
            }else{

                //  Add the form data to the form_params attribute of our API options
                $request_options['form_params'] = $form_data;

            }
            
            //  Set an info log of the CRUD API form data attribute
            $this->logInfo('Form Data: '.$this->wrapAsSuccessHtml( $this->convertToString($form_data)) );

        }

        //  Create a new Http Guzzle Client
        $httpClient = new \GuzzleHttp\Client();

        try {

            //  Set an info log that we are performing CRUD API call
            $this->logInfo('Run API call to: '.$this->wrapAsSuccessHtml($url));

            //  Perform and return the Http request
            return $httpClient->request($method, $url, $request_options);

            /* About guzzle errors
             *
             *  GuzzleHttp\Exception\ClientException for 400-level errors
             *  GuzzleHttp\Exception\ServerException for 500-level errors
             *  GuzzleHttp\Exception\BadResponseException for both (it's their superclass)
             *
             *  Read More = http://docs.guzzlephp.org/en/latest/quickstart.html#exceptions
             */
        } catch (\GuzzleHttp\Exception\RequestException $e) {

            //  Set a warning log that the Api call failed
            $this->logWarning('Api call to '.$this->wrapAsSuccessHtml($url).' failed.');

            if ($e->getMessage()) {
                
                $this->logWarning($this->wrapAsErrorHtml($e->getMessage()));

            }

            /*
             * Here we actually catch the instance of GuzzleHttp\Psr7\Response
             * (find it in ./vendor/guzzlehttp/psr7/src/Response.php) with all
             * its own and its 'Message' trait's methods.
             *
             * So now we have: HTTP status code, message, headers and body.
             * Just check the exception object has the response before.
             * running any methods on it.
             */
            if ($e->hasResponse()) {

                //  Return the failed response from the current exception object
                return $e->getResponse();

            //  Incase we fail to get the response object
            } else {

                //  Handle try catch error
                return $this->handleTryCatchError($e);

            }

            //  Just incase we failed to catch RequestException
        } catch (\Throwable $e) {

            //  Set a warning log that the Api call failed
            $this->logWarning('Api call to '.$this->wrapAsSuccessHtml($url).' failed.');

            //  Handle try catch error
            return $this->handleTryCatchError($e);

            //  Just incase we failed to catch RequestException and Throwable
        } catch (Exception $e) {

            //  Set a warning log that the Api call failed
            $this->logWarning('Api call to '.$this->wrapAsSuccessHtml($url).' failed.');

            //  Handle try catch error
            return $this->handleTryCatchError($e);

        }
    }

    public function get_CRUD_Api_URL()
    {
        $url = $this->event['event_data']['url'] ?? null;

        if ($url) {

            //  Convert the "url" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($url);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output
            $url = $this->convertToString($outputResponse);
            
        }

        return $url;
    }

    public function get_CRUD_Api_Method()
    {
        $method = $this->event['event_data']['method'] ?? null;

        return $method;
    }

    public function get_CRUD_Api_Headers()
    {
        $headers = $this->event['event_data']['headers'] ?? [];

        $data = [];

        foreach ($headers as $header) {

            if (!empty($header['name'])) {

                //  Convert the "header value" into its associated dynamic value
                $outputResponse = $this->convertValueStructureIntoDynamicData($header['value']);
    
                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                //  Get the generated output
                $value = $this->convertToString($outputResponse);

                $data[$header['name']] = $value;

            }
        }

        return $data;
    }

    public function get_CRUD_Api_Form_Data()
    {
        $use_code = $this->event['event_data']['form_data']['use_custom_code'];
        $convert_to_json_object = $this->event['event_data']['form_data']['convert_to_json'];

        if( $use_code ){

            $code = $this->event['event_data']['form_data']['code'];
    
            //  Process the PHP Code
            $outputResponse = $this->processPHPCode("$code");
    
            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;
    
            $data = $outputResponse;

        }else{

            $data = [];

            $form_data_params = $this->event['event_data']['form_data']['params'] ?? [];
            
            if( count($form_data_params) ){

                foreach ($form_data_params as $form_item) {
        
                    if (!empty($form_item['name'])) {
        
                        //  Convert the "form_item value" into its associated dynamic value
                        $outputResponse = $this->convertValueStructureIntoDynamicData($form_item['value']);
            
                        //  If we have a screen to show return the response otherwise continue
                        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;
        
                        //  Get the generated output
                        $value = $outputResponse;
        
                        $data[$form_item['name']] = $value;
        
                    }
                }

            }

        }
        
        return $data;
    }

    public function get_CRUD_Api_Query_Params()
    {
        $query_params = $this->event['event_data']['query_params'] ?? [];

        $data = [];

        foreach ($query_params as $query_param) {

            if (!empty($query_param['name'])) {

                //  Convert the "query_param value" into its associated dynamic value
                $outputResponse = $this->convertValueStructureIntoDynamicData($query_param['value']);
    
                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                //  Get the generated output
                $value = $this->convertToString($outputResponse);

                $data[$query_param['name']] = $value;

            }
        }
        
        return $data;
    }

    public function get_CRUD_Api_Status_Handles()
    {
        $response_status_handles = $this->event['event_data']['response']['manual']['response_status_handles'] ?? [];

        return $response_status_handles;
    }

    public function isValidUrl($url = '')
    {
        return filter_var($url, FILTER_VALIDATE_URL) ? true : false;
    }

    public function handle_CRUD_Api_Response($response = null)
    {
        if ($response) {

            /** Get the CRUD API return type. We use the return type to determine how we
             *  want to handle the response of the API Call. Our options are as follows:.
             *
             *  Automatic : Automatically display the default success/error message depending on the API success
             *  Manual    : Manually display the provided custom information or message
             *
             *  Default is "automatic" if no value is provided
             */
            $return_type = $this->event['event_data']['response']['selected_type'] ?? 'automatic';

            //  Set an info log that we are starting to handle the CRUD API response
            $this->logInfo('Start handling CRUD Api Response');

            if ($return_type == 'manual') {

                return $this->handle_CRUD_Api_Manual_Response($response);

            } elseif ($return_type == 'automatic') {

                return $this->handle_CRUD_Api_Automatic_Response($response);

            }

        }
    }

    public function handle_CRUD_Api_Automatic_Response($response = null)
    {
        //  Set an info log that the CRUD API will be handled automatically
        $this->logInfo('Handle response '.$this->wrapAsSuccessHtml('Automatically'));

        //  Get the response status code e.g "200"
        $status_code = $response->getStatusCode();

        //  Get the response status phrase e.g "OK"
        $status_phrase = $response->getReasonPhrase() ?? '';

        /************************************
         * BUILD DEFAULT SUCCESS MESSAGE    *
         ***********************************/

        //  Get the default success message
        $default_success_message = $this->event['event_data']['response']['general']['default_success_message'];

        //  Convert the "step" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($default_success_message);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the processed link value - Convert to [String]
        $default_success_message = $this->convertToString($outputResponse) ?? 'Completed successfully';

        /**********************************
         * BUILD DEFAULT ERROR MESSAGE    *
         *********************************/

        //  Get the default error message
        $default_error_message = $this->event['event_data']['response']['general']['default_error_message'];

        //  Convert the "step" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($default_error_message);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the processed link value - Convert to [String]
        $default_error_message = $this->convertToString($outputResponse) ?? null;

        /*******************
         * HANDLE TYPES    *
         *******************/

        $on_success_handle_type = $this->event['event_data']['response']['automatic']['on_handle_success'] ?? 'use_default_success_msg';
        $on_error_handle_type = $this->event['event_data']['response']['automatic']['on_handle_error'] ?? 'use_default_error_msg';

        //  Check if this is a good status code e.g "100", "200", "301" e.t.c
        if ($this->checkIfGoodStatusCode($status_code)) {

            //  Set an info log of the response status code received
            $this->logInfo('API response returned a status ('.$this->wrapAsSuccessHtml($status_code).') Status text: '.$this->wrapAsSuccessHtml($status_phrase));

            //  Since this is a successful response, check if we should display a default success message or do nothing
            if ($on_success_handle_type == 'use_default_success_msg') {

                //  Set an info log that we are displaying the custom success message
                $this->logInfo('Display default success message: '.$this->wrapAsSuccessHtml($default_success_message));

                //  This is a good response - Display the custom succcess message
                return $this->showCustomScreen($default_success_message, ['continue' => false]);

            } elseif ($on_success_handle_type == 'do_nothing') {

                //  Return nothing
                return null;

            }

        //  If this is a bad status code e.g "400", "401", "500" e.t.c
        } else {

            //  Set an info log of the response status code received
            $this->logWarning('API response returned a status ('.$this->wrapAsSuccessHtml($status_code).') <br/> Status text: '.$this->wrapAsSuccessHtml($status_phrase));

            //  Since this is a failed response, check if we should display a default error message or do nothing
            if ($on_error_handle_type == 'use_default_error_msg') {

                //  If the default error message was provided
                if (!empty($default_error_message)) {

                    //  Set an info log that we are displaying the custom error message
                    $this->logInfo('Display default error message: '.$this->wrapAsSuccessHtml($default_error_message));

                    //  This is a bad response - Display the custom error message
                    return $this->showCustomErrorScreen($default_error_message);

                //  If the default error message was not provided
                } else {

                    //  Set an warning log that the default error message was not provided
                    $this->logWarning('The default error message was not provided, using the default '.$this->wrapAsSuccessHtml('technical difficulties message').' instead');

                    //  Show the technical difficulties error screen to notify the user of the issue
                    return $this->showTechnicalDifficultiesErrorScreen();

                }

            } elseif ($on_error_handle_type == 'do_nothing') {

                //  Return nothing
                return null;

            }
        }
    }

    public function handle_CRUD_Api_Manual_Response($response = null)
    {
        //  Use the try/catch handles incase we run into any possible errors
        try {
            //  Set an info log that the CRUD API will be handled manually
            $this->logInfo('Handle response '.$this->wrapAsSuccessHtml('Manually'));

            //  Get the response status code e.g "200"
            $status_code = $response->getStatusCode();

            //  Get the response status phrase e.g "OK"
            $status_phrase = $response->getReasonPhrase() ?? '';

            //  Get the response body and convert the JSON Object to an Array e.g [ "products" => [ ... ] ]
            $response_body = $this->convertObjectToArray(json_decode($response->getBody()));

            //  Get the response status handles
            $response_status_handles = $this->event['event_data']['response']['manual']['response_status_handles'] ?? [];

            if (!empty($response_status_handles)) {

                //  Get the request status handle that matches the given status
                $selected_handle = collect(array_filter($response_status_handles, function ($request_status_handle) use ($status_code) {
                    return $request_status_handle['status'] == $status_code;
                }))->first() ?? null;

                //  If a matching response status handle was found
                if ($selected_handle) {
                    
                    //  Get the response reference name
                    $response_reference_name = $selected_handle['reference_name'] ?? 'response';

                    //  If the response reference name was provided
                    if( !empty($response_reference_name) ){
                    
                        //  Get the response attributes
                        $response_attributes = $selected_handle['attributes'];

                        //  Get the response handle type e.g "use_custom_msg" or "do_nothing"
                        $on_handle_type = $selected_handle['on_handle']['selected_type'];

                        //  Set an info log that we are storing the attributes of the custom API response
                        $this->logInfo('Start processing and storing the response attributes');

                        //  Set an info log of the number of response attributes found
                        $this->logInfo('Found ('.$this->wrapAsSuccessHtml(count($response_attributes)).') response attributes');

                        //  Add the current response body to the dynamic data storage
                        $this->storeDynamicData($response_reference_name, $response_body , false);

                        //  Foreach attribute
                        foreach ($response_attributes as $response_attribute) {
                            
                            //  Get the attribute name
                            $name = trim($response_attribute['name']);

                            //  If the attribute name and value exists
                            if ( !empty($name) ) {

                                //  Get the attribute value
                                $value = $response_attribute['value'];
        
                                /*****************************
                                 * BUILD ATTRIBUTE VALUE     *
                                 ****************************/
                                                    
                                //  If the provided value is a valid mustache tag
                                if ($this->isValidMustacheTag($value, false)) {

                                    $mustache_tag = $value;
                        
                                    // Convert the mustache tag into dynamic data
                                    $outputResponse = $this->convertMustacheTagIntoDynamicData($mustache_tag);
        
                                    //  If we have a screen to show return the response otherwise continue
                                    if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;
                        
                                    //  Get the generated output
                                    $value = $outputResponse;   

                                    //  Set an info log of the attribute name
                                    $this->logInfo('Attribute: '.$this->wrapAsSuccessHtml( $this->convertToString($name) ) .' = '.$this->wrapAsSuccessHtml( $this->convertToString($value) ));
    
                                    //  Store the attribute data as dynamic data
                                    $this->storeDynamicData($name, $value);

                                }

                            }

                        }

                    }

                    if ($on_handle_type == 'use_custom_msg') {

                        //  Check if this is a good status code e.g "100", "200", "301" e.t.c
                        if ($this->checkIfGoodStatusCode($status_code)) {

                            //  Set an info log that we are displaying the custom message
                            $this->logInfo('Start processing the custom message to display for status code '.$this->wrapAsSuccessHtml($status_code));
                        
                        } else {

                            //  Set an info log that we are displaying the custom message
                            $this->logInfo('Start processing the custom message to display for status code '.$this->wrapAsErrorHtml($status_code));
                        
                        }
    
                        /*****************************
                         * BUILD CUSTOM MESSAGE      *
                         ****************************/

                        //  Get the custom message
                        $custom_message = $selected_handle['on_handle']['use_custom_msg'];

                        //  Convert the "custom message value" into its associated dynamic value
                        $outputResponse = $this->convertValueStructureIntoDynamicData($custom_message);

                        //  If we have a screen to show return the response otherwise continue
                        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                        //  Get the generated output - Convert to [String]
                        $custom_message = $this->convertToString($outputResponse);

                        //  Set an info log of the final result
                        $this->logInfo('Final result: <br />'.$this->wrapAsSuccessHtml($outputResponse));

                        //  Return the processed custom message display
                        return $this->showCustomScreen($outputResponse);

                    } elseif ($on_handle_type == 'do_nothing') {
                        
                        //  Return nothing
                        return null;

                    }
                } else {

                    //  Check if this is a good status code e.g "100", "200", "301" e.t.c
                    if ($this->checkIfGoodStatusCode($status_code)) {

                        //  Set a warning log that the custom API does not have a matching response status handle
                        $this->logWarning('No matching status handle to process the current response of status '.$this->wrapAsSuccessHtml($status_code));
                   
                    } else {

                        //  Set a warning log that the custom API does not have a matching response status handle
                        $this->logWarning('No matching status handle to process the current response of status '.$this->wrapAsErrorHtml($status_code));
                   
                    }
                }
            } else {

                //  Check if this is a good status code e.g "100", "200", "301" e.t.c
                if ($this->checkIfGoodStatusCode($status_code)) {

                    //  Set a warning log that the custom API does not have response status handles
                    $this->logWarning('No response status handles to process the current response of status '.$this->wrapAsSuccessHtml($status_code));
               
                } else {

                    //  Set a warning log that the custom API does not have response status handles
                    $this->logWarning('No response status handles to process the current response of status '.$this->wrapAsErrorHtml($status_code));
               
                }
                
            }

            //  Set a warning log that the custom API cannot be handled manually
            $this->logWarning('Could not handle the response '.$this->wrapAsSuccessHtml('Manually').', attempt to handle response '.$this->wrapAsSuccessHtml('Automatically'));

            //  Handle the request automatically
            return $this->handle_CRUD_Api_Automatic_Response($response);

        } catch (\Throwable $e) {

            //  Handle try catch error
            return $this->handleTryCatchError($e);

        } catch (Exception $e) {

            //  Handle try catch error
            return $this->handleTryCatchError($e);

        }
    }

    public function checkIfGoodStatusCode($status_code = '')
    {
        /** About Status Codes:
         *
         *  1xx informational response – the request was received, continuing process
         *  2xx successful – the request was successfully received, understood, and accepted
         *  3xx redirection – further action needs to be taken in order to complete the request
         *  4xx client error – the request contains bad syntax or cannot be fulfilled
         *  5xx server error – the server failed to fulfil an apparently valid request.
         */
        $digit = substr($status_code, 0, 1);

        //  If the status code starts with "1", "2" or "3" e.g "100", "200", "301" e.t.c
        if (in_array($digit, ['1', '2', '3'])) {

            //  Return true for good status code
            return true;

        }

        //  Return false for bad status code
        return false;
    }

    /******************************************
     *  VALIDATION EVENT METHODS              *
     *****************************************/

    /** This method gets all the validation rules of the current display. We then use these
     *  validation rules to validate the target input.
     */
    public function handle_Validation_Event()
    {
        if ($this->event) {

            //  Get the validation rules
            $validation_rules = $this->event['event_data']['rules'] ?? [];

            //  Get the target input
            $target_value = $this->event['event_data']['target'];

            /*************************
             * BUILD TARGET VALUE    *
             ************************/

            //  Convert the "target value" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($target_value);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output
            $target_value = $outputResponse;

            //  Validate the target input
            $failedValidationResponse = $this->handleValidationRules($target_value, $validation_rules);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($failedValidationResponse)) return $failedValidationResponse;
                
        }
    }

    /** This method checks if the given validation rules are active (If they must be used).
     *  If the validation rule must be used then we determine which rule we are given and which
     *  validation method must be used for each given case.
     */
    public function handleValidationRules($target_value, $validation_rules = [])
    {
        //  If we have validation rules
        if (!empty($validation_rules)) {
            
            //  For each validation rule
            foreach ($validation_rules as $validation_rule) {
                
                //  Get the active state value
                $activeState = $this->processActiveState($validation_rule['active']);
        
                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($activeState)) return $activeState;
        
                //  If the current validation rule is active (Must be used)
                if ( $activeState === true ) {
                    
                    //  Get the type of validation rule e.g "only_letters" or "only_numbers"
                    $validationType = $validation_rule['type'];

                    //  Use the switch statement to determine which validation method to use
                    switch ($validationType) {

                        case 'only_letters':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateOnlyLetters'); break;

                        case 'only_numbers':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateOnlyNumbers'); break;

                        case 'only_letters_and_numbers':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateOnlyLettersAndNumbers'); break;

                        case 'minimum_characters':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateMinimumCharacters'); break;

                        case 'maximum_characters':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateMaximumCharacters'); break;

                        case 'equal_to_characters':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateEqualToCharacters'); break;

                        case 'validate_email':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateEmail'); break;

                        case 'validate_mobile_number':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateMobileNumber'); break;

                        case 'valiate_date_format':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateDateFormat'); break;

                        case 'equal_to':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateEqualTo'); break;

                        case 'not_equal_to':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateNotEqualTo'); break;

                        case 'less_than':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateLessThan'); break;

                        case 'less_than_or_equal':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateLessThanOrEqualTo'); break;

                        case 'greater_than':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateGreaterThan'); break;

                        case 'greater_than_or_equal':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateGreaterThanOrEqualTo'); break;

                        case 'in_between_including':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateInBetweenIncluding'); break;

                        case 'in_between_excluding':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateInBetweenExcluding'); break;

                        case 'no_spaces':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateNoSpaces'); break;

                        case 'custom_regex':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateCustomRegex'); break;

                        case 'custom_code':

                            return $this->applyFormattingRule($target_value, $validation_rule, 'validateCustomCode'); break;
                    }
                }
            }

        }

        //  Return null to indicate that validation passed
        return null;
    }

    /** This method validates to make sure the target input
     *  is only letters with or without spaces
     */
    public function validateOnlyLetters($target_value, $validation_rule)
    {
        //  Regex pattern
        $pattern = '/^[a-zA-Z\s]+$/';

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || !preg_match($pattern, $target_value)) {

            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }
    }

    /** This method validates to make sure the target input
     *  is only numbers with or without spaces
     */
    public function validateOnlyNumbers($target_value, $validation_rule)
    {
        //  Regex pattern
        $pattern = '/^[0-9\s]+$/';

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || !preg_match($pattern, $target_value)) {

            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }
    }

    /** This method validates to make sure the target input
     *  is only letters and numbers with or without spaces
     */
    public function validateOnlyLettersAndNumbers($target_value, $validation_rule)
    {
        //  Regex pattern
        $pattern = '/^[a-zA-Z0-9\s]+$/';

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || !preg_match($pattern, $target_value)) {

            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }
    }

    /** This method validates to make sure the target input
     *  has characters the length of the minimum characters
     *  allowed of more
     */
    public function validateMinimumCharacters($target_value, $validation_rule)
    {
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $value = $validation_rule['value'];

        //  Convert the "value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output
        $minimum_characters = $this->convertToString($outputResponse);

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || empty($value) || !(strlen($target_value) >= $minimum_characters)) {

            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }
    }

    /** This method validates to make sure the target input
     *  has characters the length of the minimum characters
     *  allowed of more
     */
    public function validateMaximumCharacters($target_value, $validation_rule)
    {
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $value = $validation_rule['value'];

        //  Convert the "value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output
        $maximum_characters = $this->convertToString($outputResponse);

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || empty($value) || !(strlen($target_value) <= $maximum_characters)) {

            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }
    }

    /** This method validates to make sure the target input
     *  has characters with a length equal to a given value
     */
    public function validateEqualToCharacters($target_value, $validation_rule)
    {
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $value = $validation_rule['value'];

        //  Convert the "value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output
        $value = $outputResponse;

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || empty($value) || !(strlen($target_value) == $value)) {
            
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }
    }

    /** This method validates to make sure the target input
     *  is a valid email e.g example@gmail.com
     */
    public function validateEmail($target_value, $validation_rule)
    {
        //  Regex pattern
        $pattern = '/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD';

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || !preg_match($pattern, $target_value)) {

            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }
    }

    /** This method validates to make sure the target input
     *  is a valid mobile number (Botswana Mobile Numbers)
     *  e.g 71234567
     */
    public function validateMobileNumber($target_value, $validation_rule)
    {
        //  Regex pattern
        $pattern = '/^[7]{1}[1234567]{1}[0-9]{6}$/';

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || !preg_match($pattern, $target_value)) {

            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }
    }

    /** This method validates to make sure the target input
     *  is a valid date format e.g DD/MM/YYYY
     */
    public function validateDateFormat($target_value, $validation_rule)
    {
        //  Regex pattern
        $pattern = '/[0-9]{2}\/[0-9]{2}\/[0-9]{4}/';

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || !preg_match($pattern, $target_value)) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /** This method validates to make sure the target input
     *  has characters equal to a given value
     */
    public function validateEqualTo($target_value, $validation_rule)
    {
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $value = $validation_rule['value'];

        //  Convert the "value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output
        $value = $outputResponse;

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || empty($value) || !( $target_value == $value ) ) {
            
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }
    }

    /** This method validates to make sure the target input
     *  has characters not equal to a given value
     */
    public function validateNotEqualTo($target_value, $validation_rule)
    {   
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $value = $validation_rule['value'];

        //  Convert the "value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output
        $value = $outputResponse;

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || empty($value) || ($target_value == $value) ) {
            
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }
    }

    /** This method validates to make sure the target input
     *  has characters less than a given value
     */
    public function validateLessThan($target_value, $validation_rule)
    {
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $value = $validation_rule['value'];

        //  Convert the "value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output
        $value = $outputResponse;

        //  Convert to [Integer]
        $target_value = $this->convertToInteger($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || empty($value) || !($target_value < $value) ) {

            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }
    }

    /** This method validates to make sure the target input
     *  has characters less than or equal to a given value
     */
    public function validateLessThanOrEqualTo($target_value, $validation_rule)
    {
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $value = $validation_rule['value'];

        //  Convert the "value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output - Convert to [Integer]
        $value = $this->convertToInteger($outputResponse);

        //  Convert to [Integer]
        $target_value = $this->convertToInteger($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || empty($value) || !($target_value <= $value) ) {

            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }
    }

    /** This method validates to make sure the target input
     *  has characters grater than a given value
     */
    public function validateGreaterThan($target_value, $validation_rule)
    {
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $value = $validation_rule['value'];

        //  Convert the "value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output - Convert to [Integer]
        $value = $this->convertToInteger($outputResponse);

        //  Convert to [Integer]
        $target_value = $this->convertToInteger($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || empty($value) || !($target_value > $value) ) {

            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }

    }

    /** This method validates to make sure the target input
     *  has characters grater than a given value
     */
    public function validateGreaterThanOrEqualTo($target_value, $validation_rule)
    {
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $value = $validation_rule['value'];

        //  Convert the "value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output - Convert to [Integer]
        $value = $this->convertToInteger($outputResponse);

        //  Convert to [Integer]
        $target_value = $this->convertToInteger($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || empty($value) || !($target_value >= $value) ) {

            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }

    }

    /** This method validates to make sure the target input
     *  has characters inbetween the given min and max values
     *  (Including the Min and Max values)
     */
    public function validateInBetweenIncluding($target_value, $validation_rule)
    {
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $value = $validation_rule['value'];

        //  Convert the "value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output - Convert to [Integer]
        $min = $this->convertToInteger($outputResponse);

        /*********************
         * BUILD VALUE 2     *
         ********************/
        
        $value_2 = $validation_rule['value_2'];

        //  Convert the "value 2" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value_2);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output - Convert to [Integer]
        $max = $this->convertToInteger($outputResponse);

        //  Convert to [Integer]
        $target_value = $this->convertToInteger($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || empty($min) || empty($max) || !( ($min <= $target_value) && ($target_value <= $max)) ) {

            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }

    }

    /** This method validates to make sure the target input
     *  has characters inbetween the given min and max values
     *  (Excluding the Min and Max values)
     */
    public function validateInBetweenExcluding($target_value, $validation_rule)
    {
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $value = $validation_rule['value'];

        //  Convert the "value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output - Convert to [Integer]
        $min = $this->convertToInteger($outputResponse);

        /*********************
         * BUILD VALUE 2     *
         ********************/
        
        $value_2 = $validation_rule['value_2'];

        //  Convert the "value 2" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value_2);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output - Convert to [Integer]
        $max = $this->convertToInteger($outputResponse);

        //  Convert to [Integer]
        $target_value = $this->convertToInteger($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || empty($min) || empty($max) || !( ($min < $target_value) && ($target_value < $max)) ) {

            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }

    }

    /** This method validates to make sure the target input
     *  has no characters that are spaces
     */
    public function validateNoSpaces($target_value, $validation_rule)
    {
        //  Regex pattern
        $pattern = '/[\s]/';

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        //  If we found spaces i.e validation failed
        if ( empty($target_value) || preg_match($pattern, $target_value)) {

            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }

    }

    /** This method validates to make sure the target input
     *  matches the given custom regex rule
     */
    public function validateCustomRegex($target_value, $validation_rule)
    {
        //  Regex pattern
        $rule = $validation_rule['rule'];

        //  Convert the "rule value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($rule);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get Regex pattern - Convert to [String]
        $pattern = $this->convertToString($outputResponse);

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        //  If the pattern was not matched exactly i.e validation failed
        if ( empty($target_value) || !preg_match($pattern, $target_value)) {

            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }
    }

    /** This method validates to make sure the target input
     *  matches the given custom regex rule
     */
    public function validateCustomCode($target_value, $validation_rule)
    {
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $code = $validation_rule['value'];

        //  Process the PHP Code
        $outputResponse = $this->processPHPCode("$code");

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        $validation = $outputResponse;

        //  If the validation failed
        if ( $validation === false ) {

            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);

        }
    }

    /** This method gets the validation rule and callback. The callback represents the name of
     *  the validation function that we must run to validate the current input target. Since
     *  we allow custom Regex patterns for custom validation support, we must perform this under
     *  a try/catch incase the provided custom Regex pattern is invalid. This will allow us to
     *  catch any emerging error and be able to use the handleFailedValidation() in order to
     *  display the fatal error message and additional debugging details.
     */
    public function applyValidationRule($target_value, $validation_rule, $callback)
    {
        try {
            /** Perform the validation method here e.g "validateOnlyLetters()" within the try/catch
             *  method and pass the validation rule e.g "$this->validateOnlyLetters($target_value, $validation_rule )"
             */

            return call_user_func_array(array($this, $callback), [$target_value, $validation_rule]);

        } catch (\Throwable $e) {

            //  Handle failed validation
            $this->handleFailedValidation($validation_rule);

            //  Handle try catch error
            return $this->handleTryCatchError($e);

        } catch (Exception $e) {

            //  Handle failed validation
            $this->handleFailedValidation($validation_rule);

            //  Handle try catch error
            return $this->handleTryCatchError($e);

        }
    }

    /** This method logs a warning with details about the failed validation rule
     */
    public function handleFailedValidation($validation_rule)
    {
        $error_message = $validation_rule['error_msg'];

        //  Convert the "error message" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($error_message);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get error message - Convert to [String]
        $error_message = $this->convertToString($outputResponse);

        $this->logWarning('Validation failed using ('.$this->wrapAsSuccessHtml($validation_rule['name']).') with message: '.$this->wrapAsErrorHtml($error_message));

        //  Return the processed custom validation error message display
        return $this->showCustomGoBackScreen($error_message."\n");
    }

    /******************************************
     *  VALIDATION EVENT METHODS              *
     *****************************************/

    /** This method gets all the formatting rules of the current display. We then use these
     *  formatting rules to modify the target input.
     */
    public function handle_Formatting_Event()
    {
        if ($this->event) {

            //  Get the formatting rules
            $formatting_rules = $this->event['event_data']['rules'] ?? [];

            //  Get the target input
            $reference_name = $this->event['event_data']['reference_name'];

            //  Get the target input
            $target_value = $this->event['event_data']['target'];

            /*************************
             * BUILD TARGET VALUE    *
             ************************/

            //  Convert the "target value" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($target_value);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output
            $target_value = $outputResponse ?? null;

            //  Format the target input
            $formattingResponse = $this->handleFormattingRules($target_value, $formatting_rules);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($formattingResponse)) return $formattingResponse;

            //  Store the formatted data as dynamic data
            $this->storeDynamicData($reference_name, $formattingResponse);
                
        }
    }

    /** This method checks if the given formatting rules are active (If they must be used).
     *  If the formatting rule must be used then we determine which rule we are given and 
     *  which formatting method must be used for each given case.
     */
    public function handleFormattingRules($target_value, $formatting_rules = [])
    {
        //  If we have formatting rules
        if (!empty($formatting_rules)) {
            
            //  For each formatting rule
            foreach ($formatting_rules as $formatting_rule) {

                //  Get the active state value
                $activeState = $this->processActiveState($formatting_rule['active']);
        
                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($activeState)) return $activeState;
        
                //  If the current formatting rule is active (Must be used)
                if ( $activeState === true ) {
                    
                    //  Get the type of formatting rule e.g "only_letters" or "only_numbers"
                    $formattingType = $formatting_rule['type'];

                    //  Use the switch statement to determine which formatting method to use
                    switch ($formattingType) {

                        case 'capitalize':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'capitalizeFormat'); break;

                        case 'uppercase':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'uppercaseFormat'); break;

                        case 'lowercase':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'lowercaseFormat'); break;

                        case 'trim':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'trimFormat'); break;

                        case 'trim_left':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'trimLeftFormat'); break;

                        case 'trim_right':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'trimRightFormat'); break;

                        case 'convert_to_money':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'converToMoneyFormat'); break;

                        case 'limit':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'limitFormat'); break;

                        case 'substr':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'substrFormat'); break;

                        case 'remove_letters':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'removeLettersFormat'); break;

                        case 'remove_numbers':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'removeNumbersFormat'); break;

                        case 'remove_symbols':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'removeSymbolsFormat'); break;

                        case 'remove_spaces':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'removeSpacesFormat'); break;

                        case 'replace_with':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'replaceWithFormat'); break;

                        case 'replace_first_with':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'replaceWithFormat', 'first'); break;

                        case 'replace_last_with':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'replaceWithFormat', 'last'); break;

                        case 'plural_or_singular':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'pluralOrSingularFormat'); break;

                        case 'random_string':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'randomStringFormat'); break;

                        case 'custom_code':

                            return $this->applyFormattingRule($target_value, $formatting_rule, 'customCodeFormat'); break;
                    }
                }
            }

        }

        //  Return null to indicate that formatting passed
        return null;
    }

    /** This method capitalizes the given target value
     */
    public function capitalizeFormat($target_value, $formatting_rule)
    {
        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        return ucfirst( $target_value );
    }

    /** This method convert the given target value into lowercase
     */
    public function lowercaseFormat($target_value, $formatting_rule)
    {
        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        return strtolower( $target_value );
    }

    /** This method convert the given target value into uppercase
     */
    public function uppercaseFormat($target_value, $formatting_rule)
    {
        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        return strtoupper( $target_value );
    }

    /** This method removes left and right spaces from the target value
     */
    public function trimFormat($target_value, $formatting_rule)
    {
        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        return trim($target_value);
    }

    /** This method removes left spaces from the target value
     */
    public function trimLeftFormat($target_value, $formatting_rule)
    {
        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        return ltrim($target_value);
    }

    /** This method removes right spaces from the target value
     */
    public function trimRightFormat($target_value, $formatting_rule)
    {
        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        return rtrim($target_value);
    }

    /** This method convert a given number to represent money format
     */
    public function converToMoneyFormat($target_value, $formatting_rule)
    {
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $value = $formatting_rule['value'];

        //  Convert the "value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output - Convert to [String]
        $currency_symbol = $this->convertToString($outputResponse);

        //  Convert to [Integer]
        $target_value = $this->convertToInteger($target_value);

        return $currency_symbol . number_format($target_value, 2, '.', ',');

    }

    /** This method limits the number of characters of the target value
     */
    public function limitFormat($target_value, $formatting_rule)
    {
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $value = $formatting_rule['value'];

        //  Convert the "value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output - Convert to [Integer]
        $limit = $this->convertToInteger($outputResponse);

        /*********************
         * BUILD VALUE 2     *
         ********************/
        
        $value = $formatting_rule['value_2'];

        //  Convert the "value 2" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output - Convert to [Integer]
        $trail = $this->convertToString($outputResponse);

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);
        
        if( strlen($target_value) > $limit ){

            if( $limit > strlen($trail) ){

                return substr($target_value, 0, $limit - strlen($trail)) . $trail;

            }else{
                
                return substr($target_value, 0, $limit);

            }

        }

    }

    /** This method strips the characters of the target value
     */
    public function substrFormat ($target_value, $formatting_rule)
    {
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $value = $formatting_rule['value'];

        //  Convert the "value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output - Convert to [Integer]
        $start = $this->convertToInteger($outputResponse);

        /*********************
         * BUILD VALUE 2     *
         ********************/
        
        $value = $formatting_rule['value_2'];

        //  Convert the "value 2" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output - Convert to [Integer]
        $length = $this->convertToInteger($outputResponse);

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        return substr($target_value, $start, $length);

    }

    /** This method removes letters from the target value
     */
    public function removeLettersFormat ($target_value, $formatting_rule)
    {
        //  Regex pattern
        $pattern = '/[a-zA-Z]+/';

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        //  Replace the letters from the target value with nothing
        return preg_replace($pattern, '', $target_value);
    }

    /** This method removes numbers from the target value
     */
    public function removeNumbersFormat ($target_value, $formatting_rule)
    {
        //  Regex pattern
        $pattern = '/[0-9]+/';

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        //  Replace the numbers from the target value with nothing
        return preg_replace($pattern, '', $target_value);
    }

    /** This method removes symbols from the target value
     *  (Removes everything except letters, numbers and
     *  spaces)
     */
    public function removeSymbolsFormat ($target_value, $formatting_rule)
    {
        //  Regex pattern
        $pattern = '/[^a-zA-Z0-9\s]+/';

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        //  Replace the symbols from the target value with nothing
        return preg_replace($pattern, '', $target_value);
    }

    /** This method removes spaces
     */
    public function removeSpacesFormat ($target_value, $formatting_rule)
    {
        //  Regex pattern
        $pattern = '/[\s]+/';

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        //  Replace the symbols from the target value with nothing
        return preg_replace($pattern, '', $target_value);
    }

    /** This method replaces a value within the target value with
     *  another value
     */
    public function replaceWithFormat($target_value, $formatting_rule, $type = null)
    {
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $value = $formatting_rule['value'];

        //  Convert the "value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output - Convert to [String]
        $search_value = $this->convertToString($outputResponse);

        /*********************
         * BUILD VALUE 2     *
         ********************/
        
        $value = $formatting_rule['value_2'];

        //  Convert the "value 2" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output - Convert to [String]
        $replace_value = $this->convertToString($outputResponse);

        //  Convert to [String]
        $target_value = $this->convertToString($target_value);

        if( $type == 'first' ){

            //  Replaces the first occurrence of a given value in a string
            return Str::of($target_value)->replaceFirst($search_value, $replace_value);

        }elseif( $type == 'last' ){

            //  Replaces the last occurrence of a given value in a string
            return Str::of($target_value)->replaceLast($search_value, $replace_value);

        }else{
            
            //  Replaces the every occurrence of a given value in a string
            return str_replace($target_value, $search_value, $replace_value);

        }

    }

    /** This method will convert the target value into its plural form
     */
    public function pluralOrSingularFormat($target_value, $formatting_rule)
    {
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $value = $formatting_rule['value'];

        //  Convert the "value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output - Convert to [String]
        $word = $this->convertToString($outputResponse);

        //  Convert to [Integer]
        $target_value = $this->convertToInteger($target_value);

        /** Convert the $word into "car" into "cars" or "child" into "children" 
         *  if $target_value is greater than 1 and vice-versa if the
         *  $target_value is equal to 1
         */
        return Str::plural($word, $target_value);
    }

    /** This method will generate a random string with a length the size of the 
     *  target value specified
     */
    public function randomStringFormat($target_value, $formatting_rule)
    {
        //  Convert to [Integer]
        $target_value = $this->convertToInteger($target_value);

        /** Convert the $word into "car" into "cars" or "child" into "children" 
         *  if $target_value is greater than 1 and vice-versa if the
         *  $target_value is equal to 1
         */
        return Str::random($target_value);
    }

    public function customCodeFormat($target_value, $formatting_rule)
    {
        /*******************
         * BUILD VALUE     *
         ******************/
        
        $code = $formatting_rule['value'];

        //  Process the PHP Code
        $outputResponse = $this->processPHPCode("$code");

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        return $outputResponse;
    }

    /** This method gets the formatting rule and callback. The callback represents the name of
     *  the formatting function that we must run to format the current target value. Since
     *  we allow custom code for custom formatting support, we must perform this under
     *  a try/catch incase the provided custom Regex pattern is invalid. This will 
     *  allow us to catch any emerging error and be able to use the 
     *  handleFailedformatting() in order to display the fatal 
     *  error message and additional debugging details.
     */
    public function applyformattingRule($target_value, $formatting_rule, $callback)
    {
        try {
            /* Perform the formatting method here e.g "validateOnlyLetters()" within the try/catch
             *  method and pass the formatting rule e.g "$this->validateOnlyLetters($target_value, $formatting_rule )"
             */

            return call_user_func_array(array($this, $callback), [$target_value, $formatting_rule]);

        } catch (\Throwable $e) {

            //  Handle failed formatting
            $this->handleFailedFormatting($formatting_rule);

            //  Handle try catch error
            return $this->handleTryCatchError($e);

        } catch (Exception $e) {

            //  Handle failed formatting
            $this->handleFailedFormatting($formatting_rule);

            //  Handle try catch error
            return $this->handleTryCatchError($e);

        }
    }
    
    /** This method logs a warning and returns the technical difficulties screen
     */
    public function handleFailedFormatting($formatting_rule)
    {
        $this->logWarning('Formatting failed using ('.$this->wrapAsSuccessHtml($formatting_rule['name']).')');

        //  Show the technical difficulties error screen to notify the user of the issue
        return $this->showTechnicalDifficultiesErrorScreen();

    }

    /*********************************************
     *  LOCAL STORAGE EVENT METHODS              *
     ********************************************/

    /*  handle_Local_Storage_Event()
     *  This method gets all the local storage of the current display.
     *  We then use these to store datasets and make them accessible
     *  to the current display and other linked displays.
     */
    public function handle_Local_Storage_Event()
    {
        if ($this->event) {

            //  Get the local storage reference name
            $reference_name = $this->event['event_data']['reference_name'];

            //  Get the local storage type e.g "string", "array"
            $storage_type = $this->event['event_data']['storage']['selected_type'];

            //  If the reference name is provided
            if (!empty($reference_name)) {

                //  If the storage type is of type "Array"
                if ($storage_type == 'array') {

                    //  Get the local storage type e.g "string", "array"
                    $dataset_type = $this->event['event_data']['storage']['array']['dataset']['selected_type'];

                    if ($dataset_type == 'values') {

                        //  Get the dataset
                        $array_values = $this->event['event_data']['storage']['array']['dataset']['values'];

                        //  If the dataset was provided
                        if (!empty($array_values)) {

                            return $this->handleArrayValuesLocalStorage();

                        }

                    } elseif ($dataset_type == 'key_values') {

                        //  Get the dataset
                        $array_key_values = $this->event['event_data']['storage']['array']['dataset']['key_values'];

                        //  If the dataset was provided
                        if (!empty($array_key_values)) {

                            return $this->handleArrayKeyValuesLocalStorage();

                        }

                    }

                //  If the storage type is of type "String"
                } elseif ($storage_type == 'string') {

                    return $this->handleStringLocalStorage();

                //  If the storage type is of type "Code"
                } elseif ($storage_type == 'code') {

                    //  Get the dataset
                    $code = $this->event['event_data']['storage']['code']['dataset']['value'];

                    //  If the dataset was provided
                    if (!empty( $code )) {

                        return $this->handleCodeLocalStorage();

                    }

                }
            } else {

                $this->logWarning('The provided Local Storage '. $this->wrapAsSuccessHtml($this->event['name']).' does not have a reference name');
            
            }
        }
    }

    public function handleArrayValuesLocalStorage()
    {
        //  Get the local storage reference name
        $reference_name = $this->event['event_data']['reference_name'];

        //  Get the dataset mode e.g "replace", "append", "prepend"
        $mode = $this->event['event_data']['storage']['array']['mode']['selected_type'];

        //  Get the dataset
        $array_values = $this->event['event_data']['storage']['array']['dataset']['values'];

        $processed_values = [];

        //  Foreach dataset value
        foreach ($array_values as $array_value) {

            /******************
             * BUILD VALUE    *
             ******************/

            //  Convert the "array value" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($array_value['value']);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)){
                
                $outputResponse = $this->setEmptyKeyValueWithDefaultValue($reference_name, $array_value);

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            }

            //  Set the storage value to the received array value
            $storage_value = $outputResponse;

            //  Add current processed value to the the processed values array
            array_push($processed_values, $storage_value);
        }

        //  Store the processed values
        $this->handleProcessedValueStorage($reference_name, $processed_values, $mode);

    }

    public function handleArrayKeyValuesLocalStorage()
    {
        //  Get the local storage reference name
        $reference_name = $this->event['event_data']['reference_name'];

        //  Get the dataset mode e.g "replace", "append", "prepend"
        $mode = $this->event['event_data']['storage']['array']['mode']['selected_type'];

        //  Get the dataset
        $array_key_values = $this->event['event_data']['storage']['array']['dataset']['key_values'];

        $processed_values = [];

        //  Foreach dataset value
        foreach ($array_key_values as $key => $array_key_value) {

            /******************
             * BUILD VALUE    *
             ******************/

            //  Convert the "array value" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($array_key_value['value']);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)){
                
                $outputResponse = $this->setEmptyKeyValueWithDefaultValue($reference_name, $array_key_value);

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            }
            
            //  Set the storage value to the received array value
            $storage_value = $outputResponse;

            //  Add current processed value to the the processed values array
            $processed_values[$array_key_value['key']] = $storage_value;
            
        }

        //  Store the processed values
        $this->handleProcessedValueStorage($reference_name, $processed_values, $mode);
    }

    public function setEmptyKeyValueWithDefaultValue($name, $value = null)
    {
        $this->logWarning('Value for '.$this->wrapAsSuccessHtml($name).' could not be set, attempting to use default value');

        //  Get selected default type e.g "text_input", "number_input", "true", "false", "null", 'empty_array'
        $default_type = $value['on_empty_value']['default']['selected_type'];

        if ($default_type == 'true') {

            //  Set the storage value to "True"
            $storage_value = true;

            $this->logInfo('Setting value of '.$this->wrapAsSuccessHtml($name).' to '.$this->wrapAsSuccessHtml('True'));

        } elseif ($default_type == 'false') {

            //  Set the storage value to "False"
            $storage_value = false;

            $this->logInfo('Setting value of '.$this->wrapAsSuccessHtml($name).' to '.$this->wrapAsSuccessHtml('False'));

        } elseif ($default_type == 'null') {

            //  Set the storage value to "Null"
            $storage_value = null;

            $this->logInfo('Setting value of '.$this->wrapAsSuccessHtml($name).' to '.$this->wrapAsSuccessHtml('Null'));

        } elseif ($default_type == 'empty_array') {

            //  Set the storage value to an "Empty Array"
            $storage_value = [];

            $this->logInfo('Setting value of '.$this->wrapAsSuccessHtml($name).' to an '.$this->wrapAsSuccessHtml('empty array []'));

        }else{

            //  Get the default value
            $value = $value['on_empty_value']['default']['custom'];
                
            /******************
             * BUILD VALUE    *
             ******************/

            //  Convert the "default value" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($value);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output
            $storage_value = $outputResponse;

            //  Get the storage_value type wrapped in html tags
            $dataType = $this->wrapAsSuccessHtml( $this->getDataType($storage_value) );

            $this->logInfo('Setting value of '.$this->wrapAsSuccessHtml($name).' to ['.$dataType.']');

        }

        return $storage_value;

    }

    public function handleCodeLocalStorage()
    {
        //  Get the local storage reference name
        $reference_name = $this->event['event_data']['reference_name'];

        //  Get the dataset mode e.g "concatenate", "replace", "append", "prepend"
        $mode = $this->event['event_data']['storage']['code']['mode']['selected_type'];

        //  Get the dataset join
        $join = $this->event['event_data']['storage']['code']['mode']['concatenate']['value'];

        //  Get the dataset code
        $code = $this->event['event_data']['storage']['code']['dataset']['value'];

        //  Process the PHP Code
        $outputResponse = $this->processPHPCode("$code");

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        $processed_values = $outputResponse;

        //  Store the processed values
        $this->handleProcessedValueStorage($reference_name, $processed_values, $mode, $join);

    }

    public function handleStringLocalStorage()
    {
        //  Get the local storage reference name
        $reference_name = $this->event['event_data']['reference_name'];

        //  Get the dataset mode e.g "concatenate", "replace", "append", "prepend"
        $mode = $this->event['event_data']['storage']['string']['mode']['selected_type'];

        //  Get the dataset join
        $join = $this->event['event_data']['storage']['string']['mode']['concatenate']['value'];

        //  Get the dataset
        $value = $this->event['event_data']['storage']['string']['dataset'];

        //  Convert the "value" into its associated dynamic value
        $outputResponse = $this->convertValueStructureIntoDynamicData($value);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        //  Get the generated output - Convert to [String]
        $processed_values = $this->convertToString($outputResponse);

        //  Store the processed values
        $this->handleProcessedValueStorage($reference_name, $processed_values, $mode, $join);
    }

    public function handleProcessedValueStorage($reference_name, $processed_values, $mode, $join = ' ')
    {
        //  Get any already existing data
        $existing_value = $this->getDynamicData($reference_name);

        //  Check if the given mode matches any array modes
        $matches_array_modes = count(array_filter(['replace', 'append', 'prepend'], function ($value) use ($mode) {
            return $mode == $value;
        })) ? true : false;

        //  Check if the given mode matches any string modes
        $matches_string_modes = count(array_filter(['concatenate'], function ($value) use ($mode) {
            return $mode == $value;
        })) ? true : false;

        //  If the processed value(s) matches the array modes
        if ($matches_array_modes) {

            //  If the processed value(s) is an array
            if (is_array($processed_values)) {
                
                //  If the mode is set to "replace"
                if ($mode == 'replace') {

                    //  Store the array value(s) as dynamic data (Replace existing data)
                    $this->storeDynamicData($reference_name, $processed_values);

                //  If the mode is set to "append" or "prepend"
                } else {

                    /** If we have only one value e.g
                     *  $processed_values = ["Francistown"] or $processed_values = ["Gaborone"].
                     */
                    if (count($processed_values) == 1) {
                        /* Ungroup the result by removing the braces [] e.g
                         *
                         *  Allow for this:.
                         *
                         *  $this->getDynamicData('locations') = ["Francistown", "Gaborone" ]
                         *
                         *  Instead of this:
                         *
                         *  $this->getDynamicData('locations') = [ ["Francistown"], ["Gaborone"] ]
                         *
                         *  If we have more than one value then we do not need to do this othrwise we get:
                         *
                         *  $this->getDynamicData('locations') = ["1", "Francistown", "2", "Gaborone" ]
                         *
                         *  Instead of this:
                         *
                         *  $this->getDynamicData('locations') = [ ["1", "Francistown"], ["2", "Gaborone"] ]
                         */
                        if (isset($processed_values[0])) {
                            $processed_values = $processed_values[0];
                        }

                    }

                    if ($mode == 'append') {

                        if (!empty($existing_value) && is_array($existing_value)) {
                            
                            $exising_array_data = $existing_value;

                            //  Add after existing datasets
                            array_push($exising_array_data, $processed_values);

                            //  Store the array value(s) as dynamic data
                            $this->storeDynamicData($reference_name, $exising_array_data);

                        } else {

                            //  Store the array value(s) as dynamic data
                            $this->storeDynamicData($reference_name, [$processed_values]);

                        }

                        //  If the mode is set to "prepend"
                    } elseif ($mode == 'prepend') {

                        if (!empty($existing_value) && is_array($existing_value)) {
                            
                            $exising_array_data = $this->getDynamicData($reference_name);

                            //  Add before existing datasets
                            array_unshift($exising_array_data, $processed_values);

                            //  Store the array value(s) as dynamic data
                            $this->storeDynamicData($reference_name, $exising_array_data);

                        } else {

                            //  Store the array value(s) as dynamic data
                            $this->storeDynamicData($reference_name, [$processed_values]);

                        }
                    }
                }

            } else {

                $mode = $this->wrapAsSuccessHtml( getDataType($mode) );
                
                $dataType = $this->wrapAsSuccessHtml( $this->getDataType($processed_values) );

                $this->logInfo('Local Storage called '.$this->wrapAsSuccessHtml($name).' using the Mode = ['.$mode.'] requires the data to be of type ['.$this->wrapAsSuccessHtml('Array').'], however we received data of type ['.$dataType.']');

            }

            //  If the storage value is a string and the given mode matches the string modes
        } elseif ($matches_string_modes) {

            if (is_string($processed_values)) {

                //  If the mode is set to "replace"
                if ($mode == 'concatenate') {

                    if (!empty($existing_value) && is_string($existing_value)) {
                        
                        $exising_string_data = $this->getDynamicData($reference_name);

                        //  Concatenate the dataset
                        $exising_string_data .= $join.$processed_values;

                        //  Store the string value as dynamic data
                        $this->storeDynamicData($reference_name, $exising_string_data);

                    } else {

                        //  Store the string value(s) as dynamic data
                        $this->storeDynamicData($reference_name, $processed_values);

                    }

                }else{

                    //  Store the string value(s) as dynamic data
                    $this->storeDynamicData($reference_name, $processed_values);

                }

            } else {

                $mode = $this->wrapAsSuccessHtml( getDataType($mode) );
                
                $dataType = $this->wrapAsSuccessHtml( $this->getDataType($processed_values) );

                $this->logInfo('Local Storage called '.$this->wrapAsSuccessHtml($name).' using the Mode = ['.$mode.'] requires the data to be of type ['.$this->wrapAsSuccessHtml('String').'], however we received data of type ['.$dataType.']');

            }
        }
    }

    /******************************************
     *  REDIRECT EVENT METHODS                *
     *****************************************/

    /** This method gets all the revisit instructions of the current display. We then use these
     *  revisit instructions to allow the current display to revisit a previous screen, marked
     *  screen or the first launched screen of the current USSD Service Code.
     */
    public function handle_Revisit_Event()
    {
        if ($this->event) {

            //  Get the trigger type e.g "automatic", "manual"
            $trigger = $this->event['event_data']['general']['trigger']['selected_type'];

            //  Get the trigger type e.g "automatic", "manual"
            $manual_trigger_input = $this->event['event_data']['general']['trigger']['manual']['input'];

            //  Get the additional responses
            $automatic_replies = $this->event['event_data']['general']['automatic_replies'];

            //  Get the redirect type e.g "home_revisit", "screen_revisit", "marked_revisit"
            $revisit_type = $this->event['event_data']['revisit_type']['selected_type'];

            $is_triggered = false;

            /********************************
             * BUILD MANUAL TRIGGER INPUT   *
             *******************************/

            //  Convert the "manual_trigger_input" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($manual_trigger_input);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output - Convert to [String]
            $manual_trigger_input = $this->convertToString($outputResponse);

            /** If the trigger is manual, this means that the redirect is only
             *  triggered if the user provided the trigger input and if the
             *  input matches the required value to trigger the redirect.
             */
            if( $trigger == 'manual' ){

                $this->logInfo('Handling '.$this->wrapAsSuccessHtml('Manual Revisit').' event');

                //  If the manual input is provided
                if (!empty($manual_trigger_input)) {

                    //  If the manual trigger input matches the current user input
                    if( $manual_trigger_input == $this->current_user_response ){
                        
                        //  Trigger the event manually to redirect
                        $is_triggered = true;
    
                    }

                }

            }else{

                $this->logInfo('Handling '.$this->wrapAsSuccessHtml('Automatic Revisit').' event');

                //  Trigger the event automatically to redirect
                $is_triggered = true;

            }

            //  If the event has been triggered
            if( $is_triggered ){

                $this->logInfo('The '.$this->event['name'].' event has been triggered');

                /****************************
                 * BUILD AUTOMATIC REPLIES  *
                 ****************************/

                //  Convert the "automatic_replies" into its associated dynamic value
                $outputResponse = $this->convertValueStructureIntoDynamicData($automatic_replies);

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                //  Get the generated output - Convert to [String]
                $automatic_replies_text = $this->convertToString($outputResponse);

                //  If the text is not a type of [String] or [Integer]
                if ( !( is_string($automatic_replies_text) || is_integer($automatic_replies_text) )) {
                
                    $dataType = $this->wrapAsSuccessHtml( $this->getDataType($processed_values) );
    
                    $this->logInfo('The given '.$this->wrapAsSuccessHtml('Additional Responses').' must return data of type ['.$this->wrapAsSuccessHtml('String').'], however we received data of type ['.$dataType.']');
    
                    //  Empty the value
                    $automatic_replies_text = '';

                }

                if( $revisit_type ==  'home_revisit'){

                    return $this->handleHomeRevisit($automatic_replies_text);
    
                }else if( $revisit_type ==  'screen_revisit'){

                    return $this->handleScreenRevisit($automatic_replies_text);
    
                }else if( $revisit_type ==  'marked_revisit'){
    
                }
            }
        }
    }

    public function handleHomeRevisit($automatic_replies_text = '')
    {
        if( !empty( $automatic_replies_text ) ){

            $service_code = substr($this->service_code, 0, -1).'*'.$automatic_replies_text.'#';

        }else{
            
            $service_code = $this->service_code;

        }

        $this->logInfo('Revisiting Home: '.$this->wrapAsSuccessHtml($service_code));

        /** We need to re-run the handleExistingSession() method. This will allow us the opportunity
         *  to change the database "text" value. By updating this value we are able to alter the
         *  current session journey to force changes such as:
         * 
         *  - Going back
         *  - Going back and inserting new replies
         *  - Cancelling long Journeys
         *  - Undoing previous actions
         *  ...e.t.c
        */
        
        //  Reset the level
        $this->level = 1;

        //  Update the text value
        $this->text = $automatic_replies_text;

        //  Handle existing session - Re-run the handleExistingSession()
        return $this->handleExistingSession(false);
        
    }

    public function handleScreenRevisit($automatic_replies = [])
    {
        $outputResponse = $this->merge_array_text_responses($this->service_code, $automatic_replies);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) {

            return $outputResponse;

        }

        //  Get the processed service code
        $service_code = $outputResponse;

        $this->logInfo('Revisiting Screen: <span class="text-success">'.$service_code.'</span>');

        return $service_code;
    }

    /******************************************
     *  REDIRECT EVENT METHODS                *
     *****************************************/

    /** This method  gets the user information to create a new user account or
     *  update an existing user account. User accounts are updated if an account
     *  with a matching mobile number is found.
     */
    public function handle_Create_Or_Update_Account_Event()
    {
        if ($this->event) {

            //  Get the users first name
            $first_name = $this->event['event_data']['first_name'];

            //  Get the users last name
            $last_name = $this->event['event_data']['last_name'];

            //  Get the users mobile number
            $mobile_number = $this->event['event_data']['mobile_number'];

            /*********************
             * BUILD FIRST NAME  *
             *********************/

            //  Convert the "first_name" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($first_name);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output - Convert to [String]
            $first_name = $this->convertToString($outputResponse);

            //  If the "first_name" is not a type of [String]
            if ( !is_string($first_name) ) {
            
                $dataType = $this->wrapAsSuccessHtml( $this->getDataType($first_name) );

                $this->logInfo('The given '.$this->wrapAsSuccessHtml('first name').' of the user account must return data of type ['.$this->wrapAsSuccessHtml('String').'], however we received data of type ['.$dataType.']');

            }

            /*********************
             * BUILD LAST NAME  *
             *********************/

            //  Convert the "last_name" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($last_name);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output - Convert to [String]
            $last_name = $this->convertToString($outputResponse);

            //  If the "last_name" is not a type of [String]
            if ( !is_string($last_name) ) {
            
                $dataType = $this->wrapAsSuccessHtml( $this->getDataType($last_name) );

                $this->logInfo('The given '.$this->wrapAsSuccessHtml('last name').' of the user account must return data of type ['.$this->wrapAsSuccessHtml('String').'], however we received data of type ['.$dataType.']');

            }

            /************************
             * BUILD MOBILE NUMBER  *
             ***********************/

            //  Convert the "mobile_number" into its associated dynamic value
            $outputResponse = $this->convertValueStructureIntoDynamicData($mobile_number);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output - Convert to [String]
            $mobile_number = $this->convertToString($outputResponse);

            //  If the "mobile_number" is not a type of [String] or [Integer]
            if ( !( is_string($mobile_number) || is_integer($mobile_number) ) ) {
            
                $dataType = $this->wrapAsSuccessHtml( $this->getDataType($mobile_number) );

                $this->logInfo('The given '.$this->wrapAsSuccessHtml('moible number').' of the user account must return data of type ['.$this->wrapAsSuccessHtml('String').'] or ['.$this->wrapAsSuccessHtml('Integer').'], however we received data of type ['.$dataType.']');

            }

            /****************************
             * BUILD ADDITIONAL VALUES  *
             ***************************/

            $processed_fields = [];

            //  Get the additional fields dataset
            $additional_fields = $this->event['event_data']['additional_fields'];

            //  Foreach dataset value
            foreach ($additional_fields as $key => $field) {

                /******************
                 * BUILD VALUE    *
                 ******************/

                $reference_name = $field['key'];

                //  Convert the "field value" into its associated dynamic value
                $outputResponse = $this->convertValueStructureIntoDynamicData($field['value']);

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)){
                    
                    $outputResponse = $this->setEmptyKeyValueWithDefaultValue($reference_name, $field);

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                }
                
                //  Set the value to the received array value
                $value = $outputResponse;

                //  Add current processed value to the the processed values array
                $processed_fields[$field['key']] = $value;
                
            }
                            
            $user_account_data = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'mobile_number' => $mobile_number,
                'project_id' =>  $this->project->id
            ];

            //  If we are on test mode
            if( $this->test_mode ){
    
                //  Get the User Fake Account (Check if we have an account matching the mobile number)
                $user_account = \App\UserAccount::where('mobile_number', $mobile_number)->testAccount()->first();
    
                //  Set the User Account as a test account
                $user_account_data['test'] = true;
    
            //  If we are not on test mode
            }else{
    
                //  Get the User Fake Account (Check if we have an account matching the mobile number)
                $user_account = \App\UserAccount::where('mobile_number', $mobile_number)->realAccount()->first();
    
                //  Set the User Account as a real account
                $user_account_data['test'] = false;
    
            }

            //  If the user account already exists
            if( $user_account ){

                $this->logInfo('Found existing user account matching the mobile number '.$this->wrapAsSuccessHtml($mobile_number));

                $this->logInfo('Attempting to update user account using the mobile number '.$this->wrapAsSuccessHtml($mobile_number));
                    
                //  Get the existing user account metadata
                $metadata = $user_account->metadata ?? [];

                //  If we have processed additional fields
                if( count($processed_fields) ){
                   
                    //  Overide the existing user account metadata
                    $metadata = array_merge($metadata, $processed_fields);

                }

                //  Update the user account metadata
                $user_account_data['metadata'] = $metadata;

                //  Update existing user account
                $user_account_updated = \App\UserAccount::where('mobile_number', $mobile_number)
                                                        ->where('test', $user_account_data['test'])
                                                        ->update($user_account_data);

                if( $user_account_updated ){
                    
                    $this->logInfo('User account updated successfully');

                    $this->user_account = $this->getUserAccountDetails($user_account->fresh());

                    $this->logInfo($this->wrapAsSuccessHtml( $this->user_account ));

                }else{

                    $this->logError('Sorry, account update failed');

                }

            //  If the user account does not already exist
            }else{

                $this->logInfo('User account matching the mobile number '.$this->wrapAsSuccessHtml($mobile_number).' does not exist');

                $this->logInfo('Attempting to create a new user account using the mobile number '.$this->wrapAsSuccessHtml($mobile_number));

                //  Update the user account metadata
                $user_account_data['metadata'] = $processed_fields;

                //  Create new user account
                $user_account = \App\UserAccount::create($user_account_data);

                if( $user_account ){

                    $this->logInfo('User account created successfully');

                    $this->user_account = $this->getUserAccountDetails($user_account);

                    $this->logInfo($this->wrapAsSuccessHtml( $this->user_account ));

                }else{
                    
                    $this->logError('Sorry, account creation failed');

                }

            }

            //  Update the ussd data
            $this->ussd['user_account'] = $this->user_account;
    
            //  Store the ussd data using the given item reference name
            $this->storeDynamicData('ussd', $this->ussd, false);

            //  Get the existing session metadata "revisit_text"
            $revisit_text = $this->existing_session->metadata['revisit_text'] ?? null;

            /** If we have the "revisit_text" which represents the destination that
             *  the subscriber was trying to access before they were prompted to 
             *  create an account, then we should revisit that destination. 
             */
            if( $revisit_text ){ 

                /** We can use the "revisit_text" to make a "Home Revisit" request
                 *  to implement our initial journey.
                 */
                return $this->handleHomeRevisit($revisit_text);

            }

            return null;

        }
    }

    public function getUserAccountDetails($user_account)
    {
        return collect($user_account)->only(['first_name', 'last_name', 'mobile_number', 'metadata']);
    }


















    /** This method converts a given value into a mathcing dynamic property. First it checks if
     *  the given value is a valid mustache tag. If it is a valid mustache tag, then we convert
     *  the given value into its dynamic value. If the value is not a valid mustache tag, then 
     *  we search for nested mustache tags embedded within the given value. We replace each
     *  and every matching tag into its appropriate data variable.
     * 
     *  If the value is immediately a valid mustage tag it can be directly converted and 
     *  returned as a String, Integer, Boolean, Array or Object.
     * 
     *  If the value is not immediately a valid mustache tag it can only be returned as
     *  a String with embedded mustache tags converted into their matching data values
     * 
     *  Therefore:
     * 
     *  "{{ products }}" can convert into a valid Array e.g
     * 
     *  {{ products }} = [
     *      ['id' => 1, 'name' => 'Product 1'],
     *      ['id' => 2, 'name' => 'Product 2']
     *  ]
     * 
     *  However "I love {{ products }}" will convert into a string with {{ products }}
     *  changed into its dynamic value which is then parsed into a string for rejoining
     *  with the string "I love " e.g
     * 
     *  "I love [['id' => 1, 'name' => 'Product 1'],['id' => 2, 'name' => 'Product 2']]"
     * 
     */
    public function convertValueStructureIntoDynamicData($data)
    {
        /** $data contains three main properties e.g 
         * 
         *  $data = [
         *      'text' => '{{ products }}',
         *      'code_editor_text' => '<?php ?>',
         *      'code_editor_mode' => false
         *  ];
         * 
         *  text: This represents and normal string, a mustache tag or a normal string with
         *        a mustache tag embbeded within it
         * 
         *  code_editor_text: This represents PHP code that must be processed
         *  code_editor_mode: This represents a true/false indication of whether the data
         *                    to proccess is embedded within the "text" property or the
         *                    "code_editor_text" property
         */

        $text = $data['text'];
        $code = $data['code_editor_text'];
        $code_editor_mode = $data['code_editor_mode'];

        //  If the content uses Code Editor Mode
        if ($code_editor_mode == true) {

            //  Process the PHP Code
            $outputResponse = $this->processPHPCode("$code");

        }else{

            //  If the text is set to "true"
            if($text === true){
                
                return true;

            //  If the text is set to "false"
            }elseif($text === false){
                
                return false;

            //  If the provided text is a valid mustache tag
            }elseif ($this->isValidMustacheTag($text, false)) {

                $mustache_tag = $text;
    
                // Convert the mustache tag into dynamic data
                $outputResponse = $this->convertMustacheTagIntoDynamicData($mustache_tag);
    
            //  If the provided value is not a valid mustache tag
            } else {
    
                //  Process dynamic content embedded within the text
                $outputResponse = $this->handleEmbeddedDynamicContentConversion($text);
    
            }

        }

        //  Return the build response
        return $outputResponse;
    }

    /** Validate if the given value uses valid mustache tag syntax 
     */
    public function isValidMustacheTag($text = null, $log_warning = true)
    {
        //  If we have the text to verify
        if (!empty($text)) {

            //  If the text to verify is of type String
            if (is_string($text)) {

                //  Remove the (\u00a0) special character which represents a no-break space in HTML
                $text = $this->remove_HTML_No_Break_Space($text);

                //  Remove any HTML or PHP tags
                $text = strip_tags($text);

                //  Remove left and right spaces
                $text = trim($text);
                
                /** Detect Dynamic Variables
                 *
                 *  Pattern Meaning:.
                 *  
                 *  ^ = Must start with the following rules listed below
                 * 
                 *  [{]{2} = The string must have exactly 2 opening curly braces e.g {{ not that "{{{" or "({{" or "[{{" will also pass
                 *
                 *  [\s]* = The string may have zero or more occurences of spaces e.g "{{company" or "{{ company" or "{{   company"
                 *
                 *  [a-zA-Z_]{1} = The first character at this point must be a lowercase or uppercase alphabet or an underscrore (_)
                 *                 e.g "{{ c" or "{{ company" or "{{ _company" but deny "{{ 123" or "{{ 123_company" e.t.c
                 *
                 *  [a-zA-Z0-9_\.]{0,} = After the first character the string may have zero or more occurances of lowercase or uppercase
                 *             alphabets, numbers, underscores (_) and periods (.) e.g "{{ company_123" or "{{ company.name" e.t.c
                 *
                 *  [\s]* = The string may have zero or more occurences of spaces afterwards "{{ company" or "{{ company   " e.t.c
                 *
                 *  [}]{2} = The string must end with exactly 2 closing curly braces e.g }} not that "}}}" or "}})" or "}}]" will also pass
                 *  
                 *  $ = Must end with the following rules listed above
                 * 
                 */
                $pattern = "/^[{]{2}[\s]*[a-zA-Z_]{1}[a-zA-Z0-9_\.]{0,}[\s]*[}]{2}$/";

                //  Check if the given data passes validation
                if (preg_match($pattern, $text)) {

                    //  Return true to indicate that this is a valid mustache tag
                    return true;

                }
            }
        }

        //  If we should log a warning
        if ($log_warning == true) {

            //  Incase the value received is not a string
            if (!is_string($text)) {

                //  Get the text type wrapped in html tags
                $dataType = $this->wrapAsSuccessHtml( $this->getDataType($text) );

                $this->logWarning('The provided mustache tag is not a valid mustache tag syntax. Instead we received a value of type ['.$dataType.']');
            
            } else {

                $this->logWarning('The provided mustache tag "'.$this->wrapAsSuccessHtml($text).'" is not a valid mustache tag syntax');

            }
        }

        //  Return false to indicate that this is not a valid mustache tag
        return false;
    }

    /** Remove the (\u00a0) special character which represents a no-break space in HTML.
     *  This can cause issues since it can make valid mustache tags look invalid
     *  e.g convert "{{ \u00a0users }}" into "{{ users }}"
     */
    public function remove_HTML_No_Break_Space($text = '')
    {
        return preg_replace('/\xc2\xa0/', '', $text);
    }

    /** Convert the given mustache tag into a valid matching dynamic value
     *  e.g "{{ first_name }}" into "John"
     */
    public function convertMustacheTagIntoDynamicData($mustache_tag)
    {
        //  Use the try/catch handles incase we run into any possible errors
        try {
            
            //  Convert "{{ products }}" into "$products"
            $outputResponse = $this->convertMustacheTagIntoPHPVariable($mustache_tag, true);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the converted variable
            $variable = $outputResponse;

            //  Convert the variable into its dynamic value e.g "$products" into "[ ['name' => 'Product 1', ...], ... ]"
            $outputResponse = $this->processPHPCode("return $variable;");

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            //  Get the generated output
            $output = $outputResponse;

            //  Get the output type wrapped in html tags
            $dataType = $this->wrapAsSuccessHtml( $this->getDataType($output) );

            //  Set an info log for the final conversion result
            $this->logInfo('Converted '.$this->wrapAsSuccessHtml($mustache_tag).' to ['.$dataType.']');

            //  Return the final output
            return $output;

        } catch (\Throwable $e) {

            //  Handle try catch error
            return $this->handleTryCatchError($e);

        } catch (Exception $e) {

            //  Handle try catch error
            return $this->handleTryCatchError($e);

        }
    }

    /** Convert the given mustache tag into a valid PHP variable e.g 
     * 
     *  1) {{ users }} into $users
     *  2) {{ product.id }} into $product->id
     * 
     *  Note that adding the "$" sign to the variable name is optional
     */
    public function convertMustacheTagIntoPHPVariable($text = null, $add_sign = false)
    {
        //  Use the try/catch handles incase we run into any possible errors
        try {

            //  If the text has been provided and is type of (String)
            if (!empty($text) && is_string($text)) {

                //  Remove the (\u00a0) special character which represents a no-break space in HTML
                $text = $this->remove_HTML_No_Break_Space($text);

                //  Remove any HTML or PHP tags
                $text = strip_tags($text);

                //  Replace all curly braces and spaces with nothing e.g convert "{{ company.name }}" into "company.name"
                $text = preg_replace("/[{}\s]*/", '', $text);

                //  Replace one or more occurences of the period with "." e.g convert "company..name" or "company...name" into "company.name"
                $text = preg_replace("/[\.]+/", '.', $text);

                //  Remove left and right spaces (If Any)
                $text = trim($text);

                //  Convert the dot syntaxt to array syntax e.g "company.details.name" into "company['details']['name']"
                $text = $this->convertDotSyntaxToArraySyntax($text);

                //  If we should add the PHP "$" sign
                if ($add_sign == true) {

                    //  Append the $ sign to the begining of the result e.g convert "company->name" into "$company->name"
                    $text = '$'.$text;

                }

                //  Return the converted text
                return $text;

            }

            return null;

        } catch (\Throwable $e) {

            //  Handle try catch error
            return $this->handleTryCatchError($e);

        } catch (Exception $e) {

            //  Handle try catch error
            return $this->handleTryCatchError($e);

        }
    }

    public function convertDotSyntaxToArraySyntax($text)
    {
        //  Start with an empty result
        $result = '';

        //  If the text provided has a value
        if( $text ){

            /** This following process converts the given $text with dot notation
             *  syntax e.g "data.value.nested_value" into a valid array notation
             *  synataxt e.g "data['value']['nested_value']". The returned value
             *  is not an actual array but a string that maintains the proper
             *  written syntax of an array that must then be proccessed to 
             *  get the actual value.
             */

            /** STEP 1
            * 
            *  Convert $text = "data.value.nested_value" into ['data', 'value', 'nested_value']

            */
            $properties = explode('.', $text);
    
            /** STEP 2 
            * 
            *  Iterate over the properties
            */
            for ($i = 0; $i < count($properties); $i++) { 
                
                /** STEP 3
                * 
                *  Foreach property e.g "data", "value" or "nested_value" property
                */

                //  If this is the first property e.g "data"
                if( $i == 0 ){
                    
                    //  This sets the first element e.g "data"
                    $result = $properties[$i];

                }else{
                    
                    //  This sets the follow-up elements e.g "data['value']" or "data['value']['nested_value']"
                    $result .= '[\''.$properties[$i].'\']';

                }
    
            }

        }

        //  Return the final result
        return $result;

    }

    /** Proccess and execute PHP Code
     */
    public function processPHPCode($code = 'return null', $log_dynamic_data = true)
    {
        //  Use the try/catch handles incase we run into any possible errors
        try {

            //  If we have dynamic data
            if (count($this->getDynamicData())) {

                //  Set an info log that we are setting variables with dynamic data
                if ($log_dynamic_data) {

                    $this->logInfo('Setting variables using stored dynamic data');

                }

                //  Create dynamic variables
                foreach ($this->getDynamicData() as $key => $value) {

                    /*  Foreach dataset use the iterator key to create the dynamic variable name and
                     *  assign the iterator value as the new variable value.
                     *
                     *  Example:
                     *
                     *  $data = ['product' => 'Orange', 'quantity' => 3, 'price' => 450, ...e.tc];
                     *
                     *  Foreach dataset, we produce dynamic variables e.g
                     *
                     *  $product = 'Orange';
                     *  $quantity = 3;
                     *  $price = 450;
                     *
                     *  ... e.t.c
                     */

                    if( is_object($value) ){
                        
                        ${$key} = $this->convertObjectToArray(json_decode($value));

                    }else{

                        ${$key} = $value;

                    }

                    //  Set an info log for the created variable and its dynamic data value
                    if ($log_dynamic_data) {

                        //  Get the value type wrapped in html tags
                        $dataType = $this->wrapAsSuccessHtml( $this->getDataType($value) );

                        //  Use json_encode($value) to show $value data instead of getDataType($value)
                        $this->logInfo('Variable '.$this->wrapAsSuccessHtml('$'.$key).' = ['.$dataType.']');
                    
                    }

                }

            }

            //  Process dynamic content embedded within the code
            $outputResponse = $this->handleEmbeddedDynamicContentConversion($code);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

            $code = $outputResponse;

            //  Remove the PHP tags from the PHP Code (If Any)
            $code = $this->removePHPTags($code);

            //  Execute PHP Code
            return eval($code);

        } catch (\Throwable $e) {

            //  Handle try catch error
            return $this->handleTryCatchError($e);

        } catch (Exception $e) {

            //  Handle try catch error
            return $this->handleTryCatchError($e);

        }
    }

    /** Remove PHP tags from the PHP Code
     */
    public function removePHPTags($code = '')
    {
        //  Remove PHP Tags
        $code = trim(preg_replace("/<\?php|\?>/i", '', $code));

        return $code;
    }

    /** Convert the given value into a valid JSON Object if the value is a 
     *  non empty Array, otherwise return the original value
     */
    public function convertToJsonObject($data = null)
    {
        // If the data is of type [Array]
        if (is_array($data)) {

            // If the [Array] has data
            if (!empty($data)) {

                //  Convert the data into a JSON Object and return
                return json_decode(json_encode($data));

            }

        }

        //  Return the data as is
        return $data;
    }

    /** Convert the given Object into a valid Array if its a
     *  valid Object otherwise return the original value
     */
    public function convertObjectToArray($data) 
    { 
        if (is_object($data)) { 
        
            $data = get_object_vars($data); 
        
        } 
        
        if (is_array($data)) { 
            
            return array_map(array($this, 'convertObjectToArray'), $data); 
        
        } else { 
            
            return $data; 
        
        } 
    
    }

    public function processActiveState($active_state)
    {   
        //  If the active state property was found
        if( $active_state ){

            //  If the active status is set to yes
            if( $active_state['selected_type'] == 'yes' ){

                //  Return true to indicate that the state is active
                return true;

            }elseif( $active_state['selected_type'] == 'no' ){

                //  Return false to indicate that the state is not active
                return true;

            }elseif( $active_state['selected_type'] == 'conditional' ){

                $code = $active_state['code'];

                //  Process the PHP Code
                return $this->processPHPCode("$code");

            }

        }

    }

    /** Convert mustache tags embedded within the given string into their corresponding
     *  matching dynamic values. The final value returned is always of type String
     */
    public function handleEmbeddedDynamicContentConversion($text = '')
    {
        //  Use the try/catch handles incase we run into any possible errors
        try {

            //  Remove the (\u00a0) special character which represents a no-break space in HTML
            $text = $this->remove_HTML_No_Break_Space($text);

            //  Get all instances of mustache tags within the given text
            $result = $this->getInstancesOfMustacheTags($text);

            //  Get the total number of mustache tags found within the given text
            $number_of_mustache_tags = $result['total'];

            //  Get the mustache tags found within the given text
            $mustache_tags = $result['mustache_tags'];
            
            //  If we managed to detect one or more mustache tags
            if ($number_of_mustache_tags) {

                //  Foreach mustache tag we must convert it into a php variable
                foreach ($mustache_tags as $mustache_tag) {

                    //  Convert "{{ company.name }}" into "$company->name"
                    $dynamic_variable = $this->convertMustacheTagIntoPHPVariable($mustache_tag, true);

                    //  Convert the dynamic property into its dynamic value e.g "$company->name" into "Company XYZ"
                    $outputResponse = $this->processPHPCode("return $dynamic_variable;");

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

                    //  Get the generated output
                    $output = $outputResponse;

                    //  Incase the dynamic value is not a string, integer or float
                    if (!is_string($output) && !is_integer($output) && !is_float($output)) {

                        //  Get the output type wrapped in html tags
                        $dataType = $this->wrapAsSuccessHtml( $this->getDataType($output) );

                        //  Use json_encode($value) to show $value data instead of getDataType($value)
                        $this->logInfo('Converting '.$this->wrapAsSuccessHtml($mustache_tag).' into ['.$dataType.']');
                        
                    }else{

                        //  Set an info log that we are converting the dynamic property to its associated value
                        $this->logInfo('Converting '.$this->wrapAsSuccessHtml($mustache_tag).' into '.$this->wrapAsSuccessHtml($output));  

                    }

                    //  Use json_encode for any Object, Array, Boolean e.t.c in order to convert the output into a String format
                    $output = $this->convertToString( $output );

                    //  Replace the mustache tag with its dynamic data e.g replace "{{ company.name }}" with "Company XYZ"
                    $text = preg_replace("/$mustache_tag/", $output, $text);

                }
            }

            //  Return the converted text
            return $text;

        } catch (\Throwable $e) {

            //  Handle try catch error
            return $this->handleTryCatchError($e);

        } catch (Exception $e) {

            //  Handle try catch error
            return $this->handleTryCatchError($e);

        }
    }

    public function getInstancesOfMustacheTags($text = '')
    {
        //  Remove the (\u00a0) special character which represents a no-break space in HTML
        $text = $this->remove_HTML_No_Break_Space($text);
                
        /** Detect Dynamic Variables
         *
         *  Pattern Meaning:
         * 
         *  [{]{2} = The string must have exactly 2 opening curly braces e.g {{ not that "{{{" or "({{" or "[{{" will also pass
         *
         *  [\s]* = The string may have zero or more occurences of spaces e.g "{{company" or "{{ company" or "{{   company"
         *
         *  [a-zA-Z_]{1} = The first character at this point must be a lowercase or uppercase alphabet or an underscrore (_)
         *                 e.g "{{ c" or "{{ company" or "{{ _company" but deny "{{ 123" or "{{ 123_company" e.t.c
         *
         *  [a-zA-Z0-9_\.]{0,} = After the first character the string may have zero or more occurances of lowercase or uppercase
         *             alphabets, numbers, underscores (_) and periods (.) e.g "{{ company_123" or "{{ company.name" e.t.c
         *
         *  [\s]* = The string may have zero or more occurences of spaces afterwards "{{ company" or "{{ company   " e.t.c
         *
         *  [}]{2} = The string must end with exactly 2 closing curly braces e.g }} not that "}}}" or "}})" or "}}]" will also pass
         *  
         */
        $pattern = "/[{]{2}[\s]*[a-zA-Z_]{1}[a-zA-Z0-9_\.]{0,}[\s]*[}]{2}/";

        $total_results = preg_match_all($pattern, $text, $results);

        /*
         * The "$total_results" represents the number of matched mustache tags e.g
         *
         * $total_results = 3;
         *
         * The "$results[0]" represents an array of the matched mustache tags
         *
         * $results[0] = [
         *      "{{ company.name }}",
         *      "{{ company.branches.total }}",
         *      "{{ company.details.contacts.phone }}",
         *      ... e.t.c
         *  ];
         */
        return ['total' => $total_results, 'mustache_tags' => $results[0]];
    }

    public function convertToString($data = '')
    {   
        //  If the given data is not a string
        if( !is_string($data) ){

            //  If the data is an array
            if( is_array($data) || is_object($data) || is_bool($data) ){
                
                $data = json_encode($data);

            }

            //  Cast data into a string format
            $data = (String) $data;

        }

        //  Return data without HTML or PHP tags
        return strip_tags($data);
    }

    public function convertToInteger($data = 0)
    {        
        /** This will render as: $this->convertToString($data)
         *  while being called within a try/catch handler
         */
        $outputResponse = $this->tryCatch('convertToString', [$data]);
        
        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) return $outputResponse;

        return floatval( $outputResponse );
    
    }

    /** This method gets the validation rule and callback. The callback represents the name of
     *  the validation function that we must run to validate the current input target. Since
     *  we allow custom Regex patterns for custom validation support, we must perform this under
     *  a try/catch incase the provided custom Regex pattern is invalid. This will allow us to
     *  catch any emerging error and be able to use the handleFailedValidation() in order to
     *  display the fatal error message and additional debugging details.
     */
    public function tryCatch($callback, $callback_params = [])
    {
        try {
            /** Run the custom function here. 
             * 
             *  The $callback is the method/function that we must run to e.g 
             * 
             *  If $callback = 'custom_method_1'
             * 
             *  Then this will call "$this->custom_method_1()"
             * 
             *  The $callback_params represents an array of values that must be passed to the 
             *  method/function to become the method/function arguments e.g
             * 
             *  If $callback_params = ['value_1', 'value_2', ...]
             * 
             *  Then this will allow for "$this->custom_method_1('value_1', 'value_2', ...)"
             * 
             *  The result will be a custom function that will be run within the try/catch
             *  block to catch any bad exceptions that may be triggered
             * 
             */

            return call_user_func_array(array($this, $callback), $callback_params);

        } catch (\Throwable $e) {

            //  Handle try catch error
            return $this->handleTryCatchError($e);

        } catch (Exception $e) {

            //  Handle try catch error
            return $this->handleTryCatchError($e);

        }
    }

    /** This method is used to handle errors caught during try-catch screnerios. 
     *  It logs the error, indicates that an error occured and returns null.
     */
    public function handleTryCatchError($error, $load_display = true)
    {
        //  Set an error log
        $this->logError('Error:  '.$error->getMessage());

        if ($load_display) {

            //  Show the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();

        }
    }

    /**********************
     *  LOGGING METHODS   *
     **********************/

    /** This method is used to log information about the USSD
     *  application build process.
     */
    public function logInfo($description = '')
    {
        $this->addLog(['type' => 'info', 'description' => $description]);
    }

    /** This method is used to log warnings about the USSD
     *  application build process.
     */
    public function logWarning($description = '')
    {
        $this->addLog(['type' => 'warning', 'description' => $description]);
    }

    /** This method is used to log errors about the USSD
     *  application build process.
     */
    public function logError($description = '')
    {
        $this->addLog(['type' => 'error', 'description' => $description]);
    }

    /** This method is used to add a log
     */
    public function addLog($data)
    {
        //  Set additional information
        $data['level'] = $this->level ?? null;
        $data['screen'] = $this->screen['id'] ?? null;
        $data['display'] = $this->display['id'] ?? null;

        //  Push the latest log update
        array_push($this->log, $data);
    }

    /*******************************
     *  SCREEN DETECTING METHODS   *
     ******************************/

    /** Check if the given content indicates if this is a continuing
     *  screen. This means that the user will be able to make a 
     *  reply to continue the session
     */
    public function isContinueScreen($text = '')
    {
        if (is_string($text) && !empty($text)) {
            //  If the first 3 characters of the text match the word "CON" then this is a continuing screen
            return  (substr($text, 0, 3) == 'CON') ? true : false;
        }

        return false;
    }

    /** Check if the given content indicates if this is an ending
     *  screen. This means that the user will not be able to make 
     *  a reply. The session will have been closed
     */
    public function isEndScreen($text = '')
    {
        if (is_string($text) && !empty($text)) {
            //  If the first 3 characters of the text match the word "END" then this is an ending screen
            return  (substr($text, 0, 3) == 'END') ? true : false;
        }

        return false;
    }

    /** Check if the given content indicates if this is a redirecting
     *  screen. This means that we will be redirecting the user to
     *  the provided Service Code
     */
    public function isRedirectScreen($text = '')
    {
        if (is_string($text) && !empty($text)) {
            //  If the first 3 characters of the text match the word "RED" then this is a redirecting screen
            return  (substr($text, 0, 3) == 'RED') ? true : false;
        }

        return false;
    }

    /** Check if the given content indicates if this is a timeout
     *  screen. This means that the user's session has ended due
     *  to a delayed response.
     */
    public function isTimeoutScreen($text = '')
    {
        if (is_string($text) && !empty($text)) {
            //  If the first 3 characters of the text match the word "TIM" then this is a timeout screen
            return  (substr($text, 0, 3) == 'TIM') ? true : false;
        }

        return false;
    }

    /** Check if the given content indicates if this is a screen
     */
    public function shouldDisplayScreen($text = '')
    {
        //  If the given text is a valid String
        if (is_string($text)) {

            //  Check if the current text represents any given screen
            return ($this->isContinueScreen($text) ||     
                    $this->isRedirectScreen($text) ||   
                    $this->isTimeoutScreen($text)  ||   
                    $this->isEndScreen($text))
                    ? true : false;

        }

        return false;
    }

    /**************************
     *  SHOW SCREEN METHODS   *
     **************************/

    /** This is the screen displayed when we want to still continue the session.
     *  We therefore display the custom message.
     */
    public function showCustomScreen($message = '', $options = [])
    {
        $default_options = [
            'continue' => true,
            'use_line_breaker' => true,
            'show_go_back' => false,
        ];

        $options = array_merge($default_options, $options);

        $response = $options['continue'] ? 'CON ' : 'END ';
        $response .= $message;
        $response .= $options['use_line_breaker'] ? "\n" : '';
        $response .= $options['show_go_back'] ? '0. Go Back' : '';

        return trim($response);
    }

    /** This is the screen displayed when a problem was encountered and but we want
     *  to still continue the session. We therefore display the custom error
     *  message but also display the option to go back.
     */
    public function showCustomGoBackScreen($message = '', $options = [])
    {
        $default_options = [
            'show_go_back' => true,
        ];

        $options = array_merge($default_options, $options);

        $response = $this->showCustomScreen($message, $options);

        return $response;
    }

    /** This is the screen displayed when a problem was encountered and we want
     *  to end the session with a custom error message.
     */
    public function showCustomErrorScreen($error_message = '', $options = [])
    {
        $default_options = [
            'continue' => false,
        ];

        $options = array_merge($default_options, $options);

        $response = $this->showCustomScreen($error_message, $options);

        return $response;
    }

    /** This is the screen displayed when we have experienced technical difficulties 
     *  and we want to end the session with a general error message.
     */
    public function showTechnicalDifficultiesErrorScreen()
    {
        $response = $this->showCustomErrorScreen($this->default_technical_difficulties_message);

        return $response;
    }

    /** This is the screen displayed when the USSD session times out
     */
    public function showTimeoutScreen($timeout_message)
    {
        return 'TIM ' . $timeout_message;   
    }

    /** This is the screen displayed when we want to redirect the current
     *  session to another USSD Service Code
     */
    public function showRedirectScreen($service_code)
    {
        return 'RED ' . $service_code;   
    }


}