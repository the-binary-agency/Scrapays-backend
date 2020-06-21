@component('mail::message')
# Successful Registration

@if ($user->type == 'enterprise')
    Welcome to Scrapays for Enterprise {{ $user->companyName }}

    We’re thrilled that you’ve decided to join us today and make us your recyclable waste offtake partner.

    You can access Scrapays for Enterprise Account Dashboard here:
    <a href="https://app.scrapays.com/enterprise/login">https://app.scrapays.com/enterprise/login</a>

    Your account login is:
    <h3><span class="font-weight-bold">Email:</span> {{ $user->email }} </h3>
    <h3><span class="font-weight-bold">Password:</span> {{ $user->password }} </h3>
 
    Get started and request for your first paid Recyclable Waste Pickup today! 

    Now here are a few features you can access from your Dashboard 
    1. Request a pickup : Decide when and what time you want Scrapays to come pick your items. Our collector will he with you to value your materials on site and you get the price paid into your Dashboard wallet. 
    2. List your Bulky item: Getting rid of old equipment, warehouse/ factory clearance, out of use item just got easier with the "SELL NOW" option in your Dashboard. Upload images, describe the item(s), you get an inspection, get prices and get paid.
    3. Barter: You can decide to use the funds in your Scrapays Wallet to directly purchase utilities or supplies directly from our partners and get them delivered. 
    4. Fulfill your CSR: Donate to SDG causes you are passionate about with returns from your waste. From 

    Our goal is to help effectively manage your recyclable waste in the most stress free way, so you can focus on what truly matters to you and your organisation. 

    Over 1000 businesses trust our fluid technology managed recovery operations process, plus it comes at zero cost to you. 
@endif

@if ($user->type == 'household')
    Welcome to Scrapays for Household Mr./Mrs. {{ $user->lastName }}

    We’re thrilled that you’ve decided to join us today and make us your recyclable waste offtake partner.

    You can access Scrapays for Household Account Dashboard here:
    <a href="https://app.scrapays.com/household/login">https://app.scrapays.com/household/login</a>

    Your account login is:
    <h3><span class="font-weight-bold">Phone Number:</span> {{ $user->phone }} </h3>
    <h3><span class="font-weight-bold">Password:</span> {{ $user->password }} </h3>
@endif

{{-- @if ($user->type == 'household')
    Welcome to Scrapays for Household Mr./Mrs. {{ $user->lastName }}

    We’re thrilled that you’ve decided to join us today and make us your recyclable waste offtake partner.

    You can access Scrapays for Household Account Dashboard here:
    <a href="https://app.scrapays.com/household/login">https://app.scrapays.com/household/login</a>

    Your account login is:
    <h3><span class="font-weight-bold">Email:</span> {{ $user->email }} </h3>
    <h3><span class="font-weight-bold">Password:</span> {{ $user->password }} </h3>

    <h3><span class="font-weight-bold">Phone Number:</span> {{ $user->phone }} </h3>
    <h3><span class="font-weight-bold">Password:</span> {{ $user->password }} </h3>
@endif --}}



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
