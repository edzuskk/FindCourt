<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FindCourt</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        #map {
            width: 100%;
            height: 100vh;
        }
    </style>
</head>
<body>
    <x-navigation />
    {{ $slot }}

</body>
</html>