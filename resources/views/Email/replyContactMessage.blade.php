@component('mail::message')

{{ $reply->message }}

© Copyright {{ config('app.name') .' '. now()->year }}. All rights reserved.
@endcomponent
