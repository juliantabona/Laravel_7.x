<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'OQ SCE') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!--  Import HTML5 mockups of popular devices
        - https://marvelapp.github.io/devices.css
        - https://github.com/pixelsign/html5-device-mockups
    -->
    <link href="{{ asset('css/devices.min.css') }}" rel="stylesheet">

    <!-- Import Vue-highlgihtjs Styles 
        - For displaying code samples with color (e.g PHP Code) on HTML 
        - https://highlightjs.org/
    -->
    <link href="{{ asset('css/highlight.min.css') }}" rel="stylesheet">

    <!-- Custom styles -->
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    
</head>
<body>
    <div id="app">
        <!--
            #   Render the Vue Application Here
        -->
    </div>
</body>
</html>
