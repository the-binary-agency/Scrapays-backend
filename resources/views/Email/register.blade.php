@component('mail::message')
# Successful Registration

Thank you for creating an account with us. Your login details are as follows;

<h3><span class="font-weight-bold">Phone Number:</span> {{ $user->phone }} </h3>
<h3><span class="font-weight-bold">Password:</span> {{ $user->password }} </h3>

<style>
    .font-weight-bold{
        font-weight: bold;
    }
</style>

Thanks,<br>
© Copyright {{ config('app.name') .' '. now()->year }}. All rights reserved.
@endcomponent
