<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
     <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <title>Upload Location Data</title>
</head>
<body>

<div class="row mt-4">
    <div class="col-6 offset-md-3">
        @if (count($errors) > 0)
        @foreach ($errors->all() as $error)
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <strong>{{ $error }}</strong> 
            </div>
        @endforeach
    @endif

    @if (session('success'))
        <div class="alert alert-success" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <strong>{{ session('success') }}</strong>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <strong>{{ session('error') }}</strong>
        </div>
    @endif
    </div>
</div>

    
<form action="{{ url('/uplooadlocationdata') }}" method="post" enctype="multipart/form-data">

    {{ csrf_field() }}

    <div class="row mt-5">
        <div class="col-6 offset-md-3">
            <div class="form-group">
                <label for="">Location Data</label>
                <input type="file" class="form-control-file" name="locationData" id="locationData" placeholder="">
            </div>

            <div class="form-group text-right">
                <button type="submit" class="btn btn-primary">Upload</button>
            </div>
        </div>
    </div>

</form>

<!-- Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>