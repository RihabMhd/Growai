<x-mail::message>
# Password Reset Request

You are receiving this email because we received a password reset request for your account.

<x-mail::button :url="config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')) . '/reset-password?token=' . $token">
Reset Password
</x-mail::button>

If you did not request a password reset, no further action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
