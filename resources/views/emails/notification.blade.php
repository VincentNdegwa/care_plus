<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
</head>
<body>
    <h2>{{ $title }}</h2>
    <p>{{ $body }}</p>
    
    {{-- @if(!empty($data))
    <div class="additional-data">
        @foreach($data as $key => $value)
            <p><strong>{{ $key }}:</strong> {{ $value }}</p>
        @endforeach
    </div>
    @endif --}}
</body>
</html>
