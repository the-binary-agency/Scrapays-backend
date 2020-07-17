@component('mail::message')
# Successful Registration

Welcome to Scrapays for Household Mr./Mrs. {{ $user->lastName }}

We’re thrilled that you’ve decided to join us today and make us your recyclable waste offtake partner.

You can access Scrapays for Household Account Dashboard here:
<a href="https://app.scrapays.com/household/login">https://app.scrapays.com/household/login</a>

Your account login is:
<h3><span class="font-weight-bold">Phone Number:</span> {{ $user->phone }} </h3>
<h3><span class="font-weight-bold">Password:</span> {{ $user->password }} </h3>


Warm regards, 

Scrapays Team
<a href="www.scrapays.com">www.scrapays.com</a>

Have Question? 
Send a reply this mail and our customer service personnel will be with right away.

<style>
    .font-weight-bold{
        font-weight: bold;
    }
</style>
© Copyright {{ config('app.name') .' '. now()->year }}. All rights reserved.
@endcomponent
