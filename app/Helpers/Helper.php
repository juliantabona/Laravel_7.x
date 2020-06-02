<?php


    //  Handle unauthenticated requests
    function help_unauthenticated()
    {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    //  Handle unauthorized requests
    function help_not_authorized()
    {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    //  Handle model not found requests
    function help_model_not_fonud()
    {
        return response()->json(['message' => 'Model not found'], 404);
    }

    //  Handle model relationships not found requests
    function help_model_relationship_not_fonud()
    {
        return response()->json(['message' => 'Model relationship not found'], 404);
    }

    //  Handle resource not found requests
    function help_resource_not_fonud()
    {
        return response()->json(['message' => 'Resource not found'], 404);
    }

    //  Handle route not found requests
    function help_route_not_fonud()
    {
        return response()->json(['message' => 'Route not found. Make sure you are using the correct url'], 404);
    }

    //  Handle too many login attempts requests
    function help_to_many_login_attempts()
    {
        return oq_api_notify_error('Too many login attempts. Please wait some time and try again', null, 404);
    }
    
    //  Handle method not allowed requests
    function help_method_not_allowed()
    {
        return response()->json(['message' => 'Method not allowed'], 405);
    }

    