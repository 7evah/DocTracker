<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer: the same prototype notice the sidebar carries, so a message
     forwarded outside the app still says what it came from. --}}
<x-slot:footer>
<x-mail::footer>
{{ __('notifications.mail.footer', ['year' => date('Y')]) }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
