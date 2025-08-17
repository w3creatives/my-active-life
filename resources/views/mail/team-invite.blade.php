<x-mail::message>
# You're Invited to Join {{ $team->name }}!

Hi {{ $invitedUser->first_name }},

{{ $inviter->display_name ?? $inviter->first_name . ' ' . $inviter->last_name }} has invited you to join their team **{{ $team->name }}** for the {{ $team->event->name ?? 'challenge' }}.

## About the Team
- **Team Name**: {{ $team->name }}
- **Event**: {{ $team->event->name ?? 'Current Challenge' }}
- **Invited by**: {{ $inviter->display_name ?? $inviter->first_name . ' ' . $inviter->last_name }}

## What happens next?
When you accept this invitation, you'll automatically become a member of the team and can start contributing to your team's goals together.

<x-mail::button :url="$acceptUrl" color="primary">
Accept Invitation
</x-mail::button>

<x-mail::button :url="$declineUrl" color="secondary">
Decline Invitation
</x-mail::button>

## Need help?
If you have any questions about this invitation or need assistance, please don't hesitate to reach out to us at support@runtheedge.com.

Thanks,<br>
{{ config('app.name') }}

---
*This invitation will expire in 30 days. If you don't respond, it will be automatically cancelled.*
</x-mail::message>
