<!doctype html>
<html>
<head>

    <title>Demo</title>
    <script>
        // rename myToken as you like
        window.Laravel =  "{{ json_encode([
            'csrfToken' => csrf_token(),
        ])}}";
    </script>
    <!-- Fonts -->
    <link rel="stylesheet" href="/css/app.css">


    <!-- Styles -->
    <style>
        html, body {
            background-color: #fff;
            color: #636b6f;
            font-family: 'Raleway', sans-serif;
            font-weight: 100;
            height: 100vh;
            margin: 0;
        }

        .links > a {
            color: #636b6f;
            padding: 0 25px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .1rem;
            text-decoration: none;
            text-transform: uppercase;
        }

        .m-b-md {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>

<div id="app" >
    <app-header></app-header>
    <div class="container">
        <app-body token="{{csrf_token()}}"></app-body>
    </div>
</div>
<script src="/js/app.js"></script>
</body>
</html>
