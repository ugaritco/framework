@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Ugarit')
<img src="https://ugarit.com/img/notification-logo-v2.1.png" class="logo" alt="Ugarit Logo">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
