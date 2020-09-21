@component('mail::message')

{{ $body->message }}

© Copyright {{ config('app.name') .' '. now()->year }}. All rights reserved.
@endcomponent
