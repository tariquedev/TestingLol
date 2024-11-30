<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Connect to Zoom</title>
</head>
<body>
    <div class="container">
        <h1>Connect to Zoom</h1>
        <p>To connect your Zoom account, click the button below:</p>
        @if ($id_connected == true)
        <a href="{{ route('zoom.disconnect') }}" class="btn btn-primary">Disconnect Now</a>
        @else
        <a href="{{ $authUrl }}" class="btn btn-primary">Connect Now</a>
        @endif
    </div>
</body>
</html>
