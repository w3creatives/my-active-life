<x-mail::message>

Hello {{ $user->first_name }}! Need to reset your password? No problem. Just use below one time verification code to get started.

<x-mail::header url="">
Recovering your Run The Edge password
</x-mail::header>
<x-mail::panel>


Your verification code is : {{ $user->reset_password_token }}
</x-mail::panel>

</x-mail::message>

