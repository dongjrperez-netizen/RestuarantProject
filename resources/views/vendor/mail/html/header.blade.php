@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="{{ config('app.url') }}/Logo.png" class="logo" alt="ServeWise Logo">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
