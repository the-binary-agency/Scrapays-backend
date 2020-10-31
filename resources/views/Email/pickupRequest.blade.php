@component('mail::message')
# Notification

A pickup request has been issued by <span class="font-weight-bold text-capitalize"> {{ $user->first_name. ' ' .$user->last_name}}. </span> <br>

<span class="font-weight-bold">Address:</span> {{ $user->address }} <br>
<span class="font-weight-bold">Pickup Schedule:</span> {{ $pickup->schedule }} <br>
<span class="font-weight-bold">Material Categories:</span>
<ul>
    @foreach (json_decode($pickup->materials) as $material)
        <li>{{ $material }}</li>
    @endforeach
</ul>

 <span class="font-weight-bold">Pickup ID:</span> {{ $pickup->id }} <br>

@component('mail::button', ['url' => 'https://app.scrapays.com/dashboard/pickup-request/'.$pickup->id])
View Request
@endcomponent

<style>
    .font-weight-bold{
        font-weight: bold;
    }
    .text-capitalize{
        text-transform: capitalize;
    }
</style>

© Copyright {{ config('app.name') .' '. now()->year }}. All rights reserved.
@endcomponent
