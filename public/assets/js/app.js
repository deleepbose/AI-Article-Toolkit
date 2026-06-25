/**
 * AI Article Toolkit - frontend wiring.
 * Vanilla JS, no framework. Uses Fetch with JSON request/response.
 */

(function () {
    'use strict';

    const form = document.getElementById('analyze-form');
    const articleInput = document.getElementById('article');
    const analyzeBtn = document.getElementById('analyze-btn');
    const sampleBtn = document.getElementById('sample-btn');
    const resultsSection = document.getElementById('results-section');
    const resultsEl = document.getElementById('results');
    const historyEl = document.getElementById('history');
    const refreshHistoryBtn = document.getElementById('refresh-history');
    const modeIndicator = document.getElementById('mode-indicator');

    const SAMPLE_TEXT = "Wellington council has approved a new pedestrian zone in the central business district after months of public consultation. The change removes general traffic from a four-block stretch of Lambton Quay between 7am and 7pm. Local retailers gave mixed feedback, with some saying foot traffic will rise and others worried about delivery access. Council officers say a delivery window will run between 5am and 7am to address that concern. Construction begins in the spring and the new zone is expected to open before the end of the year.";

    sampleBtn.addEventListener('click', function () {
        articleInput.value = SAMPLE_TEXT;
        articleInput.focus();
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        runAnalysis();
    });

    refreshHistoryBtn.addEventListener('click', loadHistory);

    function runAnalysis() {
        const article = articleInput.value.trim();
        if (article === '') {
            renderError('Please paste an article first.');
            return;
        }

        analyzeBtn.disabled = true;
        analyzeBtn.textContent = 'Analyzing...';
        resultsSection.hidden = false;
        resultsEl.setAttribute('aria-busy', 'true');
        resultsEl.innerHTML = '<p>Working on it...</p>';

        fetch('/api/analyze', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ article: article })
        })
            .then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (body) {
                        throw new Error(body.error || ('Request failed: ' + response.status));
                    });
                }
                return response.json();
            })
            .then(renderResults)
            .catch(function (err) {
                renderError(err.message || 'Something went wrong.');
            })
            .finally(function () {
                analyzeBtn.disabled = false;
                analyzeBtn.textContent = 'Analyze article';
                resultsEl.setAttribute('aria-busy', 'false');
                loadHistory();
            });
    }

    function renderResults(data) {
        if (data.demo_mode) {
            modeIndicator.textContent = 'Demo mode (no OpenAI key set)';
        } else {
            modeIndicator.textContent = 'Live AI mode';
        }

        const headlinesHtml = (data.headlines || []).map(function (h) {
            return '<li>' + escapeHtml(h) + '</li>';
        }).join('');

        const grade = data.readability.flesch_kincaid_grade.toFixed(1);

        resultsEl.innerHTML =
            '<div class="result-card">' +
                '<h3>Headline suggestions</h3>' +
                '<ol class="headline-list">' + headlinesHtml + '</ol>' +
            '</div>' +
            '<div class="result-card">' +
                '<h3>Summary</h3>' +
                '<p>' + escapeHtml(data.summary) + '</p>' +
            '</div>' +
            '<div class="result-card">' +
                '<h3>SEO meta description</h3>' +
                '<p>' + escapeHtml(data.meta_description) + '</p>' +
                '<p class="text-muted small mb-0">Length: ' + data.meta_description.length + ' characters</p>' +
            '</div>' +
            '<div class="result-card">' +
                '<h3>Readability and reading time</h3>' +
                '<p>Flesch-Kincaid grade: <strong>' + grade + '</strong> ' +
                    '<span class="readability-band">' + escapeHtml(data.readability.band) + '</span></p>' +
                '<p class="text-muted">' + escapeHtml(data.readability.wcag_note) + '</p>' +
                '<p>Estimated reading time: ' + data.reading_time_minutes + ' min (' + data.word_count + ' words)</p>' +
            '</div>';
    }

    function loadHistory() {
        fetch('/api/history?limit=10')
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('History not available.');
                }
                return response.json();
            })
            .then(function (data) {
                const items = data.items || [];
                if (items.length === 0) {
                    historyEl.innerHTML = '<p class="text-muted">No prior analyses yet.</p>';
                    return;
                }
                historyEl.innerHTML = items.map(function (item) {
                    const grade = parseFloat(item.flesch_kincaid_grade).toFixed(1);
                    const demoBadge = item.demo_mode ? ' <span class="demo-pill">demo</span>' : '';
                    return '<div class="history-item">' +
                        '<strong>#' + item.id + '</strong> ' +
                        escapeHtml(item.created_at) + demoBadge + '<br>' +
                        '<span class="history-excerpt">' + escapeHtml(item.article_excerpt) + '</span><br>' +
                        '<span class="text-muted small">' +
                            'FK ' + grade + ' (' + escapeHtml(item.readability_band) + '), ' +
                            item.reading_time_minutes + ' min read, ' + item.word_count + ' words' +
                        '</span>' +
                    '</div>';
                }).join('');
            })
            .catch(function () {
                historyEl.innerHTML = '<p class="text-muted">History unavailable. Database may not be reachable.</p>';
            });
    }

    function renderError(message) {
        resultsSection.hidden = false;
        resultsEl.innerHTML = '<div class="error-box" role="alert">' + escapeHtml(message) + '</div>';
    }

    function escapeHtml(value) {
        if (value === null || value === undefined) {
            return '';
        }
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    loadHistory();
}());
