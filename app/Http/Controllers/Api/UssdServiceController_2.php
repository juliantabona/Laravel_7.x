<?php

namespace App\Http\Controllers\Api\UssdBuilder;

use DB;
use App\UssdServiceCode;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UssdServiceController extends Controller
{
    private $text = null;
    private $test;
    private $event;
    private $screen;
    private $builder;
    private $screens;
    private $display;
    private $log = [];
    private $displays;
    private $level = 1;
    private $session_id;
    private $api_trigger;
    private $linked_screen;
    private $service_code;
    private $phone_number;
    private $request_type;
    private $linked_display;
    private $ussdInterface;
    private $is_paginating;
    private $display_content;
    private $display_actions;
    private $pagination_index;
    private $existing_session;
    private $current_user_response;
    private $generated_variables;
    private $display_instructions;
    private $dynamic_data_storage = [];
    private $incorrect_option_selected;
    private $last_recorded_log_microtime;
    private $forward_navigation_step_number;
    private $backward_navigation_step_number;

    public function __construct(Request $request)
    {
        //  Get the "Message"
        $this->msg = $request->get('msg');

        //  Get the "Msisdn"
        $this->msisdn = $request->get('msisdn');

        //  Get the "Session ID"
        $this->session_id = $request->get('sessionId');

        //  Get the "Request Type"
        $this->request_type = $request->get('requestType');

        //  Get the "TEST MODE" status
        $this->test_mode = ($request->get('testMode') == 'true' || $request->get('testMode') == '1') ? true : false;

        //  Reset the display pagination settings
        $this->resetPagination();
    }

    public function home()
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

        //  HANDLE REQUEST

        //  If the "Request Type" is "1"
        if ($this->request_type == '1') {

            //  Handle a new session
            $response = $this->handleNewSession();

        //  If the "Request Type" is "2"
        } elseif ($this->request_type == '2') {

            //  Handle existing session
            $response = $this->handleExistingSession();
        }

        //  HANDLE RESPONSE

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


        if( $this->test_mode ){

            return response($response)->header('Content-Type', 'text/plain');

        }else{

            $data = [
                'ussd' => [
                    'type' => $response['request_type'],
                    'msg' => $response['msg'],
                ],
            ];

            return response()->xml($data);

        }
    }

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
        $allow_timeout = $this->builder['simulator']['settings']['allow_timouts'];

        //  Get the timeout limit in seconds e.g "120" to mean "timeout after 120 seconds"
        $timeout_limit_in_seconds = $this->getTimeoutLimitInSeconds();

        //  Create new session
        $session = DB::table('ussd_sessions')->insert(
            [
                'text' => $this->text,
                'msisdn' => $this->msisdn,
                'session_id' => $this->session_id,
                'allow_timeout' => $allow_timeout,
                'service_code' => $this->service_code,
                'request_type' => $this->request_type,
                'created_at' => (\Carbon\Carbon::now())->format('Y-m-d H:i:s'),
                'updated_at' => (\Carbon\Carbon::now())->format('Y-m-d H:i:s'),
                'timeout_at' => (\Carbon\Carbon::now())->addSeconds($timeout_limit_in_seconds)->format('Y-m-d H:i:s'),
            ]
        );

        //  Handle the current session
        return $this->handleSession();
    }

    public function handleExistingSession($buildResponse = true)
    {
        //  Get the existing session record from the database
        $this->existing_session = $this->getExistingSessionFromDatabase();

        //  Update the current session service code
        $this->service_code = $this->existing_session->service_code;

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
                 *  "John*1*2*3"
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
        $update = DB::table('ussd_sessions')->where('session_id', $this->session_id)->update([
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

    public function getServiceCodeFromMessage(){

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
         *  We need to extract the first value "321" to create "*321#"
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
         *  the "Service Code". The rest of the value i.e "3*4*5" will 
         *  be used as the "Message" value.
         * 
         *  Therefore
         * 
         *  $this->service_code = *321*3#
         * 
         *  $this->msg = 3*4*5
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
         *  $this->service_code = *150# or *321*3#
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

        //  Remove empty values
        $values = array_values(array_filter($values, function ($value) {
            return ($value !== '');
        }));

        //  Get the Main Ussd Shared Service Code e.g *321#
        $shared_service_code = config('app.USSD_SERVICE_CODE');

        //  Remove the "*" and "#" symbol from the Shared Code of the Main Ussd Service Code e.g from "*321#" to "321"
        $shared_service_code_number = str_replace(['*', '#'], '', $shared_service_code);

        //  Get the current Ussd Service Code e.g 
        $current_service_code_number = $values[0];

        //  Remove the first value and assign it to the "$first_number" variable
        $first_number = array_shift($values);

        //  If the current Ussd Service Code is the same as the shared Ussd Service Code
        if( $current_service_code_number == $shared_service_code_number ){

            //  Remove the first value and assign it to the "$second_number" variable
            $second_number = array_shift($values);
    
            //  Use the first and second value as the Ussd Service Code e.g *321*45#
            $this->service_code = '*'.$first_number.'*'.$second_number.'#';

            
        //  If the current Ussd Service Code is the same as the shared Ussd Service Code (i.e This is a dedicated Ussd Service Code)
        }else{
    
            //  Use the first value as the service code e.g *150#
            $this->service_code = '*'.$first_number.'#';

        }
    
        //  Use the rest of the values as the message e.g 3*4*5
        $this->msg = implode('*', $values);
        
    }

    public function getExistingSessionFromDatabase()
    {
        if( empty( $this->existing_session ) ){

            //  Get the session record that matches the given Session Id
            return \App\UssdSession::where('session_id', $this->session_id)->first();

        }else{

            return $this->existing_session;

        }
    }

    public function handleTimeout()
    {
        //  Set the timeout message
        $this->msg = $this->builder['simulator']['settings']['timeout_message'];

        //  If the timeout message was not provided
        if (empty($this->msg)) {

            //  Get the default timeout message found in Class "UssdService" within Class "UssdServiceTraits"
            $default_timeout_msg = (new \App\UssdService())->default_timeout_message;

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

    public function getTimeoutLimitInSeconds()
    {   
        //  Get the timeout limit in seconds e.g "120" to mean "timeout after 120 seconds"
        return $this->builder['simulator']['settings']['timeout_limit_in_seconds'];
    }

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

    public function buildResponse($response)
    {
        /* Get the response message for display to the user e.g
         *
         *  Extract "Welcome, Enter Username" from "CON Welcome, Enter Username"
         *  Extract "Payment Successful" from "END Payment Successful"
         */
        $this->msg = $this->getResponseMsg($response);

        if ($this->isContinueScreen($response)) {

            //  Continue response
            $this->request_type = '2';

        } elseif ($this->isEndScreen($response)) {

            //  End response
            $this->request_type = '3';

        } elseif ($this->isTimeoutScreen($response)) {

            //  Redirect response
            $this->request_type = '4';

        } elseif ($this->isRedirectScreen($response)) {

            //  Redirect response
            $this->request_type = '5';

        }

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
                $response['logs'] = $this->log;
            }
        }

        return $response;
    }

    public function startBuildingUssd()
    {
        //  Set a log that the build process has started
        $this->logInfo('Building USSD Application');

        //  Check if the USSD screens exist
        $doesNotExistResponse = $this->handleNonExistentBuilder();

        //  Locally store the current session details
        $this->storeSessionDetails();

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($doesNotExistResponse)) {
            return $doesNotExistResponse;
        }

        //  Start building and displaying the ussd screens
        return $this->startBuildingUssdScreens();
    }

    public function getUssdBuilder()
    {
        //  If we don't have the builder
        if (empty($this->builder)) {

            //  Get the Ussd Service Code Record from the database
            $ussd_service_code = UssdServiceCode::where('shared_code', $this->service_code)->first();
    
            //  If we have a matching service code
            if ($ussd_service_code) {

                //  Get the owning resource (i.e Ussd Service)
                $owner = $ussd_service_code->owner;
                
                //  If the owner exists
                if ($owner) {

                    //  Get the owning resource builder
                    $this->builder = $owner->builder;

                }

            }

        }else{

            //  Return the current builder
            return $this->builder;

        }
    }

    public function handleNonExistentBuilder()
    {
        //  If we don't have a builder
        if (empty($this->builder)) {
            //  Set a warning log that we could not find the Ussd Builder
            $this->logWarning('Ussd builder was not found');

            //  Display the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();
        }
    }

    public function storeSessionDetails()
    {
        $this->ussd = [
            'text' => $this->text,
            'msisdn' => $this->msisdn,
            'session_id' => $this->session_id,
            'request_type' => $this->request_type,
            'service_code' => $this->service_code,
            'user_responses' => $this->getUserResponses(),
            'user_response' => $this->msg,
        ];

        //  Store the ussd data using the given item reference name
        $this->storeDynamicData('ussd', $this->ussd);
    }

    /******************************************
     *  SCREEN METHODS                        *
     *****************************************/

    /*  startBuildingUssdScreens()
     *  This method uses the ussd builder metadata get all the ussd screens,
     *  locate the first screen and start building each display screen that
     *  must be returned.
     */
    public function startBuildingUssdScreens()
    {
        //  Check if the USSD screens exist
        $doesNotExistResponse = $this->handleNonExistentScreens();

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($doesNotExistResponse)) {
            return $doesNotExistResponse;
        }

        //  Get the first screen
        $this->getFirstScreen();

        //  Handle current screen
        $response = $this->handleCurrentScreen();

        /** Check if the display data returned is greater than 160 characters.
         *  If it is set a warning log. Subtract out the first five characters
         *  first to remove the "CON " and "END ".
         */
        $characters = (strlen($response) - 4);

        if ($characters > 160) {
            //  Set a warning log that the content received is too long
            $this->logWarning('The screen content exceeds the maximum allowed content length of 160 characters. Returned <span class="text-success">'.$characters.'</span> characters');
        } else {
            //  Set an info log of the content character length
            $this->logInfo('Content Characters: <span class="text-success">'.$characters.'</span> characters');
        }

        return $response;
    }

    /*  handleNonExistentScreens()
     *  This method checks if we have any screens to display. If we don't we
     *  log a warning and display the technical difficulties screen.
     */
    public function handleNonExistentScreens()
    {
        //  Check if the screens exist
        if ($this->checkIfScreensExist() != true) {
            //  Set a warning log that we could not find any screens
            $this->logWarning('No screens found');

            //  Display the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();
        }

        //  Return null if we have screens
        return null;
    }

    /*  checkIfScreensExist()
     *  This method checks if the USSD metadata has any screens we can display.
     *  It will return true if we have screens to display and false if we don't
     *  have screens to display.
     */
    public function checkIfScreensExist()
    {
        //  Check if the screen metadata is an array that its not empty
        if (is_array($this->builder['screens']) && !empty($this->builder['screens'])) {
            //  Return true to indicate that the screens exist
            return true;
        }

        //  Return false to indicate that the screens do not exist
        return false;
    }

    /*  getFirstScreen()
     *  This method gets the first screen that we should display. First we look
     *  for a screen indicated by the user. If we can't locate that screen we
     *  then default to the first available screen that we can display.
     */
    public function getFirstScreen()
    {
        //  Set an info log that we are searching for the first screen
        $this->logInfo('Searching for the first screen');

        //  Get all the screens available
        $this->screens = $this->builder['screens'];

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

        //  Set an info log for the first selected screen
        $this->logInfo('Selected <span class="text-primary">'.$this->screen['name'].'</span> as the first screen');
    }

    /*  handleCurrentScreen()
     *  This method first checks if the screen we want to handle exists. This could be the
     *  first display screen or any linked screen. In either case if the screen does not
     *  exist we log a warning and display the technical difficulties screen. We then check
     *  if the user has already responded to the current screen. If (No) then we build
     *  and return the current screen. If (Yes) then we need to validate, format and
     *  store the users response respectively if specified.
     */
    public function handleCurrentScreen()
    {
        //  Check if the current screen exists
        $doesNotExistResponse = $this->handleNonExistentScreen();

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($doesNotExistResponse)) {
            return $doesNotExistResponse;
        }

        //  Check if the current screen repeats
        if ($this->checkIfScreenRepeats()) {
            //  Handle before repeat events
            $handleEventsResponse = $this->handleBeforeRepeatEvents();

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($handleEventsResponse)) {
                return $handleEventsResponse;
            }

            //  Handle the repeat screen
            $handleScreenResponse = $this->handleRepeatScreen();

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($handleScreenResponse)) {
                return $handleScreenResponse;
            }

            //  Handle after repeat events
            $handleEventsResponse = $this->handleAfterRepeatEvents();

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($handleEventsResponse)) {
                return $handleEventsResponse;
            }
        } else {
            //  Start building the current screen displays
            return $this->startBuildingDisplays();
        }
    }

    public function handleNonExistentScreen()
    {
        /* Note that the checkIfScreensExist() helps us make sure that the first screen is always available.
         *  If its not available we use the handleNonExistentScreens() to take care of that. This means that
         *  we never have to worry about the first screen, however any other screen that we link to must be
         *  verified for existence.
         */

        //  If the linked screen is not available
        if (empty($this->screen)) {
            //  Set a warning log that the linked screen could not be found
            $this->logWarning('The linked screen could not be found');

            //  Return the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();
        }

        return null;
    }

    public function checkIfScreenRepeats()
    {
        //  Set an info log that we are checking if the current screen repeats
        $this->logInfo('Checking if the screen should repeat');

        //  If the screen is set to repeats
        if ($this->screen['type']['selected_type'] == 'repeat') {
            //  Set an info log that the current screen does repeat
            $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span> does repeat');

            //  Return true to indicate that the screen does repeat
            return true;
        }

        //  Set an info log that the current screen does not repeat
        $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span> does not repeat');

        //  Return false to indicate that the screens does not repeat
        return false;
    }

    public function handleRepeatScreen()
    {
        //  Get the repeat type e.g "repeat_on_number" or "repeat_on_items"
        $repeatType = $this->screen['type']['repeat']['selected_type'];

        //  If the screen is set to repeats
        if ($repeatType == 'repeat_on_number') {
            //  Set an info log that the current screen repeats on a given number
            $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span> repeats on a given number');

            //  Handle repeat screen on number
            return $this->startRepeatScreen('repeat_on_number');
        } elseif ($repeatType == 'repeat_on_items') {
            //  Set an info log that the current screen repeats on a set of items
            $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span> repeats on a group of items');

            //  Handle repeat screen on items
            return $this->startRepeatScreen('repeat_on_items');
        }
    }

    public function startRepeatScreen($type)
    {
        if ($type == 'repeat_on_items') {
            $repeat_data = $this->screen['type']['repeat']['repeat_on_items'];

            //  Get the group reference value (Usually in mustache tag format) e.g "{{ products }}"
            $mustache_tag = $repeat_data['group_reference'];

            //  Get the current item reference name e.g "product"
            $item_reference_name = $repeat_data['item_reference_name'];

            //  Get the total options reference name e.g "total_products"
            $total_loops_reference_name = $repeat_data['total_loops_reference_name'];

            //  Convert "{{ products }}" into "$products"
            $variable = $this->convertMustacheTagIntoPHPVariable($mustache_tag, true);

            //  Convert the dynamic property into its dynamic value e.g "$products" into "[ ['name' => 'Product 1', ...], ... ]"
            $outputResponse = $this->processPHPCode("return $variable;");

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) {
                return $outputResponse;
            }

            //  Get the generated output e.g "A list of products"
            $items = $outputResponse;

            //  If the dynamic value is a string, integer or float
            if (is_string($items) || is_integer($items) || is_float($items)) {
                //  Set an info log that we are converting the dynamic property to its associated value
                $this->logInfo('Converting <span class="text-success">'.$mustache_tag.'</span> to <span class="text-success">'.$items.'</span>');

            //  Incase the dynamic value is not a string, integer or float
            } else {
                $dataType = ucwords(gettype($items));

                //  Set an info log that we are converting the dynamic property to its associated value
                $this->logInfo('Converting <span class="text-success">'.$mustache_tag.'</span> to <span class="text-success">['.$dataType.']</span>');
            }
        } elseif ($type == 'repeat_on_number') {
            $repeat_data = $this->screen['type']['repeat']['repeat_on_number'];

            $repeat_number = $repeat_data['value'];

            //  If the provided repeat number is a valid mustache tag
            if ($this->isValidMustacheTag($repeat_number, false)) {
                $mustache_tag = $repeat_number;

                // Convert the mustache tag into dynamic data
                $outputResponse = $this->convertMustacheTagIntoDynamicData($mustache_tag);

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) {
                    return $outputResponse;
                }

                $variable = (int) $outputResponse;
            } else {
                $variable = (int) $repeat_number;
            }

            //  If the dynamic value is a number equal to zero
            if ($variable == 0) {
                //  Set an info log that we are converting the dynamic property to its associated value
                $this->logInfo('The repeat number has a value = <span class="text-success">0</span>, therefore we won\'t be able to loop and repeat the screen');
            }

            /** Fill the $items with an array of values starting with Index = 0. Add items equal to the $variable number
             *  example results:.
             *
             *  array_fill(0, 5, 'item') = ['item', 'item', 'item', 'item', 'item'];
             */
            $items = array_fill(0, $variable, 'item');
        }

        //  Get the current loop index reference name e.g "product_index"
        $loop_index_reference_name = $repeat_data['loop_index_reference_name'];

        //  Get the current loop number reference name e.g "product_number"
        $loop_number_reference_name = $repeat_data['loop_number_reference_name'];

        //  Get the reference name for confirming if the current item is the first item e.g "is_first_product"
        $is_first_loop_reference_name = $repeat_data['is_first_loop_reference_name'];

        //  Get the reference name for confirming if the current item is the last item e.g "is_last_product"
        $is_last_loop_reference_name = $repeat_data['is_last_loop_reference_name'];

        //  Check if the given options are of type Array
        if (is_array($items)) {
            if (count($items) > 0) {
                for ($x = 0; $x < count($items); ++$x) {
                    //  Set an info log that we are converting the dynamic property to its associated value
                    $this->logInfo('<span class="text-success">'.$this->screen['name'].'</span> repeat instance <span class="text-success">['.($x + 1).']</span>');

                    if ($type == 'repeat_on_items') {
                        //  If the item reference name is provided
                        if (!empty($item_reference_name)) {
                            //  Store the current item using the given item reference name
                            $this->storeDynamicData($item_reference_name, $items[$x]);
                        }

                        //  If the total options reference name is provided
                        if (!empty($total_loops_reference_name)) {
                            //  Store the current total options using the given reference name
                            $this->storeDynamicData($total_loops_reference_name, count($items));
                        }
                    }

                    //  If the item index reference name is provided
                    if (!empty($loop_index_reference_name)) {
                        $this->logInfo('Index <span class="text-success">['.$x.']</span>');
                        //  Store the current item index using the given item reference name
                        $this->storeDynamicData($loop_index_reference_name, $x);
                    }

                    //  If the item number reference name is provided
                    if (!empty($loop_number_reference_name)) {
                        $this->logInfo('Number <span class="text-success">['.($x + 1).']</span>');
                        //  Store the current item number using the given item reference name
                        $this->storeDynamicData($loop_number_reference_name, ($x + 1));
                    }

                    //  If the first item reference name is provided
                    if (!empty($is_first_loop_reference_name)) {
                        //  Store the true/false result for first item using the given item reference name
                        $this->storeDynamicData($is_first_loop_reference_name, ($x == 0));
                    }

                    //  If the last item reference name is provided
                    if (!empty($is_last_loop_reference_name)) {
                        //  Store the true/false result for last item using the given item reference name
                        $this->storeDynamicData($is_last_loop_reference_name, (($x + 1) == count($items)));
                    }

                    //  Start building the current screen displays
                    $buildResponse = $this->startBuildingDisplays();

                    //  If we must navigate forward then proceed to next iteration otherwise continue
                    if ($buildResponse == 'navigate-forward') {
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
                             *  $this->forward_navigation_step_number = 1, 2, 3 ... e.t.c
                             */
                            $step = $this->forward_navigation_step_number;

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
                                    $this->logInfo('Navigating to <span class="text-success">Item #'.($x + $y + 1).'</span>');

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
                            $this->logInfo('<span class="text-success">'.$this->screen['name'].'</span> has reached the last loop');

                            //  Get the "After Last Loop Behaviour Type" e.g "do_nothing", "link"
                            $after_last_loop = $repeat_data['after_last_loop']['selected_type'];

                            if ($after_last_loop == 'do_nothing') {
                                $this->logInfo('<span class="text-success">'.$this->screen['name'].'</span> is defaulting to showing the last loop display');

                            //  Do nothing else
                            } elseif ($after_last_loop == 'link') {
                                $this->logInfo('<span class="text-success">'.$this->screen['name'].'</span> is attempting to link to another screen');

                                //  Get the provided link (The display or screen we must link to after the last loop of this screen)
                                $link = $repeat_data['after_last_loop']['link'] ?? null;

                                //  If the screen link name was provided
                                if ($link['name']) {
                                    $this->logInfo('<span class="text-success">'.$this->screen['name'].'</span> is linking to <span class="text-success">'.$link['name'].'</span>');

                                    //  Get the screen matching the given link name and set it as the current screen
                                    $this->screen = $this->getScreenByName($link['name']);

                                    //  Start building the current screen displays
                                    return $this->startBuildingDisplays();
                                }
                            }
                        }

                        //  Do nothing else so that we iterate to the next specified item on the list
                    } elseif ($buildResponse == 'navigate-backward') {
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
                         *  $this->backward_navigation_step_number = 1, 2, 3 ... e.t.c
                         */
                        $step = $this->backward_navigation_step_number;

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
                                $this->logInfo('Navigating to <span class="text-success">Item #'.($x - $y + 1).'</span>');

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
                $this->logWarning('<span class="text-success">'.$this->screen['name'].'</span> has <span class="text-success">0</span> loops. For this reason we cannot repeat over the screen displays');

                //  Get the "No Loop Behaviour Type" e.g "do_nothing", "link"
                $on_no_loop_type = $repeat_data['on_no_loop']['selected_type'];

                if ($on_no_loop_type == 'do_nothing') {
                    $this->logInfo('<span class="text-success">'.$this->screen['name'].'</span> is defaulting to building and showing its first display');

                //  Do nothing else
                } elseif ($on_no_loop_type == 'link') {
                    $this->logInfo('<span class="text-success">'.$this->screen['name'].'</span> is attempting to link to another screen');

                    //  Get the provided link (The display or screen we must link to if we don't have loops for this screen)
                    $link = $repeat_data['on_no_loop']['link'] ?? null;

                    //  If the screen link name was provided
                    if ($link['name']) {
                        $this->logInfo('<span class="text-success">'.$this->screen['name'].'</span> is linking to <span class="text-success">'.$link['name'].'</span>');

                        //  Get the screen matching the given link name and set it as the current screen
                        $this->screen = $this->getScreenByName($link['name']);
                    }
                }

                //  Start building the current screen displays
                return $this->startBuildingDisplays();
            }
        } else {
            $dataType = ucwords(gettype($items));

            //  Set a warning log that the dynamic property is not an array
            $this->logWarning('The <span class="text-success">'.$mustache_tag.'</span> provided must be of type <span class="text-success">[Array]</span> however we received type of <span class="text-success">['.$dataType.']</span>. For this reason we cannot repeat on options');
        }
    }

    /******************************************
     *  DISPLAY METHODS                        *
     *****************************************/

    /*  startBuildingDisplays()
     *  This method uses the current screen to get all the displays,
     *  locate the first display and start building each display that
     *  must be returned.
     */
    public function startBuildingDisplays()
    {
        //  Check if the current screen displays exist
        $doesNotExistResponse = $this->handleNonExistentDisplays();

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($doesNotExistResponse)) {
            return $doesNotExistResponse;
        }

        //  Get the first display
        $this->getFirstDisplay();

        //  Handle current display
        return $this->handleCurrentDisplay();
    }

    /*  handleNonExistentDisplays()
     *  This method checks if we have any displays. If we don't we
     *  log a warning and display the technical difficulties screen.
     */
    public function handleNonExistentDisplays()
    {
        //  Check if the displays exist
        if ($this->checkIfDisplaysExist() != true) {
            //  Set a warning log that we could not find any displays
            $this->logWarning('No displays found');

            //  Display the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();
        }

        //  Return null if we have displays
        return null;
    }

    /*  checkIfDisplaysExist()
     *  This method checks if the current screen has any displays.
     *  It will return true if we have displays and false if we don't
     *  have displays.
     */
    public function checkIfDisplaysExist()
    {
        //  Check if the current screen displays is an array that its not empty
        if (is_array($this->screen['displays']) && !empty($this->screen['displays'])) {
            //  Return true to indicate that the displays exist
            return true;
        }

        //  Return false to indicate that the displays do not exist
        return false;
    }

    /*  getFirstDisplay()
     *  This method gets the first display of the current screen. First we look
     *  for a display indicated by the user. If we can't locate that display we
     *  then default to the first available display we can find.
     */
    public function getFirstDisplay()
    {
        //  Set an info log that we are searching for the first display
        $this->logInfo('Searching for the first display');

        //  Get all the displays available
        $this->displays = $this->screen['displays'];

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

        //  Set an info log for the first selected display
        $this->logInfo('Selected <span class="text-primary">'.$this->display['name'].'</span> as the first display');
    }

    /*  handleCurrentDisplay()
     *  This method first checks if the display we want to handle exists. This could be the
     *  first display screen or any linked display. In either case if the display does not
     *  exist we log a warning and return the technical difficulties screen. We then check
     *  if the user has already responded to the current display. If (No) then we build
     *  and return the current display. If (Yes) then we need to validate, format and
     *  store the users response if specified.
     */
    public function handleCurrentDisplay()
    {
        //  Check if the current display exists
        $doesNotExistResponse = $this->handleNonExistentDisplay();

        //  If the current display does not exist return the response otherwise continue
        if ($this->shouldDisplayScreen($doesNotExistResponse)) {
            return $doesNotExistResponse;
        }

        //  Handle before display events
        $handleEventsResponse = $this->handleBeforeResponseEvents();

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($handleEventsResponse)) {
            return $handleEventsResponse;
        }

        //  Build the current screen display
        $builtDisplay = $this->buildCurrentDisplay();

        //  Check if the user has already responded to the current display screen
        if ($this->completedLevel($this->level)) {
            //  Get the user response (Input provided by the user) for the current display screen
            $this->getCurrentScreenUserResponse();

            //  Store the user response (Input provided by the user) as a named dynamic variable
            $storeInputResponse = $this->storeCurrentDisplayUserResponseAsDynamicVariable();

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($storeInputResponse)) {
                return $storeInputResponse;
            }

            //  Handle after display events
            $handleEventsResponse = $this->handleAfterResponseEvents();

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($handleEventsResponse)) {
                return $handleEventsResponse;
            }

            //  Handle forward navigation
            $handleForwardNavigationResponse = $this->handleForwardNavigation();

            //  If we have any returned data return the response otherwise continue
            if (!empty($handleForwardNavigationResponse)) {
                $this->resetIncorrectOptionSelected();
                $this->resetPagination();

                return $handleForwardNavigationResponse;
            }

            //  Handle backward navigation
            $handleBackwardNavigationResponse = $this->handleBackwardNavigation();

            //  If we have any returned data return the response otherwise continue
            if (!empty($handleBackwardNavigationResponse)) {
                $this->resetIncorrectOptionSelected();
                $this->resetPagination();

                return $handleBackwardNavigationResponse;
            }

            //  Handle linking display
            $handleLinkingDisplayResponse = $this->handleLinkingDisplay();

            //  If we have any returned data return the response otherwise continue
            if (!empty($handleLinkingDisplayResponse)) {
                return $handleLinkingDisplayResponse;
            }

            if (!empty($this->incorrect_option_selected)) {
                /* Get the "incorrect option selected message" and return display (with go back option)
                 *  to notify the user of the issue
                 */
                return $this->showCustomGoBackScreen($this->incorrect_option_selected);
            }
        }

        return $builtDisplay;
    }

    public function handleNonExistentDisplay()
    {
        /* Note that the checkIfDisplaysExist() helps us make sure that the first display is always available.
         *  If its not available we use the handleNonExistentDisplays() to take care of that. This means that
         *  we never have to worry about the first display, however any other displays that we link to must be
         *  verified for existence.
         */

        //  If the linked display is not available
        if (empty($this->display)) {
            //  Set a warning log that the linked display could not be found
            $this->logWarning('The linked display could not be found');

            //  Return the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();
        }

        return null;
    }

    /*  buildCurrentDisplay()
     *  Build the current display
     *
     */
    public function buildCurrentDisplay()
    {
        //  Set an info log that we are building the display
        $this->logInfo('Start building display <span class="text-primary">'.$this->display['name'].'</span>');

        //  Build the display instruction
        $instructionsBuildResponse = $this->buildDisplayInstructions();

        //  If the instructions failed to build return the failed response otherwise continue
        if ($this->shouldDisplayScreen($instructionsBuildResponse)) {
            return $instructionsBuildResponse;
        }

        //  Get the built display instructions (E,g Welcome to Company XYZ)
        $this->display_instructions = $instructionsBuildResponse;

        //  Build the display actions (E.g Select options)
        $actionBuildResponse = $this->buildDisplayActions();

        //  If the display actions failed to build return the failed response otherwise continue
        if ($this->shouldDisplayScreen($actionBuildResponse)) {
            return $actionBuildResponse;
        }

        //  Build the display actions (E.g Select options)
        $this->display_actions = $actionBuildResponse;

        //  Get the display instruction and action
        $this->display_content = $this->display_instructions.$this->display_actions;

        //  Handle the display pagination
        $outputResponse = $this->handlePagination();

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) {
            return $outputResponse;
        }

        //  If the processed instructions and action are not empty
        if (!empty($this->display_content)) {
            //  Set an info log of the final result
            $this->logInfo('Final result: <br /><span class="text-success">'.$this->display_content.'</span>');
        }

        //  Return the display instruction and action
        return $this->showCustomScreen($this->display_content);
    }

    public function buildDisplayInstructions()
    {
        //  Check if the current display uses "Code Editor Mode"
        $uses_code_editor_mode = $this->display['content']['description']['code_editor_mode'] ?? false;

        //  If the current display instructions uses the PHP Code Editor
        if ($uses_code_editor_mode == true) {
            //  Set an info log that the current display uses the PHP Code Editor to build display instructions
            $this->logInfo('Display <span class="text-primary">'.$this->display['name'].'</span> uses the PHP Code Editor to build instructions');

            //  Get the display instructions code
            $instruction_text = $this->display['content']['description']['code_editor_text'];

        //  If the current content instructions/description does not use the PHP Code Editor
        } else {
            //  Set an info log that the current display uses does not use the PHP Code Editor to build screen instructions
            $this->logInfo('Display <span class="text-primary">'.$this->display['name'].'</span> does not use the PHP Code Editor to build instructions');

            //  Get the display description text
            $instruction_text = $this->display['content']['description']['text'];
        }

        //  Process dynamic content embedded within the display instructions
        return $this->handleEmbeddedDynamicContentConversion($instruction_text, $uses_code_editor_mode);
    }

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

    /*  getDisplayActionType()
     *  This method gets the type of action requested by the current screen
     *
     */
    public function getDisplayActionType()
    {
        //  Available type: "no_action", "input_value" and "select_option"
        return $this->display['content']['action']['selected_type'] ?? '';
    }

    /*  getDisplaySelectOptionType()
     *  This method gets the type of "Select Option" requested by the current display
     *
     */
    public function getDisplaySelectOptionType()
    {
        //  Available type: "static_options", "dynamic_options" and "code_editor_options"
        return $this->display['content']['action']['select_option']['selected_type'] ?? '';
    }

    /*  getDisplayInputType()
     *  This method gets the type of "Input" requested by the current display
     *
     */
    public function getDisplayInputType()
    {
        //  Available type: "single_value_input" and "multi_value_input"
        return $this->display['content']['action']['input_value']['selected_type'] ?? '';
    }

    /*  getStaticSelectOptions()
     *  This method builds the static select options for display on the display
     */
    public function getStaticSelectOptions($returnType = 'array')
    {
        /** Get the available static options
         *
         *  Example Structure:.
         *
         *  [
         *      [
         *          "name": "1. My Messages ({{ messages.total }})",
         *          "value" => [
         *               "text" => "",
         *               "code_editor_text" => "",
         *               "code_editor_mode" => false
         *           ],
         *           "input" => "1",
         *           "separator" => [
         *               "top" => "---",
         *               "bottom" => "---"
         *           ],
         *           "link" => [
         *               "type" => "screen",        //  screen, display
         *               "name" => "messages"
         *           ]
         *      ],
         *      ...
         *  ]
         *
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
                $curr_option_name = $options[$x]['name'];
                $curr_option_value = $options[$x]['value'];

                //  Generate the option number
                $curr_option_number = $x + 1;

                /*************************
                 * BUILD OPTION NAME     *
                 ************************/

                //  Process dynamic content embedded within the option display name
                $buildResponse = $this->handleEmbeddedDynamicContentConversion(
                    //  Text containing embedded dynamic content that must be convert
                    $curr_option_name,
                    //  Is this text information generated using the PHP Code Editor
                    false
                );

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($buildResponse)) {
                    return $buildResponse;
                }

                //  Get the built option name
                $option_name = $buildResponse;

                //  Set an info log of the option display name
                $this->logInfo('Option name: <span class="text-success">'.$option_name.'</span>');

                /*************************
                 * BUILD OPTION VALUE     *
                 ************************/

                $option_value = null;

                if (!empty($curr_option_value['text']) || !empty($curr_option_value['code_editor_text'])) {
                    //  Check if the current option value uses "Code Editor Mode"
                    $uses_code_editor_mode = $curr_option_value['code_editor_mode'] ?? false;

                    //  If we are not using Code Editor Mode and the provided option value is a valid mustache tag
                    if ($uses_code_editor_mode == false && $this->isValidMustacheTag($curr_option_value, false)) {
                        $mustache_tag = $curr_option_value;

                        // Convert the mustache tag into dynamic data
                        $outputResponse = $this->convertMustacheTagIntoDynamicData($mustache_tag);

                        //  If we have a screen to show return the response otherwise continue
                        if ($this->shouldDisplayScreen($outputResponse)) {
                            return $outputResponse;
                        }

                        //  Get the mustache tag dynamic data and use it as the option value
                        $option_value = $outputResponse;

                    //  If the provided value is not a valid mustache tag
                    } else {
                        //  If the current option value uses the PHP Code Editor
                        if ($uses_code_editor_mode == true) {
                            //  Set an info log that the current option uses the PHP Code Editor to build its value
                            $this->logInfo('<span class="text-success">'.$option_name.'</span> uses the PHP Code Editor to build its value');

                            //  Get the option code
                            $curr_option_value_text = $curr_option_value['code_editor_text'];

                        //  If the current content option value does not use the PHP Code Editor
                        } else {
                            //  Set an info log that the option value does not use the PHP Code Editor to build its value
                            $this->logInfo('<span class="text-success">'.$option_name.'</span> does not use the PHP Code Editor to build its value');

                            //  Get the display description text
                            $curr_option_value_text = $curr_option_value['text'];
                        }

                        //  Process dynamic content embedded within the template value
                        $buildResponse = $this->handleEmbeddedDynamicContentConversion(
                            //  Text containing embedded dynamic content that must be convert
                            $curr_option_value_text,
                            //  Is this text information generated using the PHP Code Editor
                            $uses_code_editor_mode
                        );

                        //  If we have a screen to show return the response otherwise continue
                        if ($this->shouldDisplayScreen($buildResponse)) {
                            return $buildResponse;
                        }

                        //  Get the built option value
                        $option_value = $buildResponse;
                    }
                }

                //  Set an info log of the option value
                //  Use json_encode($option_value) to show $option_value data instead of gettype($option_value)

                $dataType = ucwords(gettype($option_value));
                $this->logInfo('Option value: <span class="text-success">['.$dataType.']</span>');

                //  If the return type is an array format
                if ($returnType == 'array') {
                    //  Build the option as an array
                    $option = [
                        //  Get the option name
                        'name' => $option_name,
                        //  Get the option input
                        'input' => $curr_option_number,
                        //  If the option value was not provided
                        'value' => (is_null($option_value))
                                //  Use the entire option data as the value
                                ? $options[$x]
                                //  Otherwise use the converted version of the value provided
                                : $option_value,
                        //  Get the option link
                        'link' => $options[$x]['link'],
                    ];

                    //  Add the option to the rest of our options
                    array_push($collection, $option);

                //  If the return type is a string format
                } elseif ($returnType == 'string') {
                    //  If we have a top separator
                    if (!empty($curr_option['separator']['top'])) {
                        $text .= $curr_option['separator']['top']."\n";
                    }

                    //  Build the option as a string
                    $text .= $option_name."\n";

                    //  If we have a bottom separator
                    if (!empty($curr_option['separator']['bottom'])) {
                        $text .= $curr_option['separator']['bottom']."\n";
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

            //  Get the custom "No options available" otherwise use default
            $text .= ($no_results_message ?? 'No options available');

            //  Return the custom or default "No options available"
            return $text;
        }
    }

    /*  getDynamicSelectOptions()
     *  This method builds the dynamic options for display on the screen
     *
     *  @param returnType = array, string
     */
    public function getDynamicSelectOptions($returnType = 'array')
    {
        /** Get the dynamic select options data
         *
         *  Example Structure:.
         *
         *  [
         *      "group_reference" => "{{ options }}",
         *      "template_reference_name" => "item",
         *      "template_display_name" => "{{ item.name }} - {{ item.price }}",
         *      "template_value" => [
         *          "text" => "",
         *          "code_editor_text" => "",
         *          "code_editor_mode" => false
         *      ],
         *      "reference_name" => "selected_item",
         *      "no_results_message" => "No options found",
         *      "incorrect_option_selected_message" => "You selected an incorrect option. Please try again",
         *      "link" => [
         *          "type" => "screen",
         *          "name" => ""
         *      ]
         *  ]
         */
        $data_structure = $this->display['content']['action']['select_option']['dynamic_options'] ?? null;

        $mustache_tag = $data_structure['group_reference'] ?? null;
        $template_reference_name = $data_structure['template_reference_name'] ?? null;
        $template_display_name = $data_structure['template_display_name'] ?? null;
        $template_value = $data_structure['template_value'] ?? null;

        //  Get the custom "no results message"
        $no_results_message = $data_structure['no_results_message'] ?? null;

        //  Get the next display or screen link
        $link = $data_structure['link'] ?? null;

        //  Check if the dynamic options data exists
        if (empty($data_structure)) {
            //  Set an warning log that the dynamic options data does not exist
            $this->logWarning('The data required to build the dynamic options does not exist');

            //  Display the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();
        }

        //  Check if the dynamic options data is an array
        if (!is_array($data_structure)) {
            //  Set an warning log that the dynamic options data does not exist
            $this->logWarning('The data required to build the dynamic options must be of type [Array]');

            //  Display the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();
        }

        // If mustache tags are not provided
        if (empty($mustache_tag)) {
            //  Set an warning log that the group reference value does not exist
            $this->logWarning('The group reference mustache tag was not provided on the Dynamic Select Option and therefore we cannot create the dynamic select options');

            //  Display the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();
        }

        // If mustache tags are not valid
        if (!$this->isValidMustacheTag($mustache_tag)) {
            //  Set an warning log that the group reference value does not exist
            $this->logWarning('The given group reference mustache tag provided on the Dynamic Select Option is not a valid mustache syntax and therefore we cannot create the dynamic select options');

            //  Display the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();
        }

        // If the template reference name is not provided
        if (empty($template_reference_name)) {
            //  Set an warning log that the group reference value does not exist
            $this->logWarning('The template reference name was not provided on the Dynamic Select Option and therefore we cannot create the dynamic select options');

            //  Display the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();
        }

        // Convert the mustache tag into dynamic data
        $outputResponse = $this->convertMustacheTagIntoDynamicData($mustache_tag);

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) {
            return $outputResponse;
        }

        $options = $outputResponse;

        /** Note that empty arrays ( i.e = [] ) are converted to null values due to the convertToJsonObject() method.
         *  This method is executed while running the convertMustacheTagIntoDynamicData() to get the options dynamic
         *  data. This means it is very possible that the value of $options may be an empty array eventhough running
         *  gettype() may return "null" instead of type of "array". This means that we must first check if the
         *  value is null before we can check if this is of type of "array". We will then later treat this null
         *  value as an empty array and display an "no results message".
         */
        if (!is_null($options)) {
            //  Check if the variable is of type [Array] - Use PHP is_array() to check.
            if (!is_array($options)) {
                $dataType = ucwords(gettype($options));
                $providedMustacheTag = $data_structure['group_reference'];

                //  Set an warning log that the group reference value must be of type array.
                $this->logWarning('The given group reference mustache tag <span class="text-success">'.$providedMustacheTag.'</span> must be of type <span class="text-success">[Array]</span> however we received a value of type <span class="text-success">['.$dataType.']</span> therefore we cannot create the dynamic select options');

                //  Display the technical difficulties error screen to notify the user of the issue
                return $this->showTechnicalDifficultiesErrorScreen();
            }
        }

        /* NOTE:
         *
         *  We only continue if the given options value is of type [Null] or [Array]. We allow type = [Null]
         *  since the convertToJsonObject() converts empty arrays ( i.e = [] ) into [Null] values. Simply
         *  put, if options is of type [Array] then it contains options, however if its of type [Null]
         *  then it contains no options. therefore we allow
         *
         *  $options = [ ... ] or
         *  $options = Null
         *
         */

        //  Use the try/catch handles incase we run into any possible errors
        try {
            //  Set an info log that we are starting to list the dynamic options
            $this->logInfo('Start listing dynamic options');

            /** Check if we have options to display
             *  The options must not be empty or null (i.e $options != [] and $options != null).
             */
            $optionsExist = (!empty($options) && !is_null($options)) ? true : false;

            //  If we have options to display
            if ($optionsExist == true) {
                $text = "\n";
                $collection = [];

                //  Foreach option
                for ($x = 0; $x < count($options); ++$x) {
                    //  Generate the option number
                    $number = $x + 1;

                    //  Add the current item using our custom template reference name as additional dynamic data to our dynamic data storage
                    $this->storeDynamicData($template_reference_name, $options[$x]);

                    /*************************
                     * BUILD OPTION NAME     *
                     ************************/

                    //  Process dynamic content embedded within the template display name
                    $buildResponse = $this->handleEmbeddedDynamicContentConversion(
                        //  Text containing embedded dynamic content that must be convert
                        $template_display_name,
                        //  Is this text information generated using the PHP Code Editor
                        false
                    );

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($buildResponse)) {
                        return $buildResponse;
                    }

                    //  Get the built option name
                    $option_name = $buildResponse;

                    //  Set an info log of the option display name
                    $this->logInfo('Option name: <span class="text-success">'.$option_name.'</span>');

                    /*************************
                     * BUILD OPTION VALUE     *
                     ************************/

                    $option_value = null;

                    if (!empty($template_value['text']) || !empty($template_value['code_editor_text'])) {
                        //  Check if the current option value uses "Code Editor Mode"
                        $uses_code_editor_mode = $template_value['code_editor_mode'] ?? false;

                        //  If we are not using Code Editor Mode and the provided option value is a valid mustache tag
                        if ($uses_code_editor_mode == false && $this->isValidMustacheTag($template_value['text'], false)) {
                            $mustache_tag = $template_value['text'];

                            // Convert the mustache tag into dynamic data
                            $outputResponse = $this->convertMustacheTagIntoDynamicData($mustache_tag);

                            //  If we have a screen to show return the response otherwise continue
                            if ($this->shouldDisplayScreen($outputResponse)) {
                                return $outputResponse;
                            }

                            //  Get the mustache tag dynamic data and use it as the option value
                            $option_value = $outputResponse;

                        //  If the provided value is not a valid mustache tag
                        } else {
                            //  If the current option value uses the PHP Code Editor
                            if ($uses_code_editor_mode == true) {
                                //  Set an info log that the current option uses the PHP Code Editor to build its value
                                $this->logInfo('<span class="text-success">'.$option_name.'</span> uses the PHP Code Editor to build its value');

                                //  Get the option code
                                $template_value_text = $template_value['code_editor_text'];

                            //  If the current content option value does not use the PHP Code Editor
                            } else {
                                //  Set an info log that the option value does not use the PHP Code Editor to build its value
                                $this->logInfo('<span class="text-success">'.$option_name.'</span> does not use the PHP Code Editor to build its value');

                                //  Get the display description text
                                $template_value_text = $template_value['text'];
                            }

                            //  Process dynamic content embedded within the template value
                            $buildResponse = $this->handleEmbeddedDynamicContentConversion(
                                //  Text containing embedded dynamic content that must be convert
                                $template_value_text,
                                //  Is this text information generated using the PHP Code Editor
                                $uses_code_editor_mode
                            );

                            //  If we have a screen to show return the response otherwise continue
                            if ($this->shouldDisplayScreen($buildResponse)) {
                                return $buildResponse;
                            }

                            //  Get the built option value
                            $option_value = $buildResponse;
                        }
                    }

                    //  Set an info log of the option value
                    //  Use json_encode($option_value) to show $option_value data instead of gettype($option_value)
                    $dataType = ucwords(gettype($option_value));
                    $this->logInfo('Option value: <span class="text-success">['.$dataType.']</span>');

                    //  If the return type is an array format
                    if ($returnType == 'array') {
                        //  Build the option as an array
                        $option = [
                            //  Get the option name
                            'name' => $option_name,
                            //  Get the option input
                            'input' => $number,
                            //  If the option value was not provided
                            'value' => (is_null($option_value))
                                    //  Use the entire option data as the value
                                    ? $options[$x]
                                    //  Otherwise use the converted version of the value provided
                                    : $option_value,
                            //  Get the option link
                            'link' => $link,
                        ];

                        //  Add the option to the rest of our options
                        array_push($collection, $option);

                    //  If the return type is a string format
                    } elseif ($returnType == 'string') {
                        //  Build the option as a string
                        $text .= $number.'. '.$option_name."\n";
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

                //  Get the custom "No options available" otherwise use default
                $text .= ($no_results_message ?? 'No options available');

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

    /*  getCodeSelectOptions()
     *  This method builds the code options for display on the screen
     */
    public function getCodeSelectOptions($returnType = 'array')
    {
        //  Get the PHP Code
        $code = $this->display['content']['action']['select_option']['code_editor_options']['code_editor_text'] ?? 'return null;';

        //  Get the custom "no results message"
        $no_results_message = $this->display['content']['action']['select_option']['code_editor_options']['no_results_message'] ?? null;

        //  Use the try/catch handles incase we run into any possible errors
        try {
            //  Set an info log that we are processing the PHP Code from the PHP Code Editor
            $this->logInfo('Process PHP Code from the Code Editor');

            //  Remove the PHP tags from the PHP Code
            $code = $this->removePHPTags($code);

            //  Process the PHP Code
            $outputResponse = $this->processPHPCode("$code");

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) {
                return $outputResponse;
            }

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
                            //  Set an warning log that the option name  was not provided
                            $this->logWarning('The <span class="text-success">Option Name</span> is not provided');

                        //  If the option name is not a type of [String] or [Integer]
                        } elseif (!is_string($option['name'])) {
                            $dataType = ucwords(gettype($option['name']));

                            //  Set an warning log that the option name must be of type [String].
                            $this->logWarning('The given <span class="text-success">Option Name</span> must return data of type <span class="text-success">[String]</span> or <span class="text-success">[Integer]</span> however we received a value of type <span class="text-success">['.$dataType.']</span>');

                        //  If the option input was not provided
                        } elseif (!isset($option['input']) || empty($option['input'])) {
                            //  Set an warning log that the option name  was not provided
                            $this->logWarning('The <span class="text-success">Option Input</span> is not provided');

                        //  If the option input is not a type of [String] or [Integer]
                        } elseif ( !( is_string($option['input']) || is_integer($option['input']) )) {
                            $dataType = ucwords(gettype($option['input']));

                            //  Set an warning log that the option input must be of type [String].
                            $this->logWarning('The given <span class="text-success">Option Input</span> must return data of type <span class="text-success">[String]</span> or <span class="text-success">[Integer]</span> however we received a value of type <span class="text-success">['.$dataType.']</span>');

                        //  If the option link was set but is not of type [Array]
                        } elseif (isset($option['link']) && !is_array($option['link'])) {
                            $dataType = ucwords(gettype($option['link']));

                            //  Set an warning log that the option input must be of type [String].
                            $this->logWarning('The given <span class="text-success">Option Link</span> must return data of type <span class="text-success">[Array]</span> however we received a value of type <span class="text-success">['.$dataType.']</span>');
                        } elseif (isset($option['link']['name']) && !is_string($option['link']['name'])) {
                            $dataType = ucwords(gettype($option['link']['name']));

                            //  Set an warning log that the option link name must be of type [String].
                            $this->logWarning('The given <span class="text-success">Option->Link->Name</span> must return data of type <span class="text-success">[String]</span> however we received a value of type <span class="text-success">['.$dataType.']</span>');
                        } elseif (isset($option['link']['type']) && !is_string($option['link']['type'])) {
                            $dataType = ucwords(gettype($option['link']));

                            //  Set an warning log that the option link name must be of type [String].
                            $this->logWarning('The given <span class="text-success">Option->Link->Type</span> must return data of type <span class="text-success">[String]</span> however we received a value of type <span class="text-success">['.$dataType.']</span>');
                        }

                        //  If the return type is an array format
                        if ($returnType == 'array') {
                            //  Build the option as an array
                            $option = [
                                //  Get the option name
                                'name' => $option['name'] ?? null,
                                //  Get the option input
                                'input' => $option['input'] ?? null,
                                //  Get the option value
                                'value' => $option['value'] ?? null,
                                //  Get the option link
                                'link' => $option['link'],
                            ];

                            //  Add the option to the rest of our options
                            array_push($collection, $option);

                        //  If the return type is a string format
                        } elseif ($returnType == 'string') {
                            //  If we have a top separator
                            if (!empty($option['separator']['top'])) {
                                $text .= $option['separator']['top']."\n";
                            }

                            //  Build the option as a string
                            $text .= $option['name']."\n";

                            //  If we have a bottom separator
                            if (!empty($option['separator']['bottom'])) {
                                $text .= $option['separator']['bottom']."\n";
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

                    //  Get the custom "No options available" otherwise use default
                    $text .= ($no_results_message ?? 'No options available');

                    //  Return the custom or default "No options available"
                    return $text;
                }
            } else {
                $dataType = ucwords(gettype($options));

                //  Set an warning log that the code must return data of type array.
                $this->logWarning('The given <span class="text-success">Code</span> must return data of type <span class="text-success">[Array]</span> however we received a value of type <span class="text-success">['.$dataType.']</span> therefore we cannot create the select options');

                //  Display the technical difficulties error screen to notify the user of the issue
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

    /** storeCurrentDisplayUserResponseAsDynamicVariable()
     *  This method gets the current screen action details to determine the type of action that the
     *  screen requested. We use the type of action e.g "Input a value" or "Select an option" to
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

            /* If the input is a single value input e.g
             *  Q: Enter your first name
             *  Ans: John
            */
            if ($screenInputType == 'single_value_input') {
                return $this->storeSingleValueInputAsDynamicData();

            /* If the input is a multi-value input e.g
             *  Q: Enter your first name, last name and age separated by spaces
             *  Ans: John Doe 25
            */
            } elseif ($screenInputType == 'multi_value_input') {
                return $this->storeMultiValueInputAsDynamicData();
            }
        }
    }

    /*  storeSelectedStaticOptionAsDynamicData()
     *  This method gets the value from the selected static option and stores it within the
     *  specified reference variable if provided. It also determines if the next display or
     *  screen link has been provided, if (yes) we fetch the specified display or screen
     *  and save it for linking in future methods.
     */
    public function storeSelectedStaticOptionAsDynamicData()
    {
        $outputResponse = $this->getStaticSelectOptions('array');

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) {
            return $outputResponse;
        }

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

    /*  storeSelectedDynamicOptionAsDynamicData()
     *  This method gets the value from the selected dynamic option and stores it within the
     *  specified reference variable if provided. It also determines if the next screen
     *  has been provided, if (yes) we fetch the specified screen and save it as a
     *  screen that we must link to in future.
     *
     */
    public function storeSelectedDynamicOptionAsDynamicData()
    {
        $outputResponse = $this->getDynamicSelectOptions('array');

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) {
            return $outputResponse;
        }

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

    /*  storeSelectedCodeOptionAsDynamicData()
     *  This method gets the value from the selected code option and stores it within the
     *  specified reference variable if provided. It also determines if the next screen
     *  has been provided, if (yes) we fetch the specified screen and save it as a
     *  screen that we must link to in future.
     *
     */
    public function storeSelectedCodeOptionAsDynamicData()
    {
        $outputResponse = $this->getCodeSelectOptions('array');

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) {
            return $outputResponse;
        }

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
         *          "link" => [
         *               "type" => "screen",        //  screen, display
         *               "name" => "messages"
         *          ]
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
            //  Process dynamic content embedded within the expected option input
            $outputResponse = $this->handleEmbeddedDynamicContentConversion(
                //  Text containing embedded dynamic content that must be convert
                $option['input'],
                //  Is this text information generated using the PHP Code Editor
                false
            );

            return $this->current_user_response == $outputResponse;
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
                if (!empty($reference_name)) {
                    //  Get the option value only
                    $dynamic_data = $selectedOption['value'];

                    //  Store the select option as dynamic data
                    $this->storeDynamicData($reference_name, $dynamic_data);
                }

                //  If the user did not select an option that exists
            } else {
                //  Display the custom "Incorrect option selected" otherwise use default
                $message = ($incorrect_option_selected_message ?? 'You selected an incorrect option. Please try again')."\n";

                //  Get the "incorrect option selected" message
                $this->incorrect_option_selected = $message;
            }

            //  If we don't have options to display
        } else {
            //  Display the custom "No options available" otherwise use default
            $message = ($no_results_message ?? 'No options available')."\n";

            //  Log the custom "no options message" to notify the user of the issue
            $this->logWarning($message);
        }
    }

    /*  storeSingleValueInputAsDynamicData()
     *  This method gets the single value from the input and stores it within the specified
     *  reference variable if provided. It also determines if the next screen has been provided,
     *  if (yes) we fetch the specified screen and save it as a screen that we must link to in future.
     *
     */
    public function storeSingleValueInputAsDynamicData()
    {
        //  Get the users current response
        $user_response = $this->current_user_response;

        //  Get the reference name (The name used to store the input value for ease of referencing)
        $reference_name = $this->display['content']['action']['input_value']['single_value_input']['reference_name'] ?? null;

        //  Get the single input link (The display or screen we must link to after the user inputs a value)
        $link = $this->display['content']['action']['input_value']['single_value_input']['link'] ?? null;

        //  Setup the link for the next display or screen
        $this->setupLink($link);

        //  If we have the reference name provided
        if (!empty($reference_name)) {
            //  Store the input value as dynamic data
            $this->storeDynamicData($reference_name, $user_response);
        }
    }

    /*  storeMultiValueInputAsDynamicData()
     *  This method gets the multiple values from the input and stores them within the specified
     *  reference variables if provided. It also determines if the next screen has been provided,
     *  if (yes) we fetch the specified screen and save it as a screen that we must link to in future.
     *
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

    public function setupLink($link)
    {
        //  If we have a link
        if (isset($link) && !empty($link)) {
            //  If the link name and type has been provided
            if (isset($link['name']) && !empty($link['name']) && isset($link['type']) && !empty($link['type'])) {
                $name = $link['name'];

                //  If we should link to a display
                if ($link['type'] == 'display') {
                    //  Get the screen matching the given name and set it as the linked screen
                    $this->linked_display = $this->getDisplayByName($name);

                //  If we should link to a screen
                } elseif ($link['type'] == 'screen') {
                    //  Get the screen matching the given name and set it as the linked screen
                    $this->linked_screen = $this->getScreenByName($name);
                }
            }
        }
    }

    /*  getDisplayByName()
     *  This method returns a display of the current screen if it exists by searching based on
     *  the display name provided
     *
     */
    public function getDisplayByName($name = null)
    {
        //  If the display name has been provided
        if (!empty($name)) {
            //  Get the first display that matches the given name
            return collect($this->screen['displays'])->where('name', $name)->first() ?? null;
        }
    }

    /*  getScreenByName()
     *  This method returns a screen if it exists by searching based on the screen name provided
     *
     */
    public function getScreenByName($name = null)
    {
        //  If the screen name has been provided
        if ($name) {
            //  Get the first screen that matches the given screen name
            return collect($this->screens)->where('name', $name)->first() ?? null;
        }
    }

    /*  getCurrentScreenUserResponse()
     *  This method gets the users response for the current screen if it exists otherwise
     *  returns an empty string if it does not exist. We also log an info message to
     *  indicate the screen name associated with the provided response.
     */
    public function getCurrentScreenUserResponse()
    {
        $this->current_user_response = $this->getResponseFromLevel($this->level) ?? '';   //  John Doe

        //  Update the ussd data
        $this->ussd['user_response'] = $this->current_user_response;

        //  Store the ussd data using the given item reference name
        $this->storeDynamicData('ussd', $this->ussd, false);

        //  Set an info log that the user has responded to the current screen and show the input value
        $this->logInfo('User has responded to <span class="text-primary">'.$this->screen['name'].'</span> with <span class="text-success">'.$this->current_user_response.'</span>');

        //  Return the current screen user response
        return $this->current_user_response;
    }

    public function handlePagination()
    {
        $pagination = $this->display['content']['pagination'];

        //  If the pagination is active
        if ($pagination['active'] == true) {
            //  Set an info log that we are handling pagination
            $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span>, handling pagination');

            //  Get the pagination content target
            $content_target = $pagination['content_target']['selected_type'];

            //  Get the pagination separation type e.g separate by "words" or "characters"
            $separation_type = $pagination['slice']['separation_type'];

            //  Get the trail for showing we have more content e.g "..."
            $paginate_by_line_breaks = $pagination['paginate_by_line_breaks'];

            //  Get the pagination start slice
            $start_slice = $pagination['slice']['start'];

            //  Get the pagination end slice
            $end_slice = $pagination['slice']['end'];

            //  Get the pagination scroll down input
            $scroll_down_input = $pagination['scroll_down_input'];

            //  Get the pagination scroll up input
            $scroll_up_input = $pagination['scroll_up_input'];

            //  Get the trail for showing we have more content e.g "..."
            $trailing_characters = $pagination['trailing_end'];

            //  Get the break line before trail
            $break_line_before_trail = $pagination['break_line_before_trail'];

            //  Get the break line after trail
            $break_line_after_trail = $pagination['break_line_after_trail'];

            //  Get the pagination show more visibility
            $show_scroll_down_text = $pagination['scroll_down']['visible'];

            //  Get the pagination show more text
            $scroll_down_text = $pagination['scroll_down']['text'];

            //  Get the pagination show more visibility
            $show_scroll_up_text = $pagination['scroll_up']['visible'];

            //  Get the pagination show more text
            $scroll_up_text = $pagination['scroll_up']['text'];

            //  Process dynamic content embedded within the start slice
            $outputResponse = $this->handleEmbeddedDynamicContentConversion($start_slice, false);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) {
                return $outputResponse;
            }

            //  Get the processed value (Convert from [String] to [Number]) - Default to 0 if anything goes wrong
            $start_slice = (int) $outputResponse ?? 0;

            //  Make sure the start slice is no less than 0
            $start_slice = ($start_slice < 0) ? 0 : $start_slice;

            //  Make sure the start slice is no greater than 150
            $start_slice = ($start_slice > 150) ? 150 : $start_slice;

            //  Process dynamic content embedded within the end slice
            $outputResponse = $this->handleEmbeddedDynamicContentConversion($end_slice, false);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) {
                return $outputResponse;
            }

            //  Get the processed value (Convert from [String] to [Number]) - Default to 160 if anything goes wrong
            $end_slice = (int) $outputResponse ?? 160;

            //  Make sure the start slice is no less than 0
            $end_slice = ($end_slice < 0) ? 0 : $end_slice;

            //  Make sure the start slice is no greater than 160
            $end_slice = ($end_slice > 160) ? 160 : $end_slice;

            //  Make sure the end slice is greater than the start slice
            $end_slice = ($end_slice < $start_slice) ? 160 : $end_slice;

            //  Process dynamic content embedded within the scroll down input
            $outputResponse = $this->handleEmbeddedDynamicContentConversion($scroll_down_input, false);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) {
                return $outputResponse;
            }

            $scroll_down_input = trim($outputResponse);

            //  Process dynamic content embedded within the scroll up input
            $outputResponse = $this->handleEmbeddedDynamicContentConversion($scroll_up_input, false);

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) {
                return $outputResponse;
            }

            $scroll_up_input = trim($outputResponse);

            if ($show_scroll_down_text) {
                //  Process dynamic content embedded within the scroll down text
                $outputResponse = $this->handleEmbeddedDynamicContentConversion($scroll_down_text, false);

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) {
                    return $outputResponse;
                }

                $scroll_down_text = $outputResponse;
            }

            if ($show_scroll_up_text) {
                //  Process dynamic content embedded within the scroll up text
                $outputResponse = $this->handleEmbeddedDynamicContentConversion($scroll_up_text, false);

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) {
                    return $outputResponse;
                }

                $scroll_up_text = $outputResponse;
            }

            if ($content_target == 'instruction') {
                $content = $this->display_instructions ?? '';
            } elseif ($content_target == 'action') {
                $content = $this->display_actions ?? '';
            } elseif ($content_target == 'both') {
                $content = $this->display_content ?? '';
            }

            //  Get the content that must always be at the top
            $fixed_content = substr($content, 0, $start_slice);

            //  Get the rest of the content as the content to paginate
            $pagination_content = substr($content, $start_slice);

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

            //  If the show scroll down text is set to be visible and its not empty
            if ($show_scroll_down_text == true && !empty($scroll_down_text)) {
                //  Combine the trail and the scroll down text e.g "..." and "99.More"
                $trailing_characters .= "\n".$scroll_down_text;
            }

            //  If the show more text is set to be visible and its not empty
            if ($show_scroll_up_text == true && !empty($scroll_up_text)) {
                //  Combine the trail and the scroll up text e.g "..." and "99.More"
                $trailing_characters .= "\n".$scroll_up_text;
            }

            /* Pagination by line breaks works a best as possible to avoid cutting words
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
                $pagination_content_paragraphs = array_filter($pagination_content_paragraphs, function ($pagination_content_paragraph) {
                    return !empty(trim($pagination_content_paragraph));
                });

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
                            $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span> scrolling up');

                            if ($this->pagination_index > 0) {
                                //  Decrement the pagination index so that we target the previous pagination content slice
                                --$this->pagination_index;
                            }
                        } elseif ($userResponse == $scroll_down_input) {
                            //  Set an info log that we are scrolling on the content
                            $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span> scrolling down');

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
        /** To stop any potential forever loops, lets limit the cycles to 100 loops
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
                //  Log a warning that its possible we have a forever loop (since its rare to reach 100 cycles)
                $this->logWarning('Possible forever loop detected while handling pagination.');
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

    public function resetIncorrectOptionSelected()
    {
        $this->incorrect_option_selected = null;
    }

    public function resetPagination()
    {
        $this->pagination_index = 0;
    }

    public function handleForwardNavigation()
    {
        //  If the screen is set to repeat
        if ($this->screen['type']['selected_type'] == 'repeat') {
            //  Set an info log that we are checking if the display can navigate forward
            $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span>, checking if the display can navigate forward');

            $forward_navigation = $this->display['content']['screen_repeat_navigation']['forward_navigation'];

            foreach ($forward_navigation as $navigation) {
                //  Get the navigation step settings
                $step = $navigation['custom']['step'];

                //  Check if the step uses "Code Editor Mode"
                $uses_code_editor_mode = $step['code_editor_mode'];

                //  If the step uses the PHP Code Editor
                if ($uses_code_editor_mode == true) {
                    //  Get the step code otherwise default to a return statement that returns 1
                    $step_text = $step['code_editor_text'] ?? "return '1';";

                //  If the step does not use the PHP Code Editor
                } else {
                    //  Get the step text otherwise default to a string of 1
                    $step_text = $step['text'] ?? '1';
                }

                //  Process dynamic content embedded within the step text
                $outputResponse = $this->handleEmbeddedDynamicContentConversion($step_text, $uses_code_editor_mode);

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) {
                    return $outputResponse;
                }

                //  Get the processed step value (Convert from [String] to [Number]) - Default to 1 if anything goes wrong
                $step_number = (int) $outputResponse ?? 1;

                //  If the processed forward navigation step number is not an integer or a number greater than 1
                if (!is_integer($step_number) || !($step_number >= 1)) {
                    $dataType = ucwords(gettype($step_number));

                    //  Set an warning log that the step number must be of type array.
                    if (!is_integer($step_number)) {
                        $this->logWarning('The given forward navigation step number must be of type <span class="text-success">[Integer]</span>. Value received <span class="text-success">['.$step_number.']</span> is of type <span class="text-success">['.$dataType.']</span>');
                    }

                    if (!($step_number >= 1)) {
                        $this->logWarning('The given forward navigation step number equals [<span class="text-success">'.$step_number.'</span>]. The expected value must equal [<span class="text-success">1</span>] or an integer greater than [<span class="text-success">1</span>].For this reason we will use the default value of [<span class="text-success">1</span>]');
                    }

                    //  Default the forward navigation step number to 1
                    $this->forward_navigation_step_number = 1;
                } else {
                    $this->forward_navigation_step_number = $step_number;
                }

                if ($navigation['selected_type'] == 'custom') {
                    //  Set an info log that we are checking if the display can navigate forward
                    $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span> supports custom forward navigation');

                    //  Get the custom inputs e.g "1, 2, 3"
                    $inputs = $navigation['custom']['inputs'];

                    //  If we have inputs
                    if (!empty($inputs)) {
                        //  Seprate the inputs by comma ","
                        $valid_inputs = explode(',', $inputs);

                        foreach ($valid_inputs as $key => $input) {
                            //  Make sure each input has no left and right spaces
                            $valid_inputs[$key] = trim($input);
                        }

                        //  If the user response matches any valid navigation input
                        if (in_array($this->current_user_response, $valid_inputs)) {
                            //  Set an info log that user response has been allowed for forward navigation
                            $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span> user response allowed for forward navigation');

                            /* Increment the current level so that we target the next repeat display
                             *  (This means we are targeting the same display but different instance)
                             */
                            ++$this->level;

                            /* Return an indication that we want to navigate forward (i.e Go to the next iteration)
                                *
                                *  Refer to: startRepeatScreen()
                                *
                                */
                            return 'navigate-forward';
                        }
                    }
                }
            }
        }
    }

    public function handleBackwardNavigation()
    {
        //  If the screen is set to repeat
        if ($this->screen['type']['selected_type'] == 'repeat') {
            //  Set an info log that we are checking if the display can navigate forward
            $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span>, checking if the display can navigate backward');

            $backward_navigation = $this->display['content']['screen_repeat_navigation']['backward_navigation'];

            foreach ($backward_navigation as $navigation) {
                //  Get the navigation step settings
                $step = $navigation['custom']['step'];

                //  Check if the step uses "Code Editor Mode"
                $uses_code_editor_mode = $step['code_editor_mode'];

                //  If the step uses the PHP Code Editor
                if ($uses_code_editor_mode == true) {
                    //  Get the step code otherwise default to a return statement that returns 1
                    $step_text = $step['code_editor_text'] ?? "return '1';";

                //  If the step does not use the PHP Code Editor
                } else {
                    //  Get the step text otherwise default to a string of 1
                    $step_text = $step['text'] ?? '1';
                }

                //  Process dynamic content embedded within the step text
                $outputResponse = $this->handleEmbeddedDynamicContentConversion($step_text, $uses_code_editor_mode);

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) {
                    return $outputResponse;
                }

                //  Get the processed step value (Convert from [String] to [Number]) - Default to 1 if anything goes wrong
                $step_number = (int) $outputResponse ?? 1;

                //  If the processed backward navigation step number is not an integer or a number greater than 1
                if (!is_integer($step_number) || !($step_number >= 1)) {
                    //  Set an warning log that the step number must be of type array.
                    if (!is_integer($step_number)) {
                        $this->logWarning('The given backward navigation step number must be of type [<span class="text-success">Integer</span>]. Value received [<span class="text-success">'.$step_number.'</span>] is of type [<span class="text-success">'.gettype($output).'</span>]');
                    }

                    if (!($step_number >= 1)) {
                        $this->logWarning('The given backward navigation step number equals [<span class="text-success">'.$step_number.'</span>]. The expected value must equal [<span class="text-success">1</span>] or an integer greater than [<span class="text-success">1</span>].For this reason we will use the default value of [<span class="text-success">1</span>]');
                    }

                    //  Default the backward navigation step number to 1
                    $this->backward_navigation_step_number = 1;
                } else {
                    $this->backward_navigation_step_number = $step_number;
                }

                if ($navigation['selected_type'] == 'custom') {
                    //  Set an info log that we are checking if the display can navigate backward
                    $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span> supports custom backward navigation');

                    //  Get the custom inputs e.g "1, 2, 3"
                    $inputs = $navigation['custom']['inputs'];

                    //  If we have inputs
                    if (!empty($inputs)) {
                        //  Seprate the inputs by comma ","
                        $valid_inputs = explode(',', $inputs);

                        foreach ($valid_inputs as $key => $input) {
                            //  Make sure each input has no left and right spaces
                            $valid_inputs[$key] = trim($input);
                        }

                        //  If the user response matches any valid navigation input
                        if (in_array($this->current_user_response, $valid_inputs)) {
                            //  Set an info log that user response has been allowed for backward navigation
                            $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span> user response allowed for backward navigation');

                            /* Increment the current level so that we target the next repeat display
                             *  (This means we are targeting the same display but different instance)
                             */
                            ++$this->level;

                            /* Return an indication that we want to navigate forward (i.e Go to the next iteration)
                             *
                             *  Refer to: startRepeatScreen()
                             *
                             */
                            return 'navigate-backward';
                        }
                    }
                }
            }
        }
    }

    public function handleLinkingDisplay()
    {
        //  Check if the current display must link to another display or screen
        if ($this->checkIfDisplayMustLink()) {
            //  Increment the current level so that we target the next screen (This means we are targeting the linked screen)
            ++$this->level;

            //  If we have a display we can link to
            if (!empty($this->linked_display)) {
                //  Set the current display as the linked display
                $this->display = $this->linked_display;

                //  Reset the linked display to nothing
                $this->linked_display = null;

                $this->resetIncorrectOptionSelected();

                $this->resetPagination();

                //  Handle the current display (This means we are handling the linked display)
                return $this->handleCurrentDisplay();

            //  If we have a screen we can link to
            } elseif (!empty($this->linked_screen)) {
                //  Set the current screen as the linked screen
                $this->screen = $this->linked_screen;

                //  Reset the linked screen to nothing
                $this->linked_screen = null;

                $this->resetPagination();

                //  Handle the current screen (This means we are handling the linked screen)
                return $this->handleCurrentScreen();
            }
        }
    }

    /******************************************
     *  REPEAT EVENT METHODS                *
     *****************************************/

    public function handleBeforeRepeatEvents()
    {
        //  Check if the screen has before repeat events
        if (count($this->screen['type']['repeat']['events']['before_repeat'])) {
            //  Get the events to handle
            $events = $this->screen['type']['repeat']['events']['before_repeat'];

            //  Set an info log that the current screen has before repeat events
            $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span> has (<span class="text-success">'.count($events).'</span>) before repeat events');

            //  Start handling the given events
            return $this->handleEvents($events);
        } else {
            //  Set an info log that the current screen does not have before repeat events
            $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span> does not have before repeat events.');

            return null;
        }
    }

    public function handleAfterRepeatEvents()
    {
        //  Check if the screen has after repeat events
        if (count($this->screen['type']['repeat']['events']['after_repeat'])) {
            //  Get the events to handle
            $events = $this->screen['type']['repeat']['events']['after_repeat'];

            //  Set an info log that the current screen has after repeat events
            $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span> has (<span class="text-success">'.count($events).'</span>) after repeat events');

            //  Start handling the given events
            return $this->handleEvents($events);
        } else {
            //  Set an info log that the current screen does not have after repeat events
            $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span> does not have after repeat events');

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
            $this->logInfo('Display <span class="text-primary">'.$this->display['name'].'</span> has (<span class="text-success">'.count($events).'</span>) before user response events.');

            //  Start handling the given events
            return $this->handleEvents($events);
        } else {
            //  Set an info log that the current display does not have before user response events
            $this->logInfo('Display <span class="text-primary">'.$this->display['name'].'</span> does not have before user response events.');

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
            $this->logInfo('Display <span class="text-primary">'.$this->display['name'].'</span> has (<span class="text-success">'.count($events).'</span>) after user response events.');

            //  Start handling the given events
            return $this->handleEvents($events);
        } else {
            //  Set an info log that the current display does not have after user response events
            $this->logInfo('Display <span class="text-primary">'.$this->display['name'].'</span> does not have after user response events.');

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

                //  If the given response is a display screen then return the response otherwise continue
                if ($this->shouldDisplayScreen($handleEventResponse)) {
                    //  Set an info log that the current event wants to display information
                    $this->logInfo('Event: <span class="text-success">'.$event['name'].'</span>, wants to display information, we are not running any other events or processes, instead we will return information to display.');

                    //  Return the screen information
                    return $handleEventResponse;
                }
            }
        }
    }

    public function handleEvent($event = null)
    {
        //  If we have an active event to handle
        if ($event['active']) {
            //  Set an info log that we are preparing to handle the given event
            $this->logInfo('<span class="text-primary">'.$this->screen['name'].'</span> preparing to handle the <span class="text-success">'.$event['name'].'</span> event');

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
            }
        } else {
            //  Set an info log that the current event is not activated
            $this->logInfo('Event: <span class="text-success">'.$event['name'].' is not activated, therefore will not be executed.');
        }
    }

    /******************************************
     *  CRUD API EVENT METHODS                *
     *****************************************/
    public function handle_CRUD_API_Event()
    {
        if ($this->event) {
            //  Run the CRUD API Call
            $apiCallResponse = $this->run_CRUD_Api_Call();

            //  If the response returned a screen display return the screen display otherwise continue
            if ($this->shouldDisplayScreen($apiCallResponse)) {
                return $apiCallResponse;
            }

            return $this->handle_CRUD_Api_Response($apiCallResponse);
        }
    }

    public function run_CRUD_Api_Call()
    {
        $url = $this->get_CRUD_Api_URL();

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($url)) {
            return $url;
        }

        $method = $this->get_CRUD_Api_Method();
        $headers = $this->get_CRUD_Api_Headers();
        $form_data = $this->get_CRUD_Api_Form_Data();
        $query_params = $this->get_CRUD_Api_Query_Params();
        $request_options = [];

        //  Check if the CRUD Url and Method has been provided
        if (empty($url) || empty($method)) {
            //  Check if the CRUD Url has been provided
            if (empty($url)) {
                //  Set a warning log that the CRUD API Url was not provided
                $this->logWarning('API Url was not provided');

                //  Display the technical difficulties error screen to notify the user of the issue
                return $this->showTechnicalDifficultiesErrorScreen();
            }

            //  Check if the CRUD Method has been provided
            if (empty($method)) {
                //  Set a warning log that the CRUD API Method was not provided
                $this->logWarning('API Method was not provided');

                //  Display the technical difficulties error screen to notify the user of the issue
                return $this->showTechnicalDifficultiesErrorScreen();
            }
        } else {
            //  Set an info log of the CRUD API Url provided
            $this->logInfo('API Url: <span class="text-success">'.$url.'</span>');

            //  Set an info log of the CRUD API Method provided
            $this->logInfo('API Method: <span class="text-success">'.ucwords($method).'</span>');
        }

        //  Check if the provided url is correct
        if (!$this->isValidUrl($url)) {
            //  Set a warning log that the CRUD API Url provided is incorrect
            $this->logWarning('API Url provided is incorrect (<span class="text-danger">'.$url.'</span>)');

            //  Display the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();
        }

        //  If we have the headers
        if (!empty($headers) && is_array($headers)) {
            //  Add the form data to the form_params attribute of our API options
            $request_options['headers'] = $headers;

            foreach ($headers as $key => $value) {
                //  Set an info log of the CRUD API header attribute
                $this->logInfo('Headers: <span class="text-success">'.$key.'</span> = <span class="text-success">'.$value.'</span>');
            }
        }

        //  If we have the form data
        if (!empty($query_params) && is_array($query_params)) {
            foreach ($query_params as $key => $value) {
                //  Set an info log of the CRUD API query param attribute
                $this->logInfo('Query Params: <span class="text-success">'.$key.'</span> = <span class="text-success">'.json_encode($value).'</span>');
            }
        }

        //  If we have the form data
        if (!empty($form_data) && is_array($form_data)) {
            //  Add the form data to the form_params attribute of our API options
            $request_options['form_params'] = $form_data;

            foreach ($form_data as $key => $value) {
                //  Set an info log of the CRUD API form data attribute
                $this->logInfo('Form Data: <span class="text-success">'.$key.'</span> = <span class="text-success">'.json_encode($value).'</span>');
            }
        }

        //  Create a new Http Guzzle Client
        $httpClient = new \GuzzleHttp\Client();

        try {
            //  Set an info log that we are performing CRUD API call
            $this->logInfo('Run API call to: <span class="text-success">'.$url.'</span>');

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
            $this->logWarning('Api call to <span class="text-danger">'.$url.'</span> failed.');

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
            $this->logWarning('Api call to <span class="text-danger">'.$url.'</span> failed.');

            //  Handle try catch error
            return $this->handleTryCatchError($e);

            //  Just incase we failed to catch RequestException and Throwable
        } catch (Exception $e) {
            //  Set a warning log that the Api call failed
            $this->logWarning('Api call to <span class="text-danger">'.$url.'</span> failed.');

            //  Handle try catch error
            return $this->handleTryCatchError($e);
        }
    }

    public function get_CRUD_Api_URL()
    {
        $url = $this->event['event_data']['url'] ?? null;

        if ($url) {
            //  Process dynamic content embedded within the url
            $buildResponse = $this->handleEmbeddedDynamicContentConversion(
                //  Text containing embedded dynamic content that must be convert
                $url,
                //  Is this text information generated using the PHP Code Editor
                false
            );

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($buildResponse)) {
                return $buildResponse;
            }

            //  Get the built option name
            $url = $buildResponse;
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
            if (!empty($header['key']) && !empty($header['value'])) {
                $data[$header['key']] = $header['value'];
            }
        }

        return $data;
    }

    public function get_CRUD_Api_Form_Data()
    {
        {
            $form_data = $this->event['event_data']['form_data'] ?? [];
    
            $data = [];
    
            foreach ($form_data as $form_item) {
    
                if (!empty($form_item['key']) && !empty($form_item['value'])) {
    
                    //  Convert the dynamic content (if any) embedded within the form data value
                    $outputResponse = $this->convertMustacheTagOrEmbeddedDynamicContentIntoDynamicData(
                        //  Text containing embedded dynamic content that must be convert
                        $form_item['value'],
                        //  Is this text information generated using the PHP Code Editor
                        false
                    );
    
                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) {
                        return $outputResponse;
                    }
    
                    $data[$form_item['key']] = $outputResponse;
    
                }
            }
    
            return $data;
        }
    }

    public function get_CRUD_Api_Query_Params()
    {
        $query_params = $this->event['event_data']['query_params'] ?? [];

        $data = [];

        foreach ($query_params as $query_param) {
            if (!empty($query_param['key']) && !empty($query_param['value'])) {
                $data[$query_param['key']] = $query_param['value'];
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
        $this->logInfo('Handle response <span class="text-success">Automatically</span>');

        //  Get the response status code e.g "200"
        $status_code = $response->getStatusCode();

        //  Get the response status phrase e.g "OK"
        $status_phrase = $response->getReasonPhrase() ?? '';

        //  Get the default success message
        $default_success_message = $this->event['event_data']['response']['general']['default_success_message'] ?? 'Completed successfully';

        //  Get the default error message
        $default_error_message = $this->event['event_data']['response']['general']['default_error_message'] ?? null;

        $on_success_handle_type = $this->event['event_data']['response']['automatic']['on_handle_success'] ?? 'use_default_success_msg';
        $on_error_handle_type = $this->event['event_data']['response']['automatic']['on_handle_error'] ?? 'use_default_error_msg';

        //  Check if this is a good status code e.g "100", "200", "301" e.t.c
        if ($this->checkIfGoodStatusCode($status_code)) {
            //  Set an info log of the response status code received
            $this->logInfo('API response returned a status (<span class="text-success">'.$status_code.'</span>) Status text: <span class="text-success">'.$status_phrase.'</span>');

            //  Since this is a successful response, check if we should display a default success message or do nothing
            if ($on_success_handle_type == 'use_default_success_msg') {
                //  Set an info log that we are displaying the custom success message
                $this->logInfo('Display default success message: <span class="text-success">'.$default_success_message.'</span>');

                //  This is a good response - Display the custom succcess message
                return $this->showCustomScreen($default_success_message, ['continue' => false]);
            } elseif ($on_success_handle_type == 'do_nothing') {
                //  Return nothing
                return null;
            }

            //  If this is a bad status code e.g "400", "401", "500" e.t.c
        } else {
            //  Set an info log of the response status code received
            $this->logWarning('API response returned a status (<span class="text-danger">'.$status_code.'</span>) <br/> Status text: <span class="text-danger">'.$status_phrase.'</span>');

            //  Since this is a failed response, check if we should display a default error message or do nothing
            if ($on_error_handle_type == 'use_default_error_msg') {
                //  Set an info log that we are displaying the custom error message
                $this->logInfo('Display default error message: <span class="text-success">'.$default_error_message.'</span>');

                //  If the default error message was provided
                if (!empty($default_error_message)) {
                    //  This is a bad response - Display the custom error message
                    return $this->showCustomErrorScreen($default_error_message);

                //  If the default error message was not provided
                } else {
                    //  Set an warning log that the default error message was not provided
                    $this->logWarning('The default error message was not provided, using the default technical difficulties message instead');

                    //  Display the technical difficulties error screen to notify the user of the issue
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
            $this->logInfo('Handle response <span class="text-success">Manually</span>');

            //  Get the response status code e.g "200"
            $status_code = $response->getStatusCode();

            //  Get the response status phrase e.g "OK"
            $status_phrase = $response->getReasonPhrase() ?? '';

            //  Get the response body e.g [ "products" => [ ... ] ]
            $response_body = json_decode($response->getBody());

            //  Get the response status handles
            $response_status_handles = $this->event['event_data']['response']['manual']['response_status_handles'] ?? [];

            if (!empty($response_status_handles)) {

                //  Get the request status handle that matches the given status
                $selected_handle = collect(array_filter($response_status_handles, function ($request_status_handle) use ($status_code) {
                    return $request_status_handle['status'] == $status_code;
                }))->first() ?? null;

                //  If a matching response status handle was found
                if ($selected_handle) {
                    //  Get the response attributes
                    $response_attributes = $selected_handle['attributes'];

                    //  Get the response handle type e.g "use_custom_msg" or "do_nothing"
                    $on_handle_type = $selected_handle['on_handle']['selected_type'];

                    //  Check if the current response status handle uses "Code Editor Mode"
                    $uses_code_editor_mode = $selected_handle['on_handle']['use_custom_msg']['code_editor_mode'];

                    //  Set an info log that we are storing the attributes of the custom API response
                    $this->logInfo('Start processing and storing the response attributes');

                    //  Set an info log of the number of response attributes found
                    $this->logInfo('Found ('.count($response_attributes).') response attributes');

                    //  Add the current response body to the dynamic data storage
                    $this->dynamic_data_storage['response'] = $response_body;

                    foreach ($response_attributes as $response_attribute) {
                        //  Get the attribute name
                        $name = trim($response_attribute['name']);

                        //  Get the attribute value
                        $value = trim($response_attribute['value']);

                        //  If the attribute name and value exists
                        if (!empty($name) && !empty($value)) {
                            //  Get the attribute value (Usually in mustache tag format)
                            $mustache_tag = $value;

                            //  Convert "{{ company.name }}" into "$company->name"
                            $dynamic_variable = $this->convertMustacheTagIntoPHPVariable($mustache_tag, true);

                            //  Convert the dynamic property into its dynamic value e.g "$company->name" into "Company XYZ"
                            $outputResponse = $this->processPHPCode("return $dynamic_variable;", false);

                            //  If processing the PHP Code failed, return the failed response otherwise continue
                            if ($this->shouldDisplayScreen($outputResponse)) {
                                return $outputResponse;
                            }

                            //  Get the generated output
                            $output = $outputResponse;

                            $dataType = ucwords(gettype($output));

                            //  If the dynamic value is a string, integer or float
                            if (is_string($output) || is_integer($output) || is_float($output)) {
                                //  Set an info log that we are converting the dynamic property to its associated value
                                $this->logInfo(
                                    //  Use json_encode($output) to show $output data instead of gettype($output)
                                    'Converting attribute: <span class="text-success">'.$mustache_tag.'</span> to <span class="text-success">['.$dataType.']</span> '.
                                    'and assigning the value to <span class="text-success">'.$response_attribute['name'].'</span>'
                                );

                            //  Incase the dynamic value is not a string, integer or float
                            } else {
                                //  Set an info log that we are converting the dynamic property to its associated value
                                $this->logInfo(
                                    //  Use json_encode($output) to show $output data instead of gettype($output)
                                    'Converting attribute: <span class="text-success">'.$mustache_tag.'</span> to <span class="text-success">['.$dataType.']</span> '.
                                    'and assigning the value to <span class="text-success">'.$response_attribute['name'].'</span>'
                                );
                            }

                            //  Store the attribute data as dynamic data
                            $this->storeDynamicData($name, $output);
                        }
                    }

                    if ($on_handle_type == 'use_custom_msg') {
                        //  Check if this is a good status code e.g "100", "200", "301" e.t.c
                        if ($this->checkIfGoodStatusCode($status_code)) {
                            //  Set an info log that we are displaying the custom message
                            $this->logInfo('Start processing the custom message to display for status code <span class="text-success">'.$status_code.'</span>');
                        } else {
                            //  Set an info log that we are displaying the custom message
                            $this->logInfo('Start processing the custom message to display for status code <span class="text-danger">'.$status_code.'</span>');
                        }

                        //  If the custom message uses the PHP Code Editor
                        if ($uses_code_editor_mode == true) {
                            //  Get the custom message code
                            $custom_message_text = $selected_handle['on_handle']['use_custom_msg']['code_editor_text'];

                        //  If the custom message does not use the PHP Code Editor
                        } else {
                            //  Get the custom message text
                            $custom_message_text = $selected_handle['on_handle']['use_custom_msg']['text'];
                        }

                        //  Process dynamic content embedded within the custom message
                        $outputResponse = $this->handleEmbeddedDynamicContentConversion($custom_message_text, $uses_code_editor_mode);

                        //  If processing the custom message failed, return the failed response otherwise continue
                        if ($this->shouldDisplayScreen($outputResponse)) {
                            return $outputResponse;
                        }

                        //  Set an info log of the final result
                        $this->logInfo('Final result: <br /><span class="text-success">'.$outputResponse.'</span>');

                        //  Return the processed custom message display
                        return $this->showCustomScreen($outputResponse);
                    } elseif ($on_handle_type == 'do_nothing') {
                        //  Return nothing
                        return null;
                    }
                } else {
                    //  Set a warning log that the custom API does not have a matching response status handle
                    $this->logWarning('No matching status handle to process the current response of status <span class="text-success">'.$status_code.'</span>');
                }
            } else {
                //  Set a warning log that the custom API does not have response status handles
                $this->logWarning('No response status handles to process the current response of status <span class="text-success">'.$status_code.'</span>');
            }

            //  Set a warning log that the custom API cannot be handled manually
            $this->logWarning('Could not handle the response <span class="text-success">Manually</span>, attempt to handle <span class="text-success">Automatically</span>');

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
                    //  If the storage type is of type "Code"
                } elseif ($storage_type == 'code') {
                    //  Get the dataset
                    $code = $this->event['event_data']['storage']['code']['dataset']['value'];

                    //  If the dataset was provided
                    if (!empty($code)) {
                        return $this->handleCodeLocalStorage();
                    }
                }
            } else {
                $this->logWarning('The provided Local Storage <span class="text-success">'.$this->event['name'].'</span> does not have a reference name');
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
            //  Convert the dynamic content (if any) embedded within the value
            $outputResponse = $this->convertMustacheTagOrEmbeddedDynamicContentIntoDynamicData(
                //  Text containing embedded dynamic content that must be convert
                $array_value['value'],
                //  Is this text information generated using the PHP Code Editor
                false
            );

            //  If we counld't get the data from the array value
            if ($this->shouldDisplayScreen($outputResponse)) {
                $outputResponse = $this->handleLocalStorageEmptyValue($reference_name, $array_value);

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) {
                    return $outputResponse;
                }

                //  Set the storage value to the received array value
                $storage_value = $outputResponse;
            } else {
                //  Set the storage value to the processed array value
                $storage_value = $outputResponse;
            }

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
            //  Convert the dynamic content (if any) embedded within the value
            $outputResponse = $this->convertMustacheTagOrEmbeddedDynamicContentIntoDynamicData(
                //  Text containing embedded dynamic content that must be convert
                $array_key_value['value'],
                //  Is this text information generated using the PHP Code Editor
                false
            );

            //  If we counld't get the data from the array value
            if ($this->shouldDisplayScreen($outputResponse)) {
                $outputResponse = $this->handleLocalStorageEmptyValue($reference_name, $array_value);

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) {
                    return $outputResponse;
                }

                //  Set the storage value to the received array value
                $storage_value = $outputResponse;
            } else {
                //  Set the storage value to the processed array value
                $storage_value = $outputResponse;
            }

            //  Add current processed value to the the processed values array
            $processed_values[$array_key_value['key']] = $storage_value;
        }

        //  Store the processed values
        $this->handleProcessedValueStorage($reference_name, $processed_values, $mode);
    }

    public function handleCodeLocalStorage()
    {
        //  Get the local storage reference name
        $reference_name = $this->event['event_data']['reference_name'];

        //  Get the dataset mode e.g "concatenate", "replace", "append", "prepend"
        $mode = $this->event['event_data']['storage']['code']['mode']['selected_type'];

        //  Get the dataset code
        $join = $this->event['event_data']['storage']['code']['mode']['concatenate']['value'];

        //  Get the dataset code
        $code = $this->event['event_data']['storage']['code']['dataset']['value'];

        //  Remove the PHP tags from the PHP Code
        $code = $this->removePHPTags($code);

        //  Process the PHP Code
        $outputResponse = $this->processPHPCode("$code");

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) {
            return $outputResponse;
        }

        $processed_values = $outputResponse;

        //  Store the processed values
        $this->handleProcessedValueStorage($reference_name, $processed_values, $mode, $join);
    }

    public function handleLocalStorageEmptyValue($name, $value)
    {
        $this->logWarning('Could not get the value for <span class="text-success">'.$name.'</span> from the Local Storage.');

        //  Get selected response for handling value without data e.g "default", "nullable"
        $handle_type = $value['on_empty_value']['selected_type'];

        //  If we must display a default value
        if ($handle_type == 'default') {
            $this->logInfo('Using default value for <span class="text-success">'.$name.'</span> from the Local Storage.');

            //  Get selected default type e.g "text_input", "number_input", "true", "false", "null", 'empty_array'
            $default_type = $value['on_empty_value']['default']['selected_type'];

            if ($default_type == 'text_input') {
                //  Get the default text input
                $text_input = $value['on_empty_value']['default']['text_input'];

                //  Convert the dynamic content (if any) embedded within the text input
                $outputResponse = $this->convertMustacheTagOrEmbeddedDynamicContentIntoDynamicData(
                    //  Text containing embedded dynamic content that must be convert
                    $text_input,
                    //  Is this text information generated using the PHP Code Editor
                    false
                );

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) {
                    return $outputResponse;
                }

                //  Set the storage value to the processed text input
                $storage_value = $outputResponse;

                $dataType = ucwords(gettype($storage_value));

                //  Get the result type e.g Object, Array, Boolean e.t.c and wrap in square brackets
                $output = '['.$dataType.']';

                $this->logInfo('Setting value of <span class="text-success">'.$name.'</span> to <span class="text-success">'.$output.'</span>');
            } elseif ($default_type == 'number_input') {
                //  Get the default text input
                $number_input = $value['on_empty_value']['default']['number_input'];

                //  Convert the dynamic content (if any) embedded within the number input
                $outputResponse = $this->convertMustacheTagOrEmbeddedDynamicContentIntoDynamicData(
                    //  Text containing embedded dynamic content that must be convert
                    $number_input,
                    //  Is this text information generated using the PHP Code Editor
                    false
                );

                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) {
                    return $outputResponse;
                }

                //  Set the storage value to the processed number input (Make sure its set to be an integer)
                $storage_value = (int) $outputResponse;

                $dataType = ucwords(gettype($storage_value));

                //  Get the result type e.g Object, Array, Boolean e.t.c and wrap in square brackets
                $output = '['.$dataType.']';

                $this->logInfo('Setting value of <span class="text-success">'.$name.'</span> to <span class="text-success">'.$output.'</span>');
            } elseif ($default_type == 'true') {
                $this->logInfo('Setting value of <span class="text-success">'.$name.'</span> to <span class="text-success">[TRUE]</span>');

                //  Set the storage value to "True"
                $storage_value = true;
            } elseif ($default_type == 'false') {
                $this->logInfo('Setting value of <span class="text-success">'.$name.'</span> to <span class="text-success">[FALSE]</span>');

                //  Set the storage value to "False"
                $storage_value = false;
            } elseif ($default_type == 'null') {
                $this->logInfo('Setting value of <span class="text-success">'.$name.'</span> to <span class="text-success">[NULL]</span>');

                //  Set the storage value to "Null"
                $storage_value = null;
            } elseif ($default_type == 'empty_array') {
                $this->logInfo('Setting value of <span class="text-success">'.$name.'</span> to <span class="text-success">[Empty Array]</span>');

                //  Set the storage value to an "Empty Array"
                $storage_value = [];
            }

            //  If we must set the value to null
        } elseif ($handle_type == 'nullable') {
            $this->logInfo('Setting value of <span class="text-success">'.$name.'</span> to <span class="text-success">[NULL]</span>');

            //  Set the storage value to NULL
            $storage_value = null;
        }

        return $storage_value;
    }

    public function handleProcessedValueStorage($reference_name, $processed_values, $mode, $join = ' ')
    {
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

                //  If the mode is set to "append"
                } else {
                    /** If we have only one value e.g
                     *  $processed_values = ["Francistown"] or $processed_values = ["Gaborone"].
                     */
                    if (count($processed_values) == 1) {
                        /* Ungroup the result by removing the braces [] e.g
                         *
                         *  Allow for this:.
                         *
                         *  $this->dynamic_data_storage[locations] = ["Francistown", "Gaborone" ]
                         *
                         *  Instead of this:
                         *
                         *  $this->dynamic_data_storage[locations] = [ ["Francistown"], ["Gaborone"] ]
                         *
                         *  If we have more than one value then we do not need to do this othrwise we get:
                         *
                         *  $this->dynamic_data_storage[locations] = ["1", "Francistown", "2", "Gaborone" ]
                         *
                         *  Instead of this:
                         *
                         *  $this->dynamic_data_storage[locations] = [ ["1", "Francistown"], ["2", "Gaborone"] ]
                         */
                        if (isset($processed_values[0])) {
                            $processed_values = $processed_values[0];
                        }
                    }

                    if ($mode == 'append') {
                        if (isset($this->dynamic_data_storage[$reference_name]) && is_array($this->dynamic_data_storage[$reference_name])) {
                            $exising_array_data = $this->dynamic_data_storage[$reference_name];

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
                        if (isset($this->dynamic_data_storage[$reference_name]) && is_array($this->dynamic_data_storage[$reference_name])) {
                            $exising_array_data = $this->dynamic_data_storage[$reference_name];

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
                $dataType = ucwords(gettype($processed_values));

                $mode = ucwords($mode);

                $this->logInfo('Local storage using the Mode = <span class="text-success">['.$mode.']</span> requires the data to be of type <span class="text-success">[Array]</span>, however we received data of type <span class="text-success">['.$dataType.']</span>');
            }

            //  If the storage value is a string and the given mode matches the string modes
        } elseif ($matches_string_modes) {
            if (is_string($processed_values)) {
                //  If the mode is set to "replace"
                if ($mode == 'concatenate') {
                    if (isset($this->dynamic_data_storage[$reference_name]) && is_string($this->dynamic_data_storage[$reference_name])) {
                        $exising_array_data = $this->dynamic_data_storage[$reference_name];

                        //  Concatenate the dataset
                        $exising_array_data .= $join.$processed_values;

                        //  Store the string value as dynamic data
                        $this->storeDynamicData($reference_name, $exising_array_data);
                    } else {
                        //  Store the array value(s) as dynamic data
                        $this->storeDynamicData($reference_name, $processed_values);
                    }
                }
            } else {
                $dataType = ucwords(gettype($processed_values));

                $mode = ucwords($mode);

                $this->logInfo('Local storage using the Mode = <span class="text-success">['.$mode.']</span> requires the data to be of type <span class="text-success">[String]</span>, however we received data of type <span class="text-success">['.$dataType.']</span>');
            }
        }
    }

    public function convertMustacheTagOrEmbeddedDynamicContentIntoDynamicData($text = '', $uses_code_editor_mode = false)
    {
        //  If the provided text is a valid mustache tag
        if ($uses_code_editor_mode == false && $this->isValidMustacheTag($text, false)) {
            $mustache_tag = $text;

            // Convert the mustache tag into dynamic data
            $outputResponse = $this->convertMustacheTagIntoDynamicData($mustache_tag);

        //  If the provided value is not a valid mustache tag
        } else {
            //  Process dynamic content embedded within the target input
            $outputResponse = $this->handleEmbeddedDynamicContentConversion(
                //  Text containing embedded dynamic content that must be convert
                $text,
                //  Is this text information generated using the PHP Code Editor
                $uses_code_editor_mode
            );
        }

        //  Return the build response
        return $outputResponse;
    }

    /******************************************
     *  VALIDATION EVENT METHODS              *
     *****************************************/

    /*  handle_Validation_Event()
     *  This method gets all the validation rules of the current display. We then use these
     *  validation rules to validate the target input.
     */
    public function handle_Validation_Event()
    {
        if ($this->event) {
            //  Get the validation rules
            $validation_rules = $this->event['event_data']['rules'] ?? [];

            //  Get the target input
            $target_value = $this->event['event_data']['target'];

            //  If the target input is provided
            if (!empty($target_value)) {
                //  If the provided target input is a valid mustache tag
                if ($this->isValidMustacheTag($target_value, false)) {
                    $mustache_tag = $target_value;

                    // Convert the mustache tag into dynamic data
                    $outputResponse = $this->convertMustacheTagIntoDynamicData($mustache_tag);

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) {
                        return $outputResponse;
                    }

                    //  Get the mustache tag dynamic data and use it as the target input
                    $target_value = $outputResponse;

                //  If the provided value is not a valid mustache tag
                } else {
                    //  Process dynamic content embedded within the target input
                    $buildResponse = $this->handleEmbeddedDynamicContentConversion(
                        //  Text containing embedded dynamic content that must be convert
                        $target_value,
                        //  Is this text information generated using the PHP Code Editor
                        false
                    );

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($buildResponse)) {
                        return $buildResponse;
                    }

                    //  Get the built option value
                    $target_value = $buildResponse;
                }

                //  Validate the target input
                $failedValidationResponse = $this->handleValidationRules($target_value, $validation_rules);

                //  If the current user response failed the validation return the failed response otherwise continue
                if ($this->shouldDisplayScreen($failedValidationResponse)) {
                    return $failedValidationResponse;
                }
            }
        }
    }

    /*  validateCurrentScreenUserResponse()
     *  This method checks if the given validation rules are active (If they must be used).
     *  If the validation rule must be used then we determine which rule we are given and which
     *  validation method must be used for each given case.
     */
    public function handleValidationRules($target_value, $validation_rules = [])
    {
        //  If we have validation rules
        if (!empty($validation_rules)) {
            //  For each validation rule
            foreach ($validation_rules as $validation_rule) {
                //  If the current validation rule is active (Must be used)
                if ($validation_rule['active'] == true) {
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

                        case 'validate_email':

                            return $this->applyValidationRule($target_value, $validation_rule, 'validateEmail'); break;

                        //case 'validate_mobile_number':

                            //return $this->applyValidationRule($target_value, $validation_rule, 'validateMobileNumber'); break;

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
                    }
                }
            }
        }

        //  Return null to indicate that validation passed
        return null;
    }

    /*  validateOnlyLetters()
     *  This method validates to make sure the target input
     *  is only letters with or without spaces
     */
    public function validateOnlyLetters($target_value, $validation_rule)
    {
        //  Regex pattern
        $pattern = '/^[a-zA-Z\s]+$/';

        //  If the pattern was not matched exactly i.e validation failed
        if (!preg_match($pattern, $target_value)) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  validateOnlyNumbers()
     *  This method validates to make sure the target input
     *  is only numbers with or without spaces
     */
    public function validateOnlyNumbers($target_value, $validation_rule)
    {
        //  Regex pattern
        $pattern = '/^[0-9\s]+$/';

        //  If the pattern was not matched exactly i.e validation failed
        if (!preg_match($pattern, $target_value)) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  validateOnlyLettersAndNumbers()
     *  This method validates to make sure the target input
     *  is only letters and numbers with or without spaces
     */
    public function validateOnlyLettersAndNumbers($target_value, $validation_rule)
    {
        //  Regex pattern
        $pattern = '/^[a-zA-Z0-9\s]+$/';

        //  If the pattern was not matched exactly i.e validation failed
        if (!preg_match($pattern, $target_value)) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  validateMinimumCharacters()
     *  This method validates to make sure the target input
     *  has characters the length of the minimum characters
     *  allowed of more
     */
    public function validateMinimumCharacters($target_value, $validation_rule)
    {
        $minimum_characters = $validation_rule['min'];

        //  If the pattern was not matched exactly i.e validation failed
        if (!(strlen($target_value) >= $minimum_characters)) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  validateMaximumCharacters()
     *  This method validates to make sure the target input
     *  has characters the length of the minimum characters
     *  allowed of more
     */
    public function validateMaximumCharacters($target_value, $validation_rule)
    {
        $maximum_characters = $validation_rule['max'];

        //  If the pattern was not matched exactly i.e validation failed
        if (!(strlen($target_value) <= $maximum_characters)) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  validateEmail()
     *  This method validates to make sure the target input
     *  is a valid email e.g example@gmail.com
     */
    public function validateEmail($target_value, $validation_rule)
    {
        //  Regex pattern
        $pattern = '/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD';

        //  If the pattern was not matched exactly i.e validation failed
        if (!preg_match($pattern, $target_value)) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  validateMobileNumber()
     *  This method validates to make sure the target input
     *  is a valid mobile number (Botswana Mobile Numbers)
     *  e.g 71234567
     */
    public function validateMobileNumber($target_value, $validation_rule)
    {
        //  Regex pattern
        $pattern = '/^[7]{1}[1234567]{1}[0-9]{6}$/';

        //  If the pattern was not matched exactly i.e validation failed
        if (!preg_match($pattern, $target_value)) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  validateDateFormat()
     *  This method validates to make sure the target input
     *  is a valid date format e.g DD/MM/YYYY
     */
    public function validateDateFormat($target_value, $validation_rule)
    {
        //  Regex pattern
        $pattern = '/[0-9]{2}\/[0-9]{2}\/[0-9]{4}/';

        //  If the pattern was not matched exactly i.e validation failed
        if (!preg_match($pattern, $target_value)) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  validateEqualTo()
     *  This method validates to make sure the target input
     *  has characters equal to a given value
     */
    public function validateEqualTo($target_value, $validation_rule)
    {
        $value = $validation_rule['value'];

        //  If the pattern was not matched exactly i.e validation failed
        if (!($target_value == $value)) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  validateNotEqualTo()
     *  This method validates to make sure the target input
     *  has characters not equal to a given value
     */
    public function validateNotEqualTo($target_value, $validation_rule)
    {
        $value = $validation_rule['value'];

        //  If the pattern was not matched exactly i.e validation failed
        if (!($target_value != $value)) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  validateLessThan()
     *  This method validates to make sure the target input
     *  has characters less than a given value
     */
    public function validateLessThan($target_value, $validation_rule)
    {
        //  Convert the target input into a number
        $target_value = (int) $target_value;

        //  Convert the given value into a number
        $value = (int) $validation_rule['value'];

        //  If the pattern was not matched exactly i.e validation failed
        if (!($target_value < $value)) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  validateLessThanOrEqualTo()
     *  This method validates to make sure the target input
     *  has characters less than or equal to a given value
     */
    public function validateLessThanOrEqualTo($target_value, $validation_rule)
    {
        //  Convert the target input into a number
        $target_value = (int) $target_value;

        //  Convert the given value into a number
        $value = (int) $validation_rule['value'];

        //  If the pattern was not matched exactly i.e validation failed
        if (!($target_value <= $value)) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  validateGreaterThan()
     *  This method validates to make sure the target input
     *  has characters grater than a given value
     */
    public function validateGreaterThan($target_value, $validation_rule)
    {
        //  Convert the target input into a number
        $target_value = (int) $target_value;

        //  Convert the given value into a number
        $value = (int) $validation_rule['value'];

        //  If the pattern was not matched exactly i.e validation failed
        if (!($target_value > $value)) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  validateGreaterThanOrEqualTo()
     *  This method validates to make sure the target input
     *  has characters grater than a given value
     */
    public function validateGreaterThanOrEqualTo($target_value, $validation_rule)
    {
        //  Convert the target input into a number
        $target_value = (int) $target_value;

        //  Convert the given value into a number
        $value = (int) $validation_rule['value'];

        //  If the pattern was not matched exactly i.e validation failed
        if (!($target_value >= $value)) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  validateInBetweenIncluding()
     *  This method validates to make sure the target input
     *  has characters inbetween the given min and max values
     *  (Including the Min and Max values)
     */
    public function validateInBetweenIncluding($target_value, $validation_rule)
    {
        //  Convert the target input into a number
        $target_value = (int) $target_value;

        //  Convert the given value into a number
        $min = (int) $validation_rule['min'];

        //  Convert the given value into a number
        $max = (int) $validation_rule['max'];

        //  If the pattern was not matched exactly i.e validation failed
        if (!(($min <= $target_value) && ($target_value <= $max))) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  validateInBetweenExcluding()
     *  This method validates to make sure the target input
     *  has characters inbetween the given min and max values
     *  (Excluding the Min and Max values)
     */
    public function validateInBetweenExcluding($target_value, $validation_rule)
    {
        //  Convert the target input into a number
        $target_value = (int) $target_value;

        //  Convert the given value into a number
        $min = (int) $validation_rule['min'];

        //  Convert the given value into a number
        $max = (int) $validation_rule['max'];

        //  If the pattern was not matched exactly i.e validation failed
        if (!(($min < $target_value) && ($target_value < $max))) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  validateNoSpaces()
     *  This method validates to make sure the target input
     *  has no characters that are spaces
     */
    public function validateNoSpaces($target_value, $validation_rule)
    {
        //  Regex pattern
        $pattern = '/[\s]/';

        //  If we found spaces i.e validation failed
        if (preg_match($pattern, $target_value)) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  validateCustomRegex()
     *  This method validates to make sure the target input
     *  matches the given custom regex rule
     */
    public function validateCustomRegex($target_value, $validation_rule)
    {
        //  Regex pattern
        $pattern = $validation_rule['rule'];

        //  If we found spaces i.e validation failed
        if (preg_match($pattern, $target_value)) {
            //  Handle the failed validation
            return $this->handleFailedValidation($validation_rule);
        }
    }

    /*  applyValidationRule()
     *  This method gets the validation rule and callback. The callback represents the name of
     *  the validation function that we must run to validate the current input target. Since
     *  we allow custom Regex patterns for custom validation support, we must perform this under
     *  a try/catch incase the provided custom Regex pattern is invalid. This will allow us to
     *  catch any emerging error and be able to use the handleFailedValidation() in order to
     *  display the fatal error message and additional debugging details.
     */
    public function applyValidationRule($target_value, $validation_rule, $callback)
    {
        try {
            /* Perform the validation method here e.g "validateOnlyLetters()" within the try/catch
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

    /*  handleFailedValidation()
     *  This method logs a warning with details about the failed validation rule
     */
    public function handleFailedValidation($validation_rule)
    {
        $error_message = $validation_rule['error_msg'];

        //  Process dynamic content embedded within the expected option input
        $outputResponse = $this->handleEmbeddedDynamicContentConversion(
            //  Text containing embedded dynamic content that must be convert
            $error_message,
            //  Is this text information generated using the PHP Code Editor
            false
        );

        //  If we have a screen to show return the response otherwise continue
        if ($this->shouldDisplayScreen($outputResponse)) {
            $this->logWarning('Validation failed using ('.$validation_rule['name'].')');

            return $outputResponse;
        }

        $error_message = $outputResponse;

        $this->logWarning('Validation failed using ('.$validation_rule['name'].'): <span class="text-error">'.$error_message.'</span>');

        //  Return the processed custom validation error message display
        return $this->showCustomGoBackScreen($error_message."\n");
    }

    /******************************************
     *  REDIRECT EVENT METHODS                *
     *****************************************/

    /*  handle_Revisit_Event()
     *  This method gets all the revisit instructions of the current display. We then use these
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
            $additional_responses = $this->event['event_data']['general']['additional_responses'];

            //  Get the redirect type e.g "home_revisit", "screen_revisit", "marked_revisit"
            $revisit_type = $this->event['event_data']['revisit_type']['selected_type'];

            $is_triggered = false;

            /** If the trigger is manual, this means that the redirect is only
             *  triggered if the user provided the trigger input and if the
             *  input matches the required value to trigger the redirect.
             */
            if( $trigger == 'manual' ){

                $this->logInfo('Handling <span class="text-success">Manual Revisit</span>');

                //  If the manual input is provided
                if (!empty($manual_trigger_input)) {

                    //  If the provided input is a valid mustache tag
                    if ($this->isValidMustacheTag($manual_trigger_input, false)) {

                        //  Get the mustache tag
                        $mustache_tag = $manual_trigger_input;

                        // Convert the mustache tag into dynamic data
                        $outputResponse = $this->convertMustacheTagIntoDynamicData($mustache_tag);

                        //  If we have a screen to show return the response otherwise continue
                        if ($this->shouldDisplayScreen($outputResponse)) {

                            return $outputResponse;

                        }

                        //  If the input is not a type of [String] or [Integer]
                        if ( !( is_string($input) || is_integer($input) )) {
            
                            $dataType = ucwords(gettype($input));
            
                            //  Set an warning log that the input must be of type [String] or [Integer]
                            $this->logWarning('The given <span class="text-success">Revisit Input</span> from <span class="text-success">Manual Trigger Input</span> must return data of type <span class="text-success">[String]</span> or <span class="text-success">[Integer]</span> however we received a value of type <span class="text-success">['.$dataType.']</span>');
            
                        }else{

                            //  Get the mustache tag dynamic data and use it as the input
                            $manual_trigger_input = $outputResponse;
            
                        }

                    //  If the provided value is not a valid mustache tag
                    } else {
                        
                        //  Process dynamic content embedded within the input
                        $buildResponse = $this->handleEmbeddedDynamicContentConversion(
                            //  Text containing embedded dynamic content that must be convert
                            $manual_trigger_input,
                            //  Is this text information generated using the PHP Code Editor
                            false
                        );

                        //  If we have a screen to show return the response otherwise continue
                        if ($this->shouldDisplayScreen($buildResponse)) {

                            return $buildResponse;

                        }

                        //  Get the built option value
                        $manual_trigger_input = $buildResponse;
                    }

                    //  If the manual trigger input matches the current user input
                    if( $manual_trigger_input == $this->current_user_response ){
                        
                        //  Trigger the event manually to redirect
                        $is_triggered = true;
    
                    }

                }

            }else{

                $this->logInfo('Handling <span class="text-success">Automatic Revisit</span>');

                //  Trigger the event automatically to redirect
                $is_triggered = true;

            }

            //  If the event has been triggered
            if( $is_triggered ){

                $this->logInfo('The <span class="text-success">'.$this->event['name'].'</span> event has been triggered');
                
                //  Check if the additional responses uses "Code Editor Mode"
                $uses_code_editor_mode = $additional_responses['code_editor_mode'];
                
                //  If the additional responses text uses the PHP Code Editor
                if ($uses_code_editor_mode == true) {

                    //  Get the additional responses text code
                    $additional_responses_text = $additional_responses['code_editor_text'];

                //  If the additional responses text does not use the PHP Code Editor
                } else {
                    
                    //  Get the additional responses text
                    $additional_responses_text = $additional_responses['text'];

                }

                //  If the provided text is a valid mustache tag
                if ($this->isValidMustacheTag($additional_responses_text, false)) {

                    //  Get the mustache tag
                    $mustache_tag = $additional_responses_text;

                    // Convert the mustache tag into dynamic data
                    $outputResponse = $this->convertMustacheTagIntoDynamicData($mustache_tag);

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) {

                        return $outputResponse;

                    }

                    //  Get the mustache tag dynamic data and use it as the text
                    $additional_responses_text = $outputResponse;

                //  If the provided text is not a valid mustache tag
                } else {
                    
                    //  Process dynamic content embedded within the text
                    $buildResponse = $this->handleEmbeddedDynamicContentConversion(
                        //  Text containing embedded dynamic content that must be convert
                        $additional_responses_text,
                        //  Is this text information generated using the PHP Code Editor
                        $uses_code_editor_mode
                    );

                    //  If we have a screen to show return the response otherwise continue
                    if ($this->shouldDisplayScreen($buildResponse)) {

                        return $buildResponse;

                    }

                    //  Get the built option value
                    $additional_responses_text = $buildResponse;
                }

                //  If the text is not a type of [String] or [Integer]
                if ( !( is_string($additional_responses_text) || is_integer($additional_responses_text) )) {
    
                    $dataType = ucwords(gettype($additional_responses_text));
    
                    //  Set an warning log that the input must be of type [String] or [Integer]
                    $this->logWarning('The given <span class="text-success">Additional Responses</span> must return data of type <span class="text-success">[String]</span> or <span class="text-success">[Integer]</span> however we received a value of type <span class="text-success">['.$dataType.']</span>');
                    
                    //  Empty the value
                    $additional_responses_text = '';

                }

                if( $revisit_type ==  'home_revisit'){

                    return $this->handleHomeRevisit($additional_responses_text);
    
                }else if( $revisit_type ==  'screen_revisit'){

                    return $this->handleScreenRevisit($additional_responses_text);
    
                }else if( $revisit_type ==  'marked_revisit'){
    
                }
            }
        }
    }

    public function handleHomeRevisit($additional_responses_text = '')
    {
        if( !empty( $additional_responses_text ) ){

            $service_code = substr($this->service_code, 0, -1).'*'.$additional_responses_text.'#';

        }else{
            
            $service_code = $this->service_code;

        }

        $this->logInfo('Revisiting Home: <span class="text-success">'.$service_code.'</span>');

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
        $this->text = $additional_responses_text;

        //  Handle existing session - Re-run the handleExistingSession()
        return $this->handleExistingSession(false);
        
    }

    public function handleScreenRevisit($additional_responses = [])
    {
        $outputResponse = $this->merge_array_text_responses($this->service_code, $additional_responses);

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

    /*  handle_Redirect_Event()
     *  This method gets all the redirect instructions of the current display. We then use these
     *  redirect instructions to allow the current display to redirect to another service code
     *  e.g from *321*45# to *321*80# or *150#
     */
    public function handle_Redirect_Event()
    {
        if ($this->event) {

            //  Get the trigger type e.g "automatic", "manual"
            $trigger = $this->event['event_data']['general']['trigger']['selected_type'];

            //  Get the trigger type e.g "automatic", "manual"
            $manual_trigger_input = $this->event['event_data']['general']['trigger']['manual']['input'];

            //  Get the additional responses
            $additional_responses = $this->event['event_data']['general']['additional_responses'];

            //  Get the redirect type e.g "home_redirect", "screen_redirect", "marked_redirect", "custom_redirect"
            $redirect_type = $this->event['event_data']['redirect_type']['selected_type'];

            $is_triggered = false;

            /** If the trigger is manual, this means that the redirect is only
             *  triggered if the user provided the trigger input and if the
             *  input matches the required value to trigger the redirect.
             */
            if( $trigger == 'manual' ){

                $this->logInfo('Handling <span class="text-success">Manual Redirect</span>');

                //  If the manual input is provided
                if (!empty($manual_trigger_input)) {

                    //  If the provided input is a valid mustache tag
                    if ($this->isValidMustacheTag($manual_trigger_input, false)) {

                        //  Get the mustache tag
                        $mustache_tag = $manual_trigger_input;

                        // Convert the mustache tag into dynamic data
                        $outputResponse = $this->convertMustacheTagIntoDynamicData($mustache_tag);

                        //  If we have a screen to show return the response otherwise continue
                        if ($this->shouldDisplayScreen($outputResponse)) {

                            return $outputResponse;

                        }

                        //  If the input is not a type of [String] or [Integer]
                        if ( !( is_string($input) || is_integer($input) )) {
            
                            $dataType = ucwords(gettype($input));
            
                            //  Set an warning log that the input must be of type [String] or [Integer]
                            $this->logWarning('The given <span class="text-success">Redirect Input</span> from <span class="text-success">Manual Trigger Input</span> must return data of type <span class="text-success">[String]</span> or <span class="text-success">[Integer]</span> however we received a value of type <span class="text-success">['.$dataType.']</span>');
            
                        }else{

                            //  Get the mustache tag dynamic data and use it as the input
                            $manual_trigger_input = $outputResponse;
            
                        }

                    //  If the provided value is not a valid mustache tag
                    } else {
                        
                        //  Process dynamic content embedded within the input
                        $buildResponse = $this->handleEmbeddedDynamicContentConversion(
                            //  Text containing embedded dynamic content that must be convert
                            $manual_trigger_input,
                            //  Is this text information generated using the PHP Code Editor
                            false
                        );

                        //  If we have a screen to show return the response otherwise continue
                        if ($this->shouldDisplayScreen($buildResponse)) {

                            return $buildResponse;

                        }

                        //  Get the built option value
                        $manual_trigger_input = $buildResponse;
                    }

                    //  If the manual trigger input matches the current user input
                    if( $manual_trigger_input == $this->current_user_response ){
                        
                        //  Trigger the event manually to redirect
                        $is_triggered = true;
    
                    }

                }

            }else{

                $this->logInfo('Handling <span class="text-success">Automatic Redirect</span>');

                //  Trigger the event automatically to redirect
                $is_triggered = true;

            }

            //  If the event has been triggered
            if( $is_triggered ){

                if( $redirect_type ==  'home_redirect'){

                    $outputResponse = $this->handleHomeRedirect($additional_responses);
    
                }else if( $redirect_type ==  'screen_redirect'){

                    $outputResponse = $this->handleScreenRedirect($additional_responses);
    
                }else if( $redirect_type ==  'marked_redirect'){
    
                }else if( $redirect_type ==  'custom_redirect'){
    
                }


                //  If we have a screen to show return the response otherwise continue
                if ($this->shouldDisplayScreen($outputResponse)) {

                    return $outputResponse;

                }

                //  Get the processed service code
                $service_code = $outputResponse;

                /** 
                 *  
                 * Update the current session. We need to reset the "text" so that we don't have any
                 *  data in it. This is because when we redirect we still continue using the same session,
                 *  however if we use the same session the builder will attempt to use the old "text" which
                 *  is no longer required after the redirect.We also need to update the expected timeout date
                 *  and time.
                */
                $this->updateCurrentSession();
    
                return $this->showRedirectScreen($service_code);

            }

        }
    }

    /*  formatCurrentScreenUserResponse()
     *  This method gets all the formatting rules of the current screen. We then use these
     *  formatting rules to format the users response for the current screen.
     */
    public function formatCurrentScreenUserResponse()
    {
        //  Get the validation rules
        $formattingRules = $this->screenContent['formatting']['rules'] ?? [];

        //  Format the user response (Input provided by the user)
        $failedFormatResponse = $this->handleFormattingRules($formattingRules);

        //  If the current user response failed to format return the failed response otherwise continue
        if ($this->shouldDisplayScreen($failedFormatResponse)) {
            return $failedFormatResponse;
        }

        //  Return null if formatting passes
        return null;
    }

    /*  handleFormattingRules()
     *  This method checks if the given formatting rules are active (If they must be used).
     *  If the formatting rule must be used then we determine which rule we are given and which
     *  formatting method must be used for each given case.
     */
    public function handleFormattingRules($formattingRules = [])
    {
        //  If we have formatting rules
        if (!empty($formattingRules)) {
            //  For each formatting rulle
            foreach ($formattingRules as $formattingRule) {
                //  If the current formatting rule is active (Must be used)
                if ($formattingRule['active'] == true) {
                    //  Get the type of formatting rule e.g "capitalize" or "uppercase"
                    $formattingType = $formattingRule['type'];

                    //  Use the switch statement to determine which formatting method to use
                    switch ($formattingType) {
                        case 'capitalize':

                            return $this->applyFormattingRule($formattingRule, 'capitalizeFormat'); break;

                        /*
                        case 'uppercase':

                            return $this->applyFormattingRule($formattingRule, 'uppercaseFormat'); break;

                        case 'lowercase':

                            return $this->applyFormattingRule($formattingRule, 'lowercaseFormat'); break;
                        */
                    }
                }
            }
        }

        //  Return null to indicate that formatting passed
        return null;
    }

    /*  capitalizeFormat()
     *  This method formats the current screen user's response
     *  by capitalizing the given string
     */
    public function capitalizeFormat($formattingRule)
    {
        //  Capitalize the current string
        $this->current_user_response = ucwords(strtolower($this->current_user_response));
    }

    /*  applyFormattingRule()
     *  This method gets the formatting rule and callback. The callback represents the name of
     *  the formatting function that we must run to format the current users response. Since we
     *  allow custom code for custom formatting support, we must perform this under a try/catch
     *  incase the provided custom PHP code is invalid. This will allow us to catch any emerging
     *  error and be able to use the handleFailedFormatting() in order to display the fatal
     *  error message and additional debugging details.
     */
    public function applyFormattingRule($formattingRule, $callback)
    {
        $initialUserResponse = $this->current_user_response;

        try {
            /* Perform the formatting method here e.g "capitalizeFormat()" within the try/catch
             *  method and pass the formatting rule e.g "capitalizeFormat( $formattingRule )"
             */
            $callback($formattingRule);
        } catch (\Throwable $e) {
            //  Handle failed formatting
            $this->handleFailedFormatting($formattingRule);

            //  Handle try catch error
            return $this->handleTryCatchError($e);
        } catch (Exception $e) {
            //  Handle failed formatting
            $this->handleFailedFormatting($formattingRule);

            //  Handle try catch error
            return $this->handleTryCatchError($e);
        }
    }

    /*  handleFailedFormatting()
     *  This method logs a warning with details about the failed formatting rule
     */
    public function handleFailedFormatting($formattingRule)
    {
        //  Handle failed formatting
        $this->logWarning('Formatting failed using ('.$formattingRule['name'].') for <span class="text-success">'.$formattingRule.'</span>');
    }

    /*  checkIfDisplayMustLink()
     *  This method checks if the current screen has a screen it can link to. If (yes)
     *  we return true, if (no) we return false.
     *
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

    public function shouldDisplayScreen($text = '')
    {
        if (is_string($text)) {
            //  If the first 3 characters of the text match the words "CON" or "END" or "TIM" or "RED" then this is a display screen
            return (substr($text, 0, 3) == 'CON' ||     
                    substr($text, 0, 3) == 'END' || 
                    substr($text, 0, 3) == 'TIM' || 
                    substr($text, 0, 3) == 'RED' ) 
                    ? true : false;
        }

        return false;
    }

    public function isContinueScreen($text = '')
    {
        if (is_string($text)) {
            //  If the first 3 characters of the text match the word "CON" then this is a continuing display screen
            return  (substr($text, 0, 3) == 'CON') ? true : false;
        }

        return false;
    }

    public function isEndScreen($text = '')
    {
        if (is_string($text)) {
            //  If the first 3 characters of the text match the word "END" then this is an ending display screen
            return  (substr($text, 0, 3) == 'END') ? true : false;
        }

        return false;
    }

    public function isRedirectScreen($text = '')
    {
        if (is_string($text)) {
            //  If the first 3 characters of the text match the word "RED" then this is a redirecting display screen
            return  (substr($text, 0, 3) == 'RED') ? true : false;
        }

        return false;
    }

    public function isTimeoutScreen($text = '')
    {
        if (is_string($text)) {
            //  If the first 3 characters of the text match the word "TIM" then this is a timeout display screen
            return  (substr($text, 0, 3) == 'TIM') ? true : false;
        }

        return false;
    }

    public function getResponseMsg($text)
    {
        /* If the content after the first 4 characters of the response text.
         *  This will remove the words "CON ", "END " or "RED " from the
         *  begining of the text.
         */
        return  substr($text, 4);
    }

    public function handleEmbeddedDynamicContentConversion($text = '', $uses_code_editor_mode = true)
    {
        //  If the text uses the PHP Code Editor
        if ($uses_code_editor_mode == true) {

            //  Get the text code otherwise default to a return statement that returns an empty string
            $custom_message_text = $text ?? "return '';";

        //  If the text does not use the PHP Code Editor
        } else {
            
            //  Get the text otherwise default to an empty string
            $custom_message_text = $text ?? '';

        }

        //  Remove the (\u00a0) special character which represents a no-break space in HTML
        $text = $this->remove_HTML_No_Break_Space($text);

        //  Get all instances of mustache tags within the given text
        $result = $this->getInstancesOfMustacheTags($text);

        //  Get the total number of mustache tags found within the given text
        $number_of_mustache_tags = $result['total'];

        //  Get the mustache tags found within the given text
        $mustache_tags = $result['mustache_tags'];

        if ($uses_code_editor_mode == true) {
            //  Set an info log for the total number of dynamic data found in the PHP Code Editor text
            $this->logInfo('Found ('.$number_of_mustache_tags.') dynamic content references within the PHP Code Editor');
        } else {
            //  Set an info log for the total number of dynamic data found in the text
            $this->logInfo('Found ('.$number_of_mustache_tags.') dynamic content references within the text: <span class="text-success">'.$text.'</span>');
        }

        //  If we managed to detect one or more mustache tags
        if ($number_of_mustache_tags) {
            //  Foreach mustache tag we must convert it into a php variable
            foreach ($mustache_tags as $mustache_tag) {
                //  Convert "{{ company.name }}" into "$company->name"
                $dynamic_variable = $this->convertMustacheTagIntoPHPVariable($mustache_tag, true);

                /*  If the current text is not using the PHP Code Editor Mode then this means that it does
                 *  not want to process complex code e.g if-else statements, foreach statements and php
                 *  methods such as trim(), strtolower(), ucwords() e.t.c In this case we can
                 *  immediately convert the dynamic variable into its corresponding value
                 */
                if (!$uses_code_editor_mode) {
                    //  Convert the dynamic property into its dynamic value e.g "$company->name" into "Company XYZ"
                    $outputResponse = $this->processPHPCode("return $dynamic_variable;");

                    //  If processing the PHP Code failed, return the failed response otherwise continue
                    if ($this->shouldDisplayScreen($outputResponse)) {
                        return $outputResponse;
                    }

                    //  Get the generated output
                    $output = $outputResponse;

                    //  Incase the dynamic value is not a string, integer or float
                    if (!is_string($output) && !is_integer($output) && !is_float($output)) {
                        $dataType = ucwords(gettype($output));

                        //  Get the result type e.g Object, Array, Boolean e.t.c and wrap in square brackets
                        $output = json_encode( $output );
                    }

                    //  Set an info log that we are converting the dynamic property to its associated value
                    $this->logInfo('Converting <span class="text-success">'.$mustache_tag.'</span> to <span class="text-success">'.$output.'</span>');

                    //  Replace the mustache tag with its dynamic data e.g replace "{{ company.name }}" with "Company XYZ"
                    $text = preg_replace("/$mustache_tag/", $output, $text);
                } else {
                    //  Replace the mustache tag with its dynamic variable e.g replace "{{ company.name }}" with "$company->name"
                    $text = preg_replace("/$mustache_tag/", $dynamic_variable, $text);
                }
            }
        }

        /*  If the current text is using the PHP Code Editor Mode then render the code
         *  and process if-else statements, foreach statements and php methods such as
         *  trim(), strtolower(), ucwords() e.t.c
         */
        if ($uses_code_editor_mode) {
            //  Set an info log that we are processing the PHP Code from the PHP Code Editor
            $this->logInfo('Process PHP Code from the Code Editor');

            //  Remove the PHP tags from the PHP Code
            $text = $this->removePHPTags($text);

            //  Process the PHP Code
            $outputResponse = $this->processPHPCode("$text");

            //  If processing the PHP Code failed, return the failed response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) {
                return $outputResponse;
            }

            //  Get the generated output
            $text = $outputResponse;
        }

        if( is_string($text) ){

            //  Remove any HTML or PHP tags
            $text = strip_tags($text);

        }

        //  Return the converted text
        return $text;
    }

    public function remove_HTML_No_Break_Space($text = '')
    {
        return preg_replace('/\xc2\xa0/', '', $text);
    }

    public function getInstancesOfMustacheTags($text = '')
    {
        //  Remove the (\u00a0) special character which represents a no-break space in HTML
        $text = $this->remove_HTML_No_Break_Space($text);

        /** Detect Dynamic Variables
         *
         *  Pattern Meaning:.
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

    public function convertMustacheTagIntoPHPVariable($text = null, $add_sign = false)
    {
        //  If the text has been provided and is type of (String)
        if (!empty($text) && is_string($text)) {
            //  Remove the (\u00a0) special character which represents a no-break space in HTML
            $text = $this->remove_HTML_No_Break_Space($text);

            if( is_string($text) ){

                //  Remove any HTML or PHP tags
                $text = strip_tags($text);
    
            }

            //  Replace all curly braces and spaces with nothing e.g convert "{{ company.name }}" into "company.name"
            $text = preg_replace("/[{}\s]*/", '', $text);

            //  Replace one or more occurences of the period with "->" e.g convert "company.name" or "company..name" into "company->name"
            $text = preg_replace("/[\.]+/", '->', $text);

            //  Remove left and right spaces (If Any)
            $text = trim($text);

            //  If we should add the PHP "$" sign
            if ($add_sign == true) {
                //  Append the $ sign to the begining of the result e.g convert "company->name" into "$company->name"
                $text = '$'.$text;
            }

            //  Return the converted text
            return $text;
        }

        return null;
    }

    public function convertMustacheTagIntoDynamicData($mustache_tag)
    {
        //  Use the try/catch handles incase we run into any possible errors
        try {
            //  Set an info log that we are converting the mustache tag into dynamic data
            $this->logInfo('Start converting mustache tag <span class="text-success">'.$mustache_tag.'</span> into its associated dynamic data');

            //  Convert "{{ products }}" into "$products"
            $variable = $this->convertMustacheTagIntoPHPVariable($mustache_tag, true);

            //  Convert the dynamic property into its dynamic value e.g "$products" into "[ ['name' => 'Product 1', ...], ... ]"
            $outputResponse = $this->processPHPCode("return $variable;");

            //  If we have a screen to show return the response otherwise continue
            if ($this->shouldDisplayScreen($outputResponse)) {
                return $outputResponse;
            }

            //  Get the generated output and convert to a JSON Object
            $output = $this->convertToJsonObject($outputResponse);

            $dataType = ucwords(gettype($output));

            //  Set an info log for the final conversion result
            $this->logInfo('Converting <span class="text-success">'.$mustache_tag.'</span> to <span class="text-success">['.$dataType.']</span>');

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

    public function processPHPCode($code = 'return null', $log_dynamic_data = true)
    {
        //  Use the try/catch handles incase we run into any possible errors
        try {
            //  If we have dynamic data
            if (count($this->dynamic_data_storage)) {
                //  Set an info log that we are creating variables with dynamic data
                if ($log_dynamic_data) {
                    $this->logInfo('Creating variables using stored dynamic data');
                }

                //  Create dynamic variables
                foreach ($this->dynamic_data_storage as $key => $value) {
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
                     *
                     *  Convert the value to a JSON Object. Converting each value into an object helps us
                     *  target nested values by using the "->" symbol e.g we can access deeply nested
                     *  values in this way:
                     *
                     *  $company->details->contacts->phone;
                     *
                     */

                    ${$key} = $this->convertToJsonObject($value);

                    //  Set an info log for the created variable and its dynamic data value
                    if ($log_dynamic_data) {
                        $dataType = ucwords(gettype($value));

                        //  Use json_encode($output) to show $value data instead of gettype($value)
                        $this->logInfo('Variable <span class="text-success">$'.$key.'</span> = <span class="text-success">['.$dataType.']</span>');
                    }
                }
            }

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

    public function removePHPTags($text = '')
    {
        //  Remove PHP Tags
        $text = trim(preg_replace("/<\?php|\?>/i", '', $text));

        return $text;
    }

    public function storeDynamicData($name = null, $value = null, $log_status = true)
    {
        if (isset($name) && !empty($name)) {
            if (isset($this->dynamic_data_storage[$name])) {
                //  Set an warning log that we are overeiding existing data
                if ($log_status) {
                    $this->logWarning('Found existing data already stored within the reference name <span class="text-success">'.$name.'</span>, overiding the information.');
                }

                $dataType = ucwords(gettype($this->dynamic_data_storage[$name]));

                //  Set an info log of the old data stored
                if ($log_status) {
                    //  Use json_encode($option_value) to show $option_value data instead of gettype($option_value)
                    $this->logInfo('Old Data: <span class="text-success">['.$dataType.']</span>');
                }

                //  Add the value as additional dynamic data to our dynamic data storage
                $this->dynamic_data_storage[$name] = $value;

                //  Set an info log of the new data stored
                if ($log_status) {
                    $this->logInfo('New Data: <span class="text-success">['.$dataType.']</span>');
                }
            } else {
                //  Add the value as additional dynamic data to our dynamic data storage
                $this->dynamic_data_storage[$name] = $value;
            }
        }
    }

    public function generateUniqueVariable($reference_name = 'variable')
    {
        //  If the provided reference name if of type String
        if (is_string($reference_name)) {
            //  Generate a unique variable name
            $unique_variable_name = uniqid($reference_name.'_').'_'.random_int(1, 100000);

            //  Add the unique variable name to our generated variables array
            $this->generated_variables[$reference_name] = $unique_variable_name;

            //  Return the unique variable name
            return $unique_variable_name;
        }
    }

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

    public function isValidMustacheTag($text = null, $log_warning = true)
    {
        //  If we have the data to verify
        if (!empty($text)) {
            //  If the data to verify is of type String
            if (is_string($text)) {
                //  Remove the (\u00a0) special character which represents a no-break space in HTML
                $text = $this->remove_HTML_No_Break_Space($text);

                /** Detect Dynamic Variables
                 *
                 *  Pattern Meaning:.
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
                 */
                $pattern = "/[{]{2}[\s]*[a-zA-Z_]{1}[a-zA-Z0-9_\.]{0,}[\s]*[}]{2}/";

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
                $this->logWarning('The provided mustache tag is not a valid mustache tag syntax. Instead we received a value of type ['.gettype($text).']');
            } else {
                $this->logWarning('The provided mustache tag '.$text.' is not a valid mustache tag syntax');
            }
        }

        //  Return false to indicate that this is not a valid mustache tag
        return false;
    }

    /** handleTryCatchError()
     *  This method is used to handle errors caught during
     *  try-catch screnerios. It logs the error, indicates
     *  that an error occured and returns null.
     */
    public function handleTryCatchError($error, $load_display = true)
    {
        //  Set an error log
        $this->logError('Error:  '.$error->getMessage());

        if ($load_display) {
            //  Display the technical difficulties error screen to notify the user of the issue
            return $this->showTechnicalDifficultiesErrorScreen();
        }
    }

    /*******************************************
    /*******************************************
     * LOGGING FUNCTIONS                       *
     *******************************************
     ******************************************/

    /** logInfo()
     *  This method is used to log information about the USSD
     *  application build process.
     */
    public function logInfo($description = '')
    {
        $data = [
            'type' => 'info',
            'description' => $description,
            'level' => $this->level ?? null,
            'screen' => $this->screen['name'] ?? null,
            'datetime' => (\Carbon\Carbon::now())->format('Y-m-d H:i:s'),
        ];

        $this->updateLog($data);
    }

    /** logWarning()
     *  This method is used to log warnings about the USSD
     *  application build process.
     */
    public function logWarning($description = '')
    {
        $data = [
            'type' => 'warning',
            'description' => $description,
            'level' => $this->level ?? null,
            'screen' => $this->screen['name'] ?? null,
        ];

        $this->updateLog($data);
    }

    /** logError()
     *  This method is used to log errors about the USSD
     *  application build process.
     */
    public function logError($description = '')
    {
        $data = [
            'type' => 'error',
            'description' => $description,
            'level' => $this->level ?? null,
            'screen' => $this->screen['name'] ?? null,
        ];

        $this->updateLog($data);
    }

    public function updateLog($data)
    {
        //  Get the last recorded log microtime
        if (empty($this->last_recorded_log_microtime)) {
            $this->last_recorded_log_microtime = $this->getMicroTime();
        }

        //  Calculate the current log time since the last recorded log time
        $microtime_since_last_log = ($this->getMicroTime() - $this->last_recorded_log_microtime) / 1000;

        //  Update our log data stack
        array_push($data, ['microtime_since_last_log', $microtime_since_last_log]);

        //  Push the latest log update
        array_push($this->log, $data);
    }

    public function getMicroTime()
    {
        return microtime(true);
    }

    /*******************************************
    /*******************************************
     * DISPLAY FUNCTIONS                       *
     *******************************************
     ******************************************/

    /*  showCustomScreen()
     *  This is the screen displayed when we want to still continue the session.
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

    /*  showCustomGoBackScreen()
     *  This is the screen displayed when a problem was encountered and but we want
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

    /*  showCustomErrorScreen()
     *  This is the screen displayed when a problem was encountered and we want
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

    public function showTechnicalDifficultiesErrorScreen()
    {
        $response = $this->showCustomErrorScreen('Sorry, we are experiencing technical difficulties');

        return $response;
    }

    /*  showTimeoutScreen()
     *  This is the screen displayed when the USSD session times out
     */
    public function showTimeoutScreen($timeout_message)
    {
        return 'TIM ' . $timeout_message;   
    }

    /*  showRedirectScreen()
     *  This is the screen displayed when we want to rdirect the current
     *  session to another USSD Service Code
     */
    public function showRedirectScreen($service_code)
    {
        return 'RED ' . $service_code;   
    }

    /*******************************************
    /*******************************************
     * BASIC USSD FUNCTIONS                    *
     *******************************************
     ******************************************/

    public function getUserResponses($text = null)
    {
        /*  The text variable represent the response from the user.
         *  To extract the users information we must explode the text
         *  to retrieve the users information concatenated using the *
         *  symbol over several interations.
         *
         *  $user_responses[0] = Response from screen 1 (Landing Page)
         *  $user_responses[1] = Response from screen 2
         *  e.t.c
         */

        $responses = explode('*', $text ?? $this->text);

        /*  Remove empty keys  */
        $responses = array_filter($responses, function ($value) {
            return !is_null($value) && $value !== '';
        });

        return array_values($responses);
    }

    public function countUserResponses()
    {
        return count($this->getUserResponses() ?? []);
    }

    public function getResponseFromLevel($levelNumber = null)
    {
        if ($levelNumber) {
            /*  Get all the user reponses.  */
            $user_responses = $this->getUserResponses();

            /* We want to say if we have levelNumber = 1 we should get the landing screen data
             *  (since thats level 1) but technically $user_responses[0] = landing screen response.
             *  This means to get the response for the level we want we must decrement by one unit.
             *
             *  Use urldecode() to convert all encoded values to their
             *  decoded counterparts e.g
             *
             *  "%23" is an encoded value representing "#"
             */

            return isset($user_responses[$levelNumber - 1]) ? urldecode($user_responses[$levelNumber - 1]) : null;
        }
    }

    public function completedLevel($levelNumber = null)
    {
        /*  If we have a level number  */
        if ($levelNumber) {
            /*  Check if we have a response for this level number  */
            $level = $this->getResponseFromLevel($levelNumber);

            /*  If the level specified is completed (Has a response from the user)  */
            return isset($level) && $level != '';
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
         *  1*001*002*003*0*0*0
         *
         *  We can explode it into an array of responses to get
         *
         *  ["1", "001", "002", "003", "0", "0", "0"]
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
         *  ["1", "001", "002", "003", "0", "0", "0"]
         *
         *  At this point our application can count the number of times the zero (0)
         *  value appears which is 2 times in the above example. This means we need
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
         *  ["1", "001", "002", "0", "0"]
         *
         *  Once we locate that zero value and remove it along with the previous
         *  value, we need to update a special array called $updated_responses
         *  with the new updated responses. After the first loop we have:
         *
         *  $updated_responses Before = ["1", "001", "002", "003", "0", "0", "0"]
         *  $updated_responses After  = ["1", "001", "002", "0", "0"]
         *
         *  On the second loop we have
         *
         *  $updated_responses Before = ["1", "001", "002", "0", "0"]
         *  $updated_responses After  = ["1", "001", "0"]
         *
         *  $updated_responses Before = ["1", "001", "0"]
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
}
