<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #1f2937; margin: 0; padding: 24px; background: #f9fafb;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 32px;">
        <img src="{{ asset('images/branding/logo-horizontal.png') }}" alt="{{ $orgName ?? config('app.name') }}" style="height: 28px; width: auto; margin-bottom: 24px;">
        {{-- $bodyHtml mixes admin-authored template markup with substituted
             variables that can include donor-supplied strings (donor_name,
             etc. — see App\Services\EmailTemplates\EmailTemplateRenderer,
             which does not HTML-escape substitutions). Sanitized here as
             the single choke point, rather than trying to escape at every
             variable source. --}}
        {!! str($bodyHtml)->sanitizeHtml() !!}
        <p style="font-size: 12px; color: #6b7280; margin-top: 24px;">{{ $orgName ?? config('app.name') }}</p>
        @if (! empty($unsubscribeUrl))
            <p style="font-size: 11px; color: #9ca3af;">
                <a href="{{ $unsubscribeUrl }}" style="color: #9ca3af;">Unsubscribe</a> from non-transactional emails.
            </p>
        @endif
    </div>
</body>
</html>
