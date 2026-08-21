<style>
{!! \App\Services\BrandingService::css() !!}
</style>

@php($favicon = \App\Services\BrandingService::faviconUrl())

<link rel="icon" href="{{ $favicon ?? '/images/favicon.ico' }}" sizes="any">
@if (! $favicon)
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png">
@endif
<meta name="theme-color" content="{{ \App\Services\BrandingService::brandPrimary() }}">
