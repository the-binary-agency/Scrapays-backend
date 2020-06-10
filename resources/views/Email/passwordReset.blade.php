@component('mail::message')
# Password Change Request

A password change was requested on your account with us. <br>
Click on the button below to change your password.

@component('mail::button', ['url' => 'http://scrapays.com/response-password-reset?token='.$token ])
Reset Password
@endcomponent

Thanks,<br> 
{{ config('app.name') }}
@endcomponent
