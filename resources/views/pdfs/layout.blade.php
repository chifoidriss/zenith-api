<!DOCTYPE html>
<html lang="fr">
<head>
    <title>@yield('title') - {{ env('APP_NAME') }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link rel="stylesheet" href="{{ asset('assets/pdfs/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/pdfs/bootstrap-reboot.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/pdfs/bootstrap-utilities.min.css') }}">

    <style>
        .bg {
            background-position: center;
            background-repeat: no-repeat;
            background-size: contain;
            /* background-image: url("{{ asset('icon.png') }}"); */
            opacity: 0.05;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }
        .content {
            position: relative;
            z-index: 1;
            /* background-color: #FFF; */
        }
        .table.table-borderless th, .table.table-borderless td {
            padding: 5.5px 0 !important;
        }
    </style>
</head>

<body>
    <div class="bg"></div>

    <div class="container-fluid px-0- content">
        @yield('content')
    </div>
</body>
</html>
