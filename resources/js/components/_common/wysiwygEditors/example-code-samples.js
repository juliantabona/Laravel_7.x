function ussd_service_instructions_sample_code()
{

    return `<?php

    /** Use this editor to write custom PHP code. Always use the return statement
     *	when you want to output your information. Below is an example: 
     */

    $variable = "dynamic";

    return 'Example using ' . $variable . ' screen information';

?>`    

};

function ussd_service_custom_formatting_sample_code()
{

    return `<?php

    /** Use this editor to write custom php code to format the users input. Remember 
     *	that the users input must be referenced using the $input variable. Always use 
     *	the return statement when you want to output your information. 
     *	Below is an example: 
     */

    // Make the users input lowercase using the PHP method strtolower()
    return strtolower( $input );

?>`
};

function ussd_service_select_options_action_sample_code()
{

    return `<?php

    /** Use this editor to write custom php code to create dynamic options.  
     *	Remember that you can reference dynamic content using mustache tags 
     *  such as {{ products }} or PHP variables $products. Always use the
     *	return statement when you want to output your information. 
     *	Below is an example: 
     */

    //  Our products (Usually dynamic information e.g From an API call)
    $products = [
        ["id"=>"1", "name"=>"Product 1"],
        ["id"=>"2", "name"=>"Product 2"],
        ["id"=>"3", "name"=>"Product 3"]
    ];

    // We will store all our options inside an empty array called "options"
    $options = [];

    // Let items be an array of the dynamic information we want to list e.g "products"
    $items = $products;  // $items = {{ products }};

    /** Foreach item we will build the option. Each option must have four
     *  main attributes:
     * 
     *  @name: The option display name
     *  @value: The option value that must be stored as dynamic data
     *  @link: The id of the screen or display to link to after option selection
     *  @input: A number, letter or symbol the user must input to select the option
     *
     *	---	Optional Parameters ---
     *  @separator: [
     *     @top: Characters to add above to separate the option from other options e.g '---'
     *     @bottom: Characters to add below to separate the option from other options e.g '---'
     *   ]
     *
     */
    foreach($items as $key => $item){

        //  Every option must have the following structure
        $option = [
            'input' => ++$key,
            'value' => $item['id'],
            'name' => $item['name'],
            'link' => 'screen_1592486781723',   //  or "display_1592486852675"
            'separator' => [
                'top' => '',
                'bottom' => ''
            ]
        ];

        //  Add each option to our options list
        array_push($options, $option);

    }

    //  Return all our options
    return $options;

?>`
};

function ussd_service_local_storage_sample_code()
{

    return `<?php

    /** Use this editor to write custom php code to store static and dynamic
     *  data on Local Storage. Remember that you can reference dynamic content 
     *  using mustache tags such as {{ user.name }} or by using PHP variables 
     *  $user->name. Always use the return statement when you want to output 
     *  your information.
     */

    /** ARRAY STORAGE 
     * 
     *  If you are using the Code Editor with the Local Storage "Mode" set to 
     *  "Array Replace", "Array Append" or "Array Prepend" then you must 
     *  always return an array for proper storage. Below is an example: 
     */

    $first_name = 'John';
    $last_name = 'Doe';
    $age = '24';

    /** Example 1:
     * 
     *  We want to store the data provided by the user into an array as values
     */
    return [$first_name, $last_name, $age]; //  or [{{ first_name }}, {{ last_name }}, {{ age }}];


    /** Example 2:
     *
     *  We want to store the data provided by the user into an array as key/values
     */
    return [
        'first_name' => $first_name, 
        'last_name' => $last_name, 
        'age' => $age
    ]; 

    /** STRING STORAGE 
     * 
     *  If you are using the Code Editor with the Local Storage Mode set to
     *  "String Replace" or "String Join (Concatenate)" then you must always
     *  return a string for proper storage. A good use of Local Storage using
     *  strings is creating templated information. Below is an example: 
     */

    /** Example 1:
     * 
     *  We want to store the data provided by the user into an array as values
     */
     return 'Hello, ' .$first_name. ' welcome back;

?>`
};

function ussd_service_revisit_sample_code()
{

    return `<?php

    /** Use this editor to write custom php code to add additional responses
     *  that should be run immediately after revisiting a particular screen.
     *  This can be helpful to automate certain behaviour on behalf of the
     *  end user. 
     * 
     *  Remember that you can reference dynamic content using mustache tags 
     *  such as {{ user.name }} or by using PHP variables $user->name. 
     *  Return the responses in order, separated with the * symbol.
     *  
     */

    /** Example 1 (without dynamic content):
     * 
     *  The following example means that after we get to the desired screen,
     *  we should enter "1", then "2" and then finally "3".
     */
    return '1*2*3';

    /** Example 2 (with dynamic content):
     * 
     *  The following example means that after we get to the desired screen,
     *  we should enter "1", then "{{order.number}}" and then "3" and finally
     *  "{{order.amount}}". Notice that {{order.number}} and {{order.amount}}
     *  are dynamic properties that will convert into their appropriate values
     *  e.g {{order.number}} = 00223 and {{order.amount}} = 450.00
     */
    return '1*{{order.number}}*3*{{order.amount}}';

?>`
};

function ussd_service_global_variable_custom_code_sample()
{

    return `<?php

    /** Use this editor to write custom php code to set customized
     *  data that should be made available to all screens and displays.
     *  This can be helpful for setting configurations, settings or 
     *  custom static variables. 
     */

    /** Example 1: Returning (String):
     * 
     *  The following example will return a String.
     */
    return 'Welcome back :)';

    /** Example 2: Returning (Integer):
     * 
     *  The following example will return a Number.
     */
    return 25;

    /** Example 3: Returning (Boolean):
     * 
     *  The following example will return a True/False Boolean.
     */
    return (1 == 1) ? true : false;

    /** Example 4: Returning (Array):
     * 
     *  The following example will return a simple Array.
     */
    return ['Alan', 'Bob', 'Candice'];

    /** Example 4: Returning (Associative Array):
     * 
     *  The following example will return an associative Array.
     */
    return [
        ['name' => 'Alan', 'age' => 25],
        ['name' => 'Bob', 'age' => 26],
        ['name' => 'Candice', 'age' => 27]
    ];

    /** Example 5: Returning (Object):
     * 
     *  The following example will return an Object.
     */
    return new Date();

    /** Example 6: Returning (String) with dynamic content:
     * 
     *  The following example means that after we have set other 
     *  global variables, we can gain access to them using mustache 
     *  tags. Note that this only works if the variables called 
     *  have already been initialized before this variable. We 
     *  can use mustache tags or normal PHP variable naming 
     *  conventions to access dynamic data properties:
     */
    return 'Welcome to ' . {{ company_name }};

    //  or

    return 'Welcome to ' . $company_name;

?>`
};

function ussd_service_screen_or_display_link_sample_code()
{

    return `<?php

    /** Use this editor to write custom php code to 
     *	conditionally determine the next screen or
     *  display to link to. For this to work, you 
     *  must return a (String) of the Screen ID 
     *	or Display ID. Note that you cannot link 
     *	to the current screen. You also cannot 
     *	link to displays outside this current 
     *	screen. The ID's given must represent 
     *	existing screens or displays.
     */

    /** Example 1: Link to (Screen):
     * 
     *  The following example will allow us to link
     *  to a screen
     */
    return 'screen_1592486781723';

    /** Example 2: Link to (Display):
     * 
     *  The following example will allow us to link
     *  to a display on this current screen
     */
    return 'display_1592486852675';

    /** Example 3: Conditional Linking:
     * 
     *  The following example will conditionally
     *  determine which result to link to.
     */
    if( 1 == 1 ){
        return 'screen_1592486781723';
    }else{
        return 'display_1592486852675';
    }

?>`

};

function ussd_service_select_option_display_name_sample_code()
{

    return `<?php

    /** Use this editor to write custom php code to output
     *  the options display name
     */

    $total_messages = 15;

    /** Example 1: Simple Output
     * 
     *  The following example will allow us to output a
     *  simple display name
     */
    return '1. My Messages';

    /** Example 2: Dynamic Output
     * 
     *  The following example will allow us to output a
     *  display name with dynamic information
     */
    return '1. My Messages ({{ total_messages }})';

    //  or

    return '1. My Messages (' . $total_messages . ')';
    
    /** Example 3: Conditional Output
     * 
     *  The following example will conditionally output a
     *  display name with dynamic information
     */
    if( $total_messages > 10 ){
        return '1. My Messages (10+)';
    }else{
        return '1. My Messages ({{ total_messages }})';
    }

?>`
};

function ussd_service_dynamic_select_option_display_name_sample_code()
{

    return `<?php

    /** Use this editor to write custom php code to output
     *  the dynamic options display name. Assuming that we
     *  used "item" as our reference name, that is Foreach
     *  "{{ items }}" As "item", then we can reference the
     *  "item" here an access its propertices.
     * 
     *  Note that the properties must exist on the "item" 
     *  that we want to access. Lets assume that each 
     *  item has the following structure:
     * 
     *  item = [
     *      'id' => 1,
     *      'name' => 'Product 1',
     *      'price' => '30.00',
     *      'quantity' => '10'
     *  ]
     */

    /** Example 1: Output the item name
     * 
     *  The following example will allow us to output the
     *  name of each item as we loop
     */
    return {{ item.name }};

    //  or

    return $item->name;

    /** Example 2: Output item name and price
     * 
     *  The following example will allow us to output the
     *  name and price of each item as we loop
     */
    return {{ item.name }} {{ item.price }};

    //  or

    return $item->name .' '. $item->price;
    
    /** Example 3: Conditional Output
     * 
     *  The following example will conditionally output the
     *  name, price and quantity of each item as we loop
     */
    if( $item->quantity > 10 ){
        //  This will output: Product 1 - 30.00 (10 left)
        return '{{ item.name }} - {{ item.price }} ({{ item->quantity }} left)';
    }else{
        //  This will output: Product 1 - 30.00 (low stock)
        return '{{ item.name }} - {{ item.price }} (low stock)';
    }

?>`
};

function ussd_service_dynamic_select_option_value_sample_code()
{

    return `<?php

    /** Use this editor to write custom php code to output
     *  the dynamic options value. Assuming that we used
     *  "item" as our reference name, that is Foreach
     *  "{{ items }}" As "item", then we can 
     *  reference the "item" here an access 
     *  its propertices. 
     * 
     *  Note that the properties must exist on the "item" 
     *  that we want to access. Lets assume that each 
     *  item has the following structure:
     * 
     *  item = [
     *      'id' => 1,
     *      'name' => 'Product 1',
     *      'price' => '30.00',
     *      'quantity' => '10'
     *  ]
     */

    /** Example 1: Simple (String/Number) Output
     * 
     *  The following example will allow us to output the
     *  item id as the value
     */
    return '{{ item.id }}';

    //  or

    return $item->id;

    /** Example 2: Simple (Array) Output
     * 
     *  The following example will allow us to output the
     *  entire item as the value
     */
    return {{ item }};

    //  or

    return $item->id;

    /** Example 3: Associative (Array) Output
     * 
     *  The following example will allow us to output the
     *  entire item and items as the value
     */
    return [
        [ 'item' => {{ item }} ],
        [ 'items' => {{ items }} ]
    ];

    //  or

    return [
        [ 'item' => $item ],
        [ 'items' => $items ]
    ];

?>`
};

function ussd_service_select_option_value_sample_code()
{

    return `<?php

    /** Use this editor to write custom php code to output
     *  the options value
     */

    /** Example 1: Simple (String) Output
     * 
     *  The following example will allow us to output a
     *  simple (String) as the value
     */
    return 'messages';

    /** Example 2: Simple (Array) Output
     * 
     *  The following example will allow us to output a
     *  simple (Array) as the value
     */
    return ['Message 1', 'Message 2', 'Message 3'];

    /** Example 3: Associative (Array) Output
     * 
     *  The following example will allow us to output
     *  an associative (Array) as the value
     */
    return [
        [ 'msg' => 'Message 1', 'from' => 'Stacey'],
        [ 'msg' => 'Message 2', 'from' => 'John'],
        [ 'msg' => 'Message 3', 'from' => 'Mark'],
    ];

?>`
};

function ussd_service_select_option_input_sample_code()
{

    return `<?php

    /** Use this editor to write custom php code to determine
     *  the input characters required to select the option.
     */

    /** Example 1: Using (String)
     * 
     *  The following example will allow us to select the
     *  option when the number 3 is provided
     */
    return '3';

    /** Example 2: Using (Integer)
     * 
     *  The following example will allow us to select the
     *  option when the number 3 is provided. Same exact
     *  result as Example 1
     */
    return 3;

    /** Example 3: Using Symbols
     * 
     *  The following example will allow us to select the
     *  option when the user inputs symbolic characters
     */
    return '#';
    
    //  or

    return '*';

    //  or

    return '+';

    /** Example 4: Using Regex
     * 
     *  The following example will allow us to select the
     *  option when the user inputs anything that matches
     *  the regex pattern. Note that this must be a valid
     *  regex expression.
     */
    return '/[a-zA-Z0-9]*/';

?>`
};

function ussd_service_select_option_top_separator_sample_code()
{

    return `<?php

    /** Use this editor to write custom php code to determine
     *  the options top separator. A separator is used to create
     *  a separation between the current option and the content
     *  above.
     */
    
    return '---';

?>`
};

function ussd_service_select_option_bottom_separator_sample_code()
{

    return `<?php

    /** Use this editor to write custom php code to determine
     *  the options bottom separator. A separator is used to 
     *  create a separation between the current option and 
     *  the content below.
     */
    
    return '---';

?>`
};

function ussd_service_select_option_no_options_found_msg_sample_code()
{

    return `<?php

    /** Use this editor to write custom php code to output
     *  information to the user if no options are available.
     */

    $first_name = 'Joe';

    /** Example 1: Simple Output
     * 
     *  The following example will allow us to output a
     *  simple message if we don\'t have any options
     */
    return 'Sorry, we don\'t have any options today.';

    /** Example 2: Dynamic Output
     * 
     *  The following example will allow us to output a
     *  message with dynamic information
     */
    return 'Sorry {{ first_name }}, we don\'t have any options today.';

    //  or

    return 'Sorry ' . $first_name . ', we don\'t have any options today.';
    
    /** Example 3: Conditional Output
     * 
     *  The following example will conditionally output a
     *  message with dynamic information
     */
    if( 1 == 1 ){
        return 'Hey {{ first_name }}, we don\'t have any options today.';
    }else{
        return 'Hey buddy, we don\'t have any options today.';
    }

?>`
};

function ussd_service_select_option_incorrect_option_selected_msg_sample_code()
{

    return `<?php

    /** Use this editor to write custom php code to output
     *  information to the user if they selected an
     *  incorrect option.
     */

    $first_name = 'Joe';

    /** Example 1: Simple Output
     * 
     *  The following example will allow us to output a
     *  simple message
     */
    return 'Sorry, you selected an incorrect option.';

    /** Example 2: Dynamic Output
     * 
     *  The following example will allow us to output a
     *  message with dynamic information
     */
    return 'Sorry {{ first_name }}, you selected an incorrect option.';

    //  or

    return 'Sorry ' . $first_name . ', you selected an incorrect option.';
    
    /** Example 3: Conditional Output
     * 
     *  The following example will conditionally output a
     *  message with dynamic information
     */
    if( 1 == 1 ){
        return 'Hey {{ first_name }}, you selected an incorrect option.';
    }else{
        return 'Hey buddy, you selected an incorrect option.';
    }

?>`
};

export default {
    'ussd_service_instructions_sample_code': ussd_service_instructions_sample_code(),
    'ussd_service_custom_formatting_sample_code': ussd_service_custom_formatting_sample_code(),
    'ussd_service_select_options_action_sample_code': ussd_service_select_options_action_sample_code(),
    'ussd_service_local_storage_sample_code': ussd_service_local_storage_sample_code(),
    'ussd_service_revisit_sample_code': ussd_service_revisit_sample_code(),
    'ussd_service_global_variable_custom_code_sample': ussd_service_global_variable_custom_code_sample(),
    'ussd_service_screen_or_display_link_sample_code': ussd_service_screen_or_display_link_sample_code(),
    'ussd_service_select_option_no_options_found_msg_sample_code': ussd_service_select_option_no_options_found_msg_sample_code(),
    'ussd_service_select_option_incorrect_option_selected_msg_sample_code': ussd_service_select_option_incorrect_option_selected_msg_sample_code(),
    'ussd_service_select_option_display_name_sample_code': ussd_service_select_option_display_name_sample_code(),

    'ussd_service_dynamic_select_option_display_name_sample_code': ussd_service_dynamic_select_option_display_name_sample_code(),
    'ussd_service_dynamic_select_option_value_sample_code': ussd_service_dynamic_select_option_value_sample_code(),

    'ussd_service_select_option_value_sample_code': ussd_service_select_option_value_sample_code(),
    'ussd_service_select_option_input_sample_code': ussd_service_select_option_input_sample_code(),
    'ussd_service_select_option_top_separator_sample_code': ussd_service_select_option_top_separator_sample_code(),
    'ussd_service_select_option_bottom_separator_sample_code': ussd_service_select_option_bottom_separator_sample_code(),


    
}

