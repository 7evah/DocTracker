@props(['url'])
{{--
    The app's sidebar wordmark, rebuilt for e-mail: a JESA badge beside the
    product name. Drawn with a table and inline styles rather than the flex
    layout the sidebar uses, because Outlook renders neither flexbox nor an
    external stylesheet (§44).
--}}
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<table cellpadding="0" cellspacing="0" role="presentation" style="border-collapse: collapse;">
<tr>
<td style="padding-right: 10px; vertical-align: middle;">
<span style="display: inline-block; background-color: #003a70; color: #ffffff; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 700; letter-spacing: 1px; padding: 8px 12px; border-radius: 6px;">JESA</span>
</td>
<td style="vertical-align: middle;">
<span style="color: #18181b; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 18px; font-weight: 600;">{{ $slot }}</span>
</td>
</tr>
</table>
</a>
</td>
</tr>
