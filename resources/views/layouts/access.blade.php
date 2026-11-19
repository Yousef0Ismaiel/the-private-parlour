<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#f5eee6">
    <title>@yield('title') · The Parlour</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="/assets/entrance.css">
    <script src="/assets/forms.js" defer></script>
</head>
<body>
<a class="skip" href="#entry">Skip to the form</a>
<header class="topline">
    <a class="wordmark" href="/login"><span class="monogram" aria-hidden="true">P</span> The Parlour</a>
    <span class="door-label">Private entrance</span>
</header>
<main class="entry-grid @yield('layout-class')">
    <div class="key-panel" aria-hidden="true">
        <div class="key-frame">
            <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
            <div class="engraving-orbit"></div>
            <img class="antique-key" src="/assets/chamber-key.webp" alt="" width="572" height="1050">
        </div>
    </div>
    <section class="entry-body" id="entry" aria-labelledby="entry-title">
        <div class="entry-inner">
            @yield('progress')
            <h1 id="entry-title">@yield('heading')</h1>
            <p class="intro">@yield('intro')</p>
            @if(session('notice'))
                <p class="notice" role="status">{{ session('notice') }}</p>
            @endif
            @if($errors->any())
                <div class="form-error" role="alert">
                    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif
            @yield('form')
        </div>
    </section>
</main>
</body>
</html>
