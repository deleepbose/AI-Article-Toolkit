<?php
/** @var string $basePath */
$base = $basePath ?? '/';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Article Toolkit</title>
    <meta name="description" content="Newsroom-focused AI toolkit: headlines, summary, SEO meta, readability scoring.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars($base, ENT_QUOTES) ?>assets/css/app.css" rel="stylesheet">
</head>
<body>
<a class="skip-link" href="#main">Skip to main content</a>

<header class="border-bottom">
    <div class="container py-3 d-flex justify-content-between align-items-center">
        <h1 class="h4 m-0">AI Article Toolkit</h1>
        <span class="text-muted small" id="mode-indicator" aria-live="polite"></span>
    </div>
</header>

<main id="main" class="container py-4">
    <section aria-labelledby="input-heading">
        <h2 id="input-heading" class="h5">Paste an article</h2>
        <p class="text-muted">The toolkit returns headline suggestions, a short summary, an SEO meta description, a readability score, and an estimated reading time.</p>

        <form id="analyze-form" novalidate>
            <div class="mb-3">
                <label for="article" class="form-label">Article text</label>
                <textarea
                    class="form-control"
                    id="article"
                    name="article"
                    rows="12"
                    required
                    aria-describedby="article-help"
                    placeholder="Paste the full article text here..."></textarea>
                <div id="article-help" class="form-text">Up to 50,000 characters. Plain text only.</div>
            </div>
            <button type="submit" class="btn btn-primary" id="analyze-btn">Analyze article</button>
            <button type="button" class="btn btn-outline-secondary" id="sample-btn">Load sample text</button>
        </form>
    </section>

    <section aria-labelledby="results-heading" class="mt-5" id="results-section" hidden>
        <h2 id="results-heading" class="h5">Results</h2>
        <div id="results" aria-live="polite" aria-busy="false"></div>
    </section>

    <section aria-labelledby="history-heading" class="mt-5">
        <h2 id="history-heading" class="h5">Recent analyses</h2>
        <button type="button" class="btn btn-link p-0" id="refresh-history">Refresh history</button>
        <div id="history" class="mt-2"></div>
    </section>
</main>

<footer class="border-top mt-5">
    <div class="container py-3 small text-muted">
        Built by Deleep Bose. PHP 8.2, OpenAI, MySQL. <a href="https://github.com/deleepbose/ai-article-toolkit" rel="noopener">Source on GitHub</a>.
    </div>
</footer>

<script>
    window.APP_BASE_PATH = <?= json_encode($base, JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="<?= htmlspecialchars($base, ENT_QUOTES) ?>assets/js/app.js"></script>
</body>
</html>
