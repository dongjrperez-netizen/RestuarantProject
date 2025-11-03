@props(['url'])
<tr>
  <td class="header">
    <a href="{{ $url }}" style="display: inline-block;">
      <img src="{{ config('app.url') . '/Logo.png' }}" class="logo" alt="{{ config('app.name') }} Logo">

    </a>
  </td>
</tr>

<!-- @props(['url']) 
<tr> 
    <td class="header"> 
        <a href="{{ $url }}" style="display: inline-block;"> @if (trim($slot) === 'Laravel') 
            <img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo"> @else {!! $slot !!} @endif 
        </a> 
    </td> 
</tr> -->
