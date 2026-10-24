<?php
// landing.php
// Public front page: every visit to the bare site address lands here (index.php sends it).
// Explains what the system does for the three field offices and leads to Sign in, or to the
// dashboard for staff who are already signed in. The only data it shows are province totals
// from models/PublicStats.php: aggregates, never names or records.
session_start();
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'models/PublicStats.php';

$auth = new Auth();
$signedIn = false;
try {
    $signedIn = $auth->isLoggedIn();
} catch (Throwable $e) {
    // The page still works without a database; Sign in will report the problem.
}
$actionHref  = $signedIn ? 'index.php' : 'login.php';
$actionLabel = $signedIn ? 'Open dashboard' : 'Sign in';

// =========================================================
// Province totals (server-rendered so the page works without JavaScript)
// =========================================================
$stats = null;
try {
    $stats = (new PublicStats())->getTotals();
} catch (Throwable $e) {
    error_log('landing stats: ' . $e->getMessage());
}

$provinceKeys = [
    'all'               => 'All provinces',
    'Negros Occidental' => 'Negros Occidental',
    'Negros Oriental'   => 'Negros Oriental',
    'Siquijor'          => 'Siquijor',
];

$figureSets = [];
foreach ($provinceKeys as $key => $label) {
    $row = $stats === null ? null : ($key === 'all' ? $stats['all'] : ($stats['provinces'][$key] ?? null));
    $millions = $row ? $row['amount_released'] / 1000000 : 0;
    $figureSets[$key] = [
        'label'    => $label,
        'amount'   => $row ? number_format($millions, 2, '.', ',') : '–',
        'people'   => $row ? number_format($row['people_served']) : '–',
        'groups'   => $row ? number_format($row['group_projects']) : '–',
        'grantees' => $row ? number_format($row['individual_grantees']) : '–',
    ];
}
$initial = $figureSets['all'];
[$amountWhole, $amountDecimals] = array_pad(explode('.', $initial['amount'], 2), 2, '');

// =========================================================
// Liquidation deadline example (same rule as Proponent::calculateLiquidationDeadline)
// =========================================================
$tz        = new DateTimeZone('Asia/Manila');
$today     = new DateTimeImmutable('today', $tz);
$turnover  = $today->modify('-4 days');
$examples  = ['today', $today->modify('-30 days')->format('j F Y'), $today->modify('-75 days')->format('j F Y')];

function landingDeadlineRow(DateTimeImmutable $turnover, DateTimeImmutable $today, int $days): array {
    $due  = $turnover->modify('+' . $days . ' days');
    $left = (int) $today->diff($due)->format('%r%a');
    if ($left > 1)       $when = $left . ' days left';
    elseif ($left === 1) $when = 'Due tomorrow';
    elseif ($left === 0) $when = 'Due today';
    else                 $when = 'Overdue by ' . abs($left) . ' day' . (abs($left) === 1 ? '' : 's');
    return ['due' => $due->format('l, j F Y'), 'when' => $when, 'late' => $left < 0];
}
$lguRow   = landingDeadlineRow($turnover, $today, 10);
$otherRow = landingDeadlineRow($turnover, $today, 60);

$year = $today->format('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DILEEP Monitoring System · DOLE Negros Island Region</title>
    <meta name="description" content="The DOLE system that encodes and tracks DILEEP livelihood grants for Negros Occidental, Negros Oriental and Siquijor.">
    <link rel="icon" href="assets/dileep-logo.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="preload" as="image" href="assets/landing/hero-sugarcane-kanlaon.jpg">
    <script>document.documentElement.classList.add('js');</script>
    <style>
        /* =========================================================
           Tokens: DOLE green and gold, kept from the app (includes/shared-styles.php)
           ========================================================= */
        :root {
            --bg: #f4f6f2;
            --surface: #e4ebe2;
            --paper: #ffffff;
            --ink: #14231a;
            --muted: #4a5a4f;
            --green: #1b7a3d;
            --green-deep: #145a2c;
            --green-night: #0e3b1e;
            --gold: #d4a017;
            --gold-light: #f0b429;
            --accent: #d4a017;
            --accent-strong: #e3ad1c;

            --font: 'Inter', 'Segoe UI', system-ui, sans-serif;

            /* five sizes */
            --step-small: 0.9375rem;
            --step-body: clamp(1.0625rem, 1rem + 0.2vw, 1.1875rem);
            --step-lead: clamp(1.1875rem, 1rem + 0.6vw, 1.5rem);
            --step-head: clamp(2.125rem, 1.1rem + 3.6vw, 4.75rem);
            --step-display: clamp(2rem, 8.4vw, 7rem);

            --margin: clamp(1rem, 2.2vw, 2rem);
            --gutter: clamp(1rem, 2vw, 2rem);
            --section: clamp(6rem, 12vw, 12rem);

            --ease-out: cubic-bezier(0.22, 1, 0.36, 1);
            --ease-expo: cubic-bezier(0.16, 1, 0.3, 1);
            --ease-in-out: cubic-bezier(0.76, 0, 0.24, 1);
            --ease-soft: cubic-bezier(0.33, 1, 0.68, 1);
            --t-press: 140ms;
            --t-hover: 180ms;
            --t-ui: 240ms;
            --stagger-item: 80ms;
        }

        /* =========================================================
           Reset and type
           ========================================================= */
        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; text-size-adjust: 100%; }
        body {
            margin: 0; background: var(--bg); color: var(--ink);
            font-family: var(--font); font-size: var(--step-body); line-height: 1.55;
            -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility;
            font-feature-settings: 'cv11', 'ss01';
        }
        h1, h2, h3, p, figure, ul, ol, dl, dd { margin: 0; }
        ul, ol { padding: 0; list-style: none; }
        img { display: block; max-width: 100%; height: auto; }
        a { color: inherit; }
        button, input { font: inherit; color: inherit; }
        button { background: none; border: 0; padding: 0; cursor: pointer; }
        h1, h2, h3 { font-weight: 700; letter-spacing: -0.035em; line-height: 0.98; text-wrap: balance; }
        p { text-wrap: pretty; }
        :focus-visible { outline: 3px solid var(--gold); outline-offset: 3px; }
        ::selection { background: var(--gold-light); color: var(--ink); }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; }
        .num { font-variant-numeric: tabular-nums lining-nums; }

        .grid { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); column-gap: var(--gutter); padding-inline: var(--margin); }

        .button {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.5em;
            min-height: 48px; padding: 0 1.4em; border-radius: 0;
            font-size: var(--step-small); font-weight: 600; text-decoration: none; letter-spacing: -0.005em;
            transition: transform var(--t-press) var(--ease-out), background-color var(--t-hover) ease, color var(--t-hover) ease;
        }
        .button:active { transform: scale(0.97); }
        .button--paper { background: var(--paper); color: var(--ink); }
        .button--gold { background: var(--gold); color: var(--ink); }
        .link { text-decoration: underline; text-decoration-color: transparent; text-underline-offset: 0.22em; text-decoration-thickness: 0.08em; transition: text-decoration-color var(--t-hover) ease, opacity var(--t-hover) ease; }
        @media (hover: hover) and (pointer: fine) {
            .button--paper:hover { background: var(--gold-light); }
            .button--gold:hover { background: var(--accent-strong); }
            .link:hover { text-decoration-color: currentColor; }
        }

        /* =========================================================
           Hero: title in the sky, line on the horizon
           ========================================================= */
        .hero { position: relative; min-height: 100svh; color: var(--paper); overflow: hidden; isolation: isolate; background: #3d6ea8; }
        .hero__media { position: absolute; inset: 0; z-index: -2; overflow: hidden; }
        .hero__media img { width: 100%; height: 100%; object-fit: cover; object-position: 50% 6%; }
        .hero__shade { position: absolute; inset: 0; z-index: -1; background: rgba(8, 24, 14, 0.22); }

        .bar { position: absolute; inset: 0 0 auto 0; display: flex; align-items: center; gap: var(--gutter); padding: 28px var(--margin); }
        .wordmark { display: inline-flex; align-items: center; gap: 12px; font-weight: 700; font-size: 1.125rem; letter-spacing: -0.02em; text-decoration: none; margin-right: auto; }
        .wordmark img { width: 40px; height: 40px; background: var(--paper); border-radius: 50%; padding: 3px; }
        .bar__links { display: flex; gap: clamp(1rem, 2.4vw, 2.25rem); font-size: var(--step-small); font-weight: 600; }
        .bar__links a { text-decoration: none; }

        .hero__title {
            position: absolute; left: var(--margin); right: var(--margin); top: max(120px, 17svh);
            text-align: center; font-size: var(--step-display); line-height: 0.95; letter-spacing: -0.045em;
        }
        .hero__title .line { display: block; }

        /* the status badge from the app, set inline at the height of a line */
        .chip {
            display: inline-flex; align-items: center; gap: 0.22em; vertical-align: 0.06em;
            width: 2.75em; height: 0.78em; padding: 0 0.26em; margin-right: 0.1em;
            border-radius: 0.39em; background: var(--surface); color: var(--ink);
            font-size: 1em; letter-spacing: -0.02em; overflow: hidden;
            transition: background-color 420ms var(--ease-out), color 420ms var(--ease-out);
        }
        .chip__dot { flex: none; width: 0.16em; height: 0.16em; border-radius: 50%; background: currentColor; }
        .chip__text { font-size: 0.3em; font-weight: 600; line-height: 1; white-space: nowrap; letter-spacing: -0.01em; }
        .chip[data-status="approved"]    { background: var(--gold-light); color: var(--ink); }
        .chip[data-status="implemented"] { background: var(--gold); color: var(--ink); }
        .chip[data-status="liquidated"]  { background: var(--green); color: var(--paper); }

        .hero__line {
            position: absolute; left: var(--margin); right: var(--margin); top: 54%;
            display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); column-gap: var(--gutter);
            font-size: var(--step-lead); font-weight: 600; line-height: 1.3; letter-spacing: -0.01em;
        }
        .hero__line span:first-child { grid-column: 1 / 6; text-align: right; }
        .hero__line span:last-child { grid-column: 8 / 13; }
        .hero__cta { display: none; }

        /* =========================================================
           Shared section frame
           ========================================================= */
        .section { padding-block: var(--section) 0; }
        .h2 { font-size: var(--step-head); }

        /* =========================================================
           One record, five stages
           ========================================================= */
        .stages__head { grid-column: 1 / -1; margin-bottom: 64px; }
        .stages__bar, .stages__notes { grid-column: 1 / -1; display: flex; gap: 6px; }
        .stages__bar { height: 22svh; min-height: 160px; }
        .stage { position: relative; flex: var(--grow) 1 0; min-width: 0; padding: 20px; overflow: hidden; }
        .stage__name { display: block; font-size: var(--step-lead); font-weight: 700; letter-spacing: -0.02em; line-height: 1.1; }
        .stage__span { display: block; margin-top: 8px; font-size: var(--step-small); font-weight: 600; }
        .stage--pending     { background: var(--surface); }
        .stage--approved    { background: var(--gold-light); }
        .stage--implemented { background: var(--gold); }
        .stage--liquidated  { background: var(--green); }
        .stage--monitored   { background: var(--green-deep); }
        .stage--liquidated, .stage--monitored { color: var(--paper); }
        .stages__notes { margin-top: 20px; }
        .stages__notes p { flex: var(--grow) 1 0; min-width: 0; padding-right: 20px; font-size: var(--step-body); line-height: 1.45; max-width: none; }

        /* =========================================================
           Statements on colour fields
           ========================================================= */
        .fields { margin-top: var(--section); }
        .field { position: relative; min-height: 76svh; padding: 40px var(--margin) 28px; display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); grid-template-rows: auto 1fr auto; column-gap: var(--gutter); }
        .field p { grid-row: 1; grid-column: 8 / 12; font-size: var(--step-lead); line-height: 1.4; font-weight: 400; max-width: 32ch; }
        .field h2 { grid-row: 3; grid-column: 1 / 11; font-size: var(--step-display); line-height: 0.92; letter-spacing: -0.045em; }
        .field--mirror p { grid-column: 2 / 6; }
        .field--mirror h2 { grid-column: 3 / -1; text-align: right; }
        .field--green { background: var(--green); color: var(--paper); }
        .field--gold { background: var(--gold-light); color: var(--ink); }
        .field--night { background: var(--green-night); color: var(--paper); }
        .field strong { font-weight: 700; }

        /* =========================================================
           The deadline rule, typed
           ========================================================= */
        .calc { row-gap: 0; align-items: start; }
        .calc__main { grid-column: 1 / 9; }
        .calc__head { margin-bottom: 40px; }
        .calc__field { display: flex; align-items: baseline; }
        .calc__input {
            width: 100%; min-width: 0; padding: 0; border: 0; background: transparent; outline: none;
            font-size: var(--step-display); font-weight: 700; letter-spacing: -0.045em; line-height: 1.05;
            caret-color: var(--gold); color: var(--ink);
        }
        .calc__field:focus-within { box-shadow: 0 3px 0 0 var(--gold); }
        .calc__answer {
            margin-top: 40px; max-width: 620px; background: var(--paper); border-radius: 10px; padding: 8px 0;
            font-size: var(--step-small);
        }
        .calc__row { display: grid; grid-template-columns: 1fr auto; gap: 4px 20px; padding: 16px 22px; }
        .calc__row + .calc__row { background: #f6f8f5; }
        .calc__type { font-weight: 600; }
        .calc__rule { color: var(--muted); }
        .calc__due { grid-column: 1; font-size: 1.0625rem; font-weight: 700; letter-spacing: -0.01em; }
        .calc__when { grid-column: 2; grid-row: 1 / 3; align-self: center; padding: 6px 12px; border-radius: 999px; background: var(--surface); font-weight: 600; white-space: nowrap; }
        .calc__when.is-late { background: #f8d9d6; color: #7a1d12; }
        .calc__hint { padding: 16px 22px; color: var(--muted); }
        .calc__examples { margin-top: 28px; display: flex; flex-wrap: wrap; gap: 8px 24px; font-size: var(--step-body); }
        .calc__examples span { color: var(--muted); }
        .calc__examples button { font-weight: 600; text-decoration: underline; text-decoration-thickness: 0.08em; text-underline-offset: 0.22em; text-decoration-color: rgba(20, 35, 26, 0.35); transition: text-decoration-color var(--t-hover) ease; }
        .calc__examples button:hover { text-decoration-color: currentColor; }
        .calc__examples { visibility: hidden; }
        .js .calc__examples { visibility: visible; }
        .calc__figure { grid-column: 9 / -1; margin-top: 8px; }
        .calc__figure img { width: 100%; aspect-ratio: 4 / 5; object-fit: cover; }
        figcaption, .figcap { margin-top: 12px; font-size: var(--step-small); color: var(--muted); }

        /* =========================================================
           Provinces: the ledger
           ========================================================= */
        .ledger { align-items: start; }
        .ledger__side { grid-column: 1 / 6; grid-row: 1; }
        .ledger__intro { margin-top: 20px; max-width: 40ch; }
        .switch { margin-top: 32px; display: flex; flex-wrap: wrap; gap: 6px; }
        .switch button {
            min-height: 44px; padding: 0 16px; background: var(--surface); font-size: var(--step-small); font-weight: 600;
            transition: background-color var(--t-hover) ease, color var(--t-hover) ease, transform var(--t-press) var(--ease-out);
        }
        .switch button:active { transform: scale(0.97); }
        .switch button[aria-pressed="true"] { background: var(--green); color: var(--paper); }
        @media (hover: hover) and (pointer: fine) { .switch button[aria-pressed="false"]:hover { background: #d3ddd0; } }
        .places { grid-column: 1 / 6; grid-row: 2; margin-top: 24px; display: flex; gap: 6px; height: clamp(220px, 34svh, 360px); }
        .place { position: relative; flex: 1 1 0; min-width: 0; overflow: hidden; transition: flex-grow 700ms var(--ease-in-out); }
        .place img { width: 100%; height: 100%; object-fit: cover; }
        .places[data-show="Negros Occidental"] .place:not([data-place="Negros Occidental"]),
        .places[data-show="Negros Oriental"] .place:not([data-place="Negros Oriental"]),
        .places[data-show="Siquijor"] .place:not([data-place="Siquijor"]) { flex-grow: 0.18; }
        .places[data-show="Negros Occidental"] .place[data-place="Negros Occidental"],
        .places[data-show="Negros Oriental"] .place[data-place="Negros Oriental"],
        .places[data-show="Siquijor"] .place[data-place="Siquijor"] { flex-grow: 4; }
        .places__caption { grid-column: 1 / 6; grid-row: 3; }

        /* one grid for every row, so the whole numbers and decimals line up down the column */
        .figures { grid-column: 7 / -1; grid-row: 1 / 4; align-self: center; display: grid; grid-template-columns: max-content max-content minmax(0, 1fr); column-gap: 0; row-gap: 22px; }
        .figure { grid-column: 1 / -1; display: grid; grid-template-columns: subgrid; align-items: baseline; transition: opacity 260ms ease; }
        .figure__whole, .figure__dec { font-size: var(--step-display); font-weight: 700; letter-spacing: -0.04em; line-height: 1; font-variant-numeric: tabular-nums lining-nums; }
        .figure__whole { text-align: right; }
        .figure__dec { text-align: left; }
        .figure__note { font-size: var(--step-body); line-height: 1.35; padding-left: 20px; }
        .figures.is-swapping .figure { opacity: 0.25; }
        .figures__foot { margin-top: 8px; grid-column: 3; font-size: var(--step-small); color: var(--muted); }

        /* =========================================================
           Who can do what
           ========================================================= */
        .access__head { grid-column: 1 / -1; font-size: var(--step-head); }
        .access__head .indent { display: block; padding-left: 3vw; }
        .access__figure { grid-column: 1 / 5; align-self: end; margin-top: 96px; }
        .access__figure img { width: 100%; aspect-ratio: 4 / 5; object-fit: cover; object-position: 50% 0%; }
        .access__word { grid-column: 7; margin-top: 96px; font-size: var(--step-small); font-weight: 600; padding-top: 3px; }
        .access__tables { grid-column: 8 / -1; margin-top: 96px; }
        .access table { width: 100%; border-collapse: collapse; font-size: var(--step-body); }
        .access caption { text-align: left; font-size: var(--step-small); font-weight: 600; color: var(--muted); padding-bottom: 10px; }
        .access table + table { margin-top: 44px; }
        .access th, .access td { padding: 13px 0; vertical-align: baseline; text-align: left; font-weight: 400; }
        .access th { color: var(--muted); width: 32%; padding-right: 16px; }
        .access td { padding-right: 16px; }
        .access td:last-child { text-align: right; padding-right: 0; white-space: nowrap; font-weight: 600; }

        /* =========================================================
           Closing sentence with a blank
           ========================================================= */
        .close { padding-bottom: var(--section); }
        .close form { grid-column: 1 / 12; }
        .close__sentence { font-size: var(--step-head); font-weight: 700; letter-spacing: -0.04em; line-height: 1.15; }
        .close__sentence label { display: inline; }
        .close__input {
            display: inline-block; width: 7.2em; height: 1.05em; padding: 0 0.25em; vertical-align: -0.12em;
            border: 0; border-radius: 0; background: var(--surface); outline: none;
            font-size: 1em; font-weight: 700; letter-spacing: -0.04em; color: var(--ink);
        }
        .close__input::placeholder { color: #8a988e; }
        .close__input:focus { background: #d7e1d4; box-shadow: inset 0 -0.08em 0 var(--gold); }
        .close__submit {
            display: inline-block; height: 1.05em; padding: 0 0.3em; vertical-align: -0.12em;
            background: var(--gold); color: var(--ink); font-size: 1em; font-weight: 700; letter-spacing: -0.04em; line-height: 1.05;
            transition: background-color var(--t-hover) ease, transform var(--t-press) var(--ease-out);
        }
        .close__submit:active { transform: scale(0.97); }
        @media (hover: hover) and (pointer: fine) { .close__submit:hover { background: var(--accent-strong); } }
        .close__note { margin-top: 24px; max-width: 60ch; font-size: var(--step-body); }
        .close__note a { color: var(--ink); font-weight: 600; }

        /* =========================================================
           Footer: the way in as the headline
           ========================================================= */
        .foot { min-height: 92svh; background: var(--green-deep); color: var(--paper); padding: 40px var(--margin) 28px; display: flex; flex-direction: column; }
        .foot__top { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); column-gap: var(--gutter); font-size: var(--step-small); line-height: 1.5; }
        .foot__marks { grid-column: 1 / 4; display: flex; gap: 14px; align-items: center; }
        .foot__marks img { height: 52px; width: auto; }
        .foot__marks .dilp { background: var(--paper); border-radius: 50%; padding: 4px; width: 52px; }
        .foot__note { grid-column: span 3; }
        .foot__top .totop { grid-column: 12; justify-self: end; font-weight: 600; text-decoration: none; }
        .foot__main { margin-top: auto; padding-top: 18svh; }
        .foot__main h2 { font-size: var(--step-display); letter-spacing: -0.05em; }
        .foot__main h2 a { text-decoration: none; }
        .foot__main p { margin-top: 20px; font-size: var(--step-lead); max-width: 44ch; }
        .foot__meta { margin-top: 56px; display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 24px; font-size: var(--step-small); line-height: 1.5; }
        .foot__meta .credits a { text-decoration: underline; text-underline-offset: 0.18em; }
        .foot__meta > :last-child { text-align: right; }
        .foot__links { display: flex; flex-direction: column; gap: 4px; }

        /* =========================================================
           Motion (transform and opacity only; nothing hidden without the js class)
           ========================================================= */
        @media (prefers-reduced-motion: no-preference) {
            .js [data-reveal] { opacity: 0; transform: translateY(24px); transition: opacity 700ms var(--ease-out), transform 900ms var(--ease-expo); transition-delay: calc(var(--i, 0) * var(--stagger-item)); }
            .js [data-reveal].is-in { opacity: 1; transform: none; }

            .js .hero__media { clip-path: inset(0 0 100% 0); transition: clip-path 1100ms var(--ease-in-out); }
            .js .hero__media img { transform: scale(1.12); transition: transform 1600ms var(--ease-soft); }
            .js .hero.is-in .hero__media { clip-path: inset(0); }
            .js .hero.is-in .hero__media img { transform: none; }
            .js .hero__title .line, .js .hero__line span, .js .bar > * { opacity: 0; transform: translateY(18px); transition: opacity 700ms var(--ease-out), transform 900ms var(--ease-expo); }
            .js .hero.is-in .hero__title .line, .js .hero.is-in .hero__line span, .js .hero.is-in .bar > * { opacity: 1; transform: none; }
            .js .hero.is-in .hero__title .line:nth-child(1) { transition-delay: 500ms; }
            .js .hero.is-in .hero__title .line:nth-child(2) { transition-delay: 590ms; }
            .js .hero.is-in .hero__line span:nth-child(1) { transition-delay: 760ms; }
            .js .hero.is-in .hero__line span:nth-child(2) { transition-delay: 830ms; }
            .js .hero.is-in .bar > * { transition-delay: 300ms; }

            .js .stage { clip-path: inset(0 100% 0 0); transition: clip-path 900ms var(--ease-expo); transition-delay: calc(var(--i, 0) * 110ms); }
            .js .stages__bar.is-in .stage { clip-path: inset(0); }
            @media (max-width: 1024px) { .js .stages__bar:not(.is-in) .stage { clip-path: inset(0 0 100% 0); } }
            .js .stage__name, .js .stage__span { opacity: 0; transition: opacity 500ms ease; transition-delay: calc(var(--i, 0) * 110ms + 380ms); }
            .js .stages__bar.is-in .stage__name, .js .stages__bar.is-in .stage__span { opacity: 1; }

            .drift { animation: drift linear both; animation-timeline: view(); animation-range: entry 0% exit 100%; }
            @keyframes drift { from { transform: scale(1.08) translateY(-3%); } to { transform: scale(1.08) translateY(3%); } }
        }
        @supports not (animation-timeline: view()) { .drift { animation: none; } }

        /* =========================================================
           Tablet
           ========================================================= */
        @media (max-width: 1024px) {
            .calc__main { grid-column: 1 / -1; }
            .calc__figure { grid-column: 7 / -1; margin-top: 48px; }
            .ledger__side { grid-column: 1 / -1; }
            .figures { grid-column: 1 / -1; grid-row: 2; margin-top: 56px; }
            .places { grid-column: 1 / -1; grid-row: 3; margin-top: 56px; }
            .places__caption { grid-column: 1 / -1; grid-row: 4; }
            .access__word { display: none; }
            .access__tables { grid-column: 1 / -1; grid-row: 2; margin-top: 56px; }
            .access__figure { grid-column: 1 / 6; grid-row: 3; margin-top: 56px; }

            /* the bar turns vertical once its segments get too narrow for their names */
            .stages { display: block; }
            .stages__bar { flex-direction: column; height: auto; min-height: 0; }
            .stage { flex: none; min-height: calc(var(--grow) * 48px + 40px); }
            .stages__notes { display: none; }
            .stage__note { display: block; margin-top: 10px; font-size: var(--step-body); line-height: 1.45; max-width: 44ch; }

            .foot__marks { grid-column: 1 / -1; margin-bottom: 24px; }
            .foot__note { grid-column: span 5; }
            .field { min-height: 56svh; }
            .field p { grid-column: 7 / -1; }
            .field--mirror p { grid-column: 1 / 7; }
        }

        /* =========================================================
           Phone: its own composition
           ========================================================= */
        @media (max-width: 720px) {
            .bar { padding: 18px var(--margin); }
            .bar__links, .bar .button { display: none; }
            .hero__media img { object-position: 58% 60%; }
            .hero__title { top: 96px; }
            .hero__line { top: calc(120px + 2 * var(--step-display)); display: block; text-align: center; font-size: var(--step-body); }
            .hero__line span { display: block; }
            .hero__line span:first-child { text-align: center; }
            .hero__cta { display: flex; position: absolute; left: 0; right: 0; bottom: 0; padding: 24px var(--margin); background: rgba(8, 24, 14, 0.55); }
            .hero__cta .button { width: 100%; min-height: 52px; }

            .stages__head { margin-bottom: 32px; }

            .field { min-height: 56svh; padding-top: 28px; }
            .field p, .field--mirror p { grid-column: 1 / -1; font-size: var(--step-body); max-width: 30ch; }
            .field--mirror p { justify-self: end; text-align: right; }
            .field h2, .field--mirror h2 { grid-column: 1 / -1; text-align: left; }

            .calc__figure { grid-column: 4 / -1; }
            .calc__examples { gap: 8px; }
            .calc__examples span { width: 100%; }
            .calc__examples button { min-height: 44px; padding: 0 14px; background: var(--surface); text-decoration: none; }

            .figures { grid-template-columns: max-content minmax(0, 1fr); row-gap: 28px; }
            .figure { row-gap: 4px; }
            .figure__note { grid-column: 1 / -1; font-size: var(--step-small); padding-left: 0; }
            .figures__foot { grid-column: 1 / -1; }

            .access__head .indent { padding-left: 0; }
            .access__tables { grid-column: 1 / -1; margin-top: 48px; }
            .access th { width: auto; display: block; padding: 14px 0 0; }
            .access td { display: inline-block; padding: 2px 0 0; }
            .access tr { display: grid; grid-template-columns: 1fr auto; }
            .access th { grid-column: 1 / -1; }
            .access__figure { grid-column: 5 / -1; order: 3; margin-top: 48px; }

            .close__sentence { line-height: 1.3; }
            .close__input { display: block; width: 100%; height: 1.2em; margin: 0.15em 0; }

            .foot__top { display: block; }
            .foot__note { margin-top: 16px; }
            .foot__top .totop { display: inline-block; margin-top: 16px; }
            .foot__meta { grid-template-columns: 1fr; }
            .foot__meta > :last-child { text-align: left; }
        }
        @media (min-width: 1025px) { .stage__note { display: none; } }
    </style>
</head>
<body>

<!-- =========================================================
     Hero
     ========================================================= -->
<header class="hero" id="top">
    <div class="hero__media">
        <img src="assets/landing/hero-sugarcane-kanlaon.jpg" width="2400" height="1800"
             alt="Young sugarcane in La Carlota City, Negros Occidental, with Kanlaon Volcano on the horizon under a clear sky"
             fetchpriority="high">
    </div>
    <div class="hero__shade" aria-hidden="true"></div>

    <nav class="bar" aria-label="Main">
        <a class="wordmark" href="#top"><img src="assets/dilp-logo.png" width="40" height="40" alt="">DILEEPSYS</a>
        <div class="bar__links">
            <a class="link" href="#stages">How it works</a>
            <a class="link" href="#provinces">Provinces</a>
            <a class="link" href="#access">Access</a>
        </div>
        <a class="button button--paper" href="<?php echo $actionHref; ?>"><?php echo $actionLabel; ?></a>
    </nav>

    <h1 class="hero__title">
        <span class="line">Every DILEEP grant,</span>
        <span class="line"><span class="sr-only">from pending</span><span class="chip" data-ui data-status="pending" aria-hidden="true"><span class="chip__dot"></span><span class="chip__text">Pending</span></span> to monitored</span>
    </h1>

    <p class="hero__line">
        <span>The DOLE system that encodes and tracks livelihood grants</span>
        <span>for Negros Occidental, Negros Oriental and Siquijor.</span>
    </p>

    <div class="hero__cta"><a class="button button--paper" href="<?php echo $actionHref; ?>"><?php echo $actionLabel; ?></a></div>
</header>

<main>

<!-- =========================================================
     How it works: one record, five stages
     ========================================================= -->
<section class="section grid stages" id="stages" aria-labelledby="stages-title">
    <h2 class="h2 stages__head" id="stages-title" data-reveal>One record, five stages</h2>

    <div class="stages__bar" data-ui data-stagebar>
        <div class="stage stage--pending" style="--grow:1.25;--i:0">
            <span class="stage__name">Pending</span>
            <span class="stage__note">First the field office encodes the grant and links it to its proponent.</span>
        </div>
        <div class="stage stage--approved" style="--grow:1.25;--i:1">
            <span class="stage__name">Approved</span>
            <span class="stage__note">Then the approval date and the amount are set.</span>
        </div>
        <div class="stage stage--implemented" style="--grow:1.45;--i:2">
            <span class="stage__name">Implemented</span>
            <span class="stage__note">Next comes turn-over, with the cheque and OR details.</span>
        </div>
        <div class="stage stage--liquidated" style="--grow:2.9;--i:3">
            <span class="stage__name">Liquidated</span>
            <span class="stage__span">Within 10 or 60 days of turn-over</span>
            <span class="stage__note">After that, group projects liquidate before their deadline.</span>
        </div>
        <div class="stage stage--monitored" style="--grow:1.6;--i:4">
            <span class="stage__name">Monitored</span>
            <span class="stage__note">Last, a field visit confirms the livelihood is running.</span>
        </div>
    </div>
    <div class="stages__notes" aria-hidden="true">
        <p style="--grow:1.25" data-reveal>First the field office encodes the grant and links it to its proponent.</p>
        <p style="--grow:1.25;--i:1" data-reveal>Then the approval date and the amount are set.</p>
        <p style="--grow:1.45;--i:2" data-reveal>Next comes turn-over, with the cheque and OR details.</p>
        <p style="--grow:2.9;--i:3" data-reveal>After that, group projects liquidate before their deadline. Individual grants skip this step.</p>
        <p style="--grow:1.6;--i:4" data-reveal>Last, a field visit confirms the livelihood is running.</p>
    </div>
</section>

<!-- =========================================================
     Features: statements on colour fields
     ========================================================= -->
<div class="fields">
    <section class="field field--green" aria-labelledby="f1">
        <p data-reveal>Enter the turn-over date and the liquidation deadline sets itself: <strong>10 days</strong> for LGU-associated proponents, <strong>60 days</strong> for everyone else. Overdue projects rise to the top of the dashboard and the bell.</p>
        <h2 id="f1" data-reveal style="--i:1">Deadlines counted for you</h2>
    </section>
    <section class="field field--gold field--mirror" aria-labelledby="f2">
        <p data-reveal>Staff get an email when fieldwork is assigned or moved, the day before it and on the day. A missed visit is followed up after one, three and seven days.</p>
        <h2 id="f2" data-reveal style="--i:1">Reminders before every visit</h2>
    </section>
    <section class="field field--night" aria-labelledby="f3">
        <p data-reveal>NOCFO, NORFO and SFO each work only on their own province’s records. The regional office sees all three. A record’s province is set once and never changes.</p>
        <h2 id="f3" data-reveal style="--i:1">Each office sees its own</h2>
    </section>
</div>

<!-- =========================================================
     Showcase: the deadline rule you can type into
     ========================================================= -->
<section class="section grid calc" id="deadline" aria-labelledby="calc-title">
    <div class="calc__main">
        <h2 class="h2 calc__head" id="calc-title" data-reveal>When is liquidation due?</h2>
        <form class="calc__form" data-ui action="#deadline" onsubmit="return false" data-reveal style="--i:1">
            <label class="sr-only" for="turnover">Turn-over date</label>
            <div class="calc__field">
                <input class="calc__input" id="turnover" name="turnover" type="text" inputmode="text" autocomplete="off" spellcheck="false"
                       value="<?php echo htmlspecialchars($turnover->format('j F Y')); ?>" aria-describedby="calc-answer">
            </div>
        </form>
        <div class="calc__answer" id="calc-answer" data-ui aria-live="polite" data-reveal style="--i:2">
            <div class="calc__row">
                <span class="calc__type">LGU-associated</span>
                <span class="calc__due"><?php echo htmlspecialchars($lguRow['due']); ?></span>
                <span class="calc__when<?php echo $lguRow['late'] ? ' is-late' : ''; ?>"><?php echo htmlspecialchars($lguRow['when']); ?></span>
                <span class="calc__rule">Turn-over + 10 days</span>
            </div>
            <div class="calc__row">
                <span class="calc__type">Non-LGU, By Administration and Others</span>
                <span class="calc__due"><?php echo htmlspecialchars($otherRow['due']); ?></span>
                <span class="calc__when<?php echo $otherRow['late'] ? ' is-late' : ''; ?>"><?php echo htmlspecialchars($otherRow['when']); ?></span>
                <span class="calc__rule">Turn-over + 60 days</span>
            </div>
        </div>
        <div class="calc__examples" data-reveal style="--i:3">
            <span>Try a turn-over date:</span>
            <?php foreach ($examples as $example): ?>
                <button type="button" data-example="<?php echo htmlspecialchars($example); ?>"><?php echo htmlspecialchars($example); ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <figure class="calc__figure" data-reveal style="--i:2">
        <div style="overflow:hidden"><img class="drift" src="assets/landing/sari-sari-store.jpg" width="1024" height="768" loading="lazy" decoding="async"
             alt="A small neighbourhood sari-sari store with soft-drink and load signs above its window"></div>
        <figcaption>A sari-sari store, the kind of small business many livelihood grants set up.</figcaption>
    </figure>
</section>

<!-- =========================================================
     Stats: the three provinces, live
     ========================================================= -->
<section class="section grid ledger" id="provinces" aria-labelledby="prov-title">
    <div class="ledger__side">
        <h2 class="h2" id="prov-title" data-reveal>What each office has released</h2>
        <p class="ledger__intro" data-reveal style="--i:1">Live totals from the system for approved, implemented, liquidated and monitored grants. Choose a province.</p>
        <div class="switch" role="group" aria-label="Province" data-reveal style="--i:2">
            <?php foreach ($provinceKeys as $key => $label): ?>
                <button type="button" data-province="<?php echo htmlspecialchars($key); ?>" aria-pressed="<?php echo $key === 'all' ? 'true' : 'false'; ?>"><?php echo htmlspecialchars($label); ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="places" data-show="all" data-reveal style="--i:3">
        <div class="place" data-place="Negros Occidental">
            <img src="assets/landing/negros-occidental-capitol.jpg" width="1000" height="1500" loading="lazy" decoding="async" alt="Columns of the Provincial Capitol of Negros Occidental in Bacolod">
        </div>
        <div class="place" data-place="Negros Oriental">
            <img src="assets/landing/negros-oriental-talinis.jpg" width="1600" height="1062" loading="lazy" decoding="async" alt="Mount Talinis above coconut palms in Negros Oriental">
        </div>
        <div class="place" data-place="Siquijor">
            <img src="assets/landing/siquijor-coast.jpg" width="1600" height="1064" loading="lazy" decoding="async" alt="Turquoise shallows along the green coast of Siquijor">
        </div>
    </div>
    <p class="places__caption figcap" data-reveal style="--i:4">The Provincial Capitol in Bacolod, Mount Talinis in Negros Oriental and the coast of Siquijor.</p>

    <div class="figures" aria-live="polite" data-figures>
        <p class="figure" data-reveal>
            <span class="figure__whole num" data-k="amountWhole"><?php echo htmlspecialchars($amountWhole); ?></span>
            <span class="figure__dec num" data-k="amountDec"><?php echo $amountDecimals !== '' ? '.' . htmlspecialchars($amountDecimals) : ''; ?></span>
            <span class="figure__note">million pesos released, <span data-k="label"><?php echo htmlspecialchars(strtolower($initial['label']) === 'all provinces' ? 'all three provinces' : $initial['label']); ?></span></span>
        </p>
        <p class="figure" data-reveal style="--i:1">
            <span class="figure__whole num" data-k="people"><?php echo htmlspecialchars($initial['people']); ?></span>
            <span class="figure__dec"></span>
            <span class="figure__note">people reached through group and individual grants</span>
        </p>
        <p class="figure" data-reveal style="--i:2">
            <span class="figure__whole num" data-k="groups"><?php echo htmlspecialchars($initial['groups']); ?></span>
            <span class="figure__dec"></span>
            <span class="figure__note">group projects with associations, LGUs and ACPs</span>
        </p>
        <p class="figure" data-reveal style="--i:3">
            <span class="figure__whole num" data-k="grantees"><?php echo htmlspecialchars($initial['grantees']); ?></span>
            <span class="figure__dec"></span>
            <span class="figure__note">individual grantees</span>
        </p>
        <p class="figures__foot"><?php echo $stats ? 'Updated every ten minutes. Pending records are not counted.' : 'Live totals are unavailable right now.'; ?></p>
    </div>
</section>

<!-- =========================================================
     List: who can do what
     ========================================================= -->
<section class="section grid access" id="access" aria-labelledby="access-title">
    <h2 class="access__head" id="access-title" data-reveal><span class="indent">Each role sees exactly</span> what its work needs</h2>
    <figure class="access__figure" data-reveal>
        <img src="assets/landing/negros-pigafetta-map.jpg" width="1024" height="1657" loading="lazy" decoding="async"
             alt="Hand-painted map of the island of Negros in a blue sea, from Antonio Pigafetta’s manuscript">
        <figcaption>Negros as Antonio Pigafetta painted it in the 1520s.</figcaption>
    </figure>
    <p class="access__word" aria-hidden="true">Roles</p>
    <div class="access__tables" data-reveal style="--i:1">
        <table>
            <caption class="sr-only">Roles</caption>
            <tbody>
                <tr><th scope="row">Super admin</th><td>Everything, plus users and system settings</td><td>All provinces</td></tr>
                <tr><th scope="row">Regional director</th><td>Data work across the three offices</td><td>All provinces</td></tr>
                <tr><th scope="row">Admin</th><td>Records, deletions, logs and backups</td><td>One province</td></tr>
                <tr><th scope="row">Encoder</th><td>Encodes grants, proponents and fieldwork</td><td>One province</td></tr>
                <tr><th scope="row">User</th><td>Views records and reports</td><td>One province</td></tr>
            </tbody>
        </table>
        <table>
            <caption>Offices</caption>
            <tbody>
                <tr><th scope="row">NOCFO</th><td>Negros Occidental Field Office</td><td>Negros Occidental</td></tr>
                <tr><th scope="row">NORFO</th><td>Negros Oriental Field Office</td><td>Negros Oriental</td></tr>
                <tr><th scope="row">SFO</th><td>Siquijor Field Office</td><td>Siquijor</td></tr>
                <tr><th scope="row">NIR</th><td>Regional Office, Negros Island Region</td><td>All three</td></tr>
            </tbody>
        </table>
    </div>
</section>

<!-- =========================================================
     CTA: a sentence with a blank
     ========================================================= -->
<section class="section grid close" id="signin" aria-labelledby="close-title">
    <?php if ($signedIn): ?>
    <form method="get" action="index.php" data-reveal>
        <h2 class="close__sentence" id="close-title">
            Welcome back. <button class="close__submit" type="submit">Open dashboard</button>
        </h2>
        <p class="close__note">Pick up your records, fieldwork and reports where you left them.</p>
    </form>
    <?php else: ?>
    <form method="post" action="login.php" data-reveal>
        <input type="hidden" name="from" value="landing">
        <h2 class="close__sentence" id="close-title">
            <label for="close-user">Sign in as</label>
            <input class="close__input" id="close-user" name="username" type="text" placeholder="username" autocomplete="username" autocapitalize="none" spellcheck="false" required>
            and <button class="close__submit" type="submit">continue</button>
        </h2>
        <p class="close__note">Your password comes next, on the sign-in page. No account yet? Ask the admin of your field office to create one for you.</p>
    </form>
    <?php endif; ?>
</section>

</main>

<!-- =========================================================
     Footer
     ========================================================= -->
<footer class="foot">
    <div class="foot__top">
        <div class="foot__marks">
            <img src="assets/dole.png" width="60" height="52" alt="Department of Labor and Employment">
            <img src="assets/Bagong_Pilipinas_logo.png" width="56" height="52" alt="Bagong Pilipinas">
            <img class="dilp" src="assets/dilp-logo.png" width="52" height="52" alt="DOLE Integrated Livelihood Program">
        </div>
        <p class="foot__note">Department of Labor and Employment, Negros Island Region.</p>
        <p class="foot__note">The DILEEP Monitoring System is for DOLE staff of NOCFO, NORFO and SFO.</p>
        <a class="totop link" href="#top">Back to top</a>
    </div>
    <div class="foot__main">
        <h2><a class="link" href="<?php echo $actionHref; ?>">dileepsys.com</a></h2>
        <p><?php echo $signedIn ? 'You are signed in. The dashboard is one click away.' : 'Sign in with the account your field office gave you.'; ?></p>
    </div>
    <div class="foot__meta">
        <div class="foot__links">
            <a class="link" href="<?php echo $actionHref; ?>"><?php echo $actionLabel; ?></a>
            <a class="link" href="#stages">How it works</a>
            <a class="link" href="#provinces">Provinces</a>
        </div>
        <p class="credits">Pictures: <a href="https://commons.wikimedia.org/wiki/File:Sugar_Cane_Field,_Barangay_Haguimit,_La_Carlota_City_-_panoramio_(1).jpg">“Sugar Cane Field, Barangay Haguimit, La Carlota City”</a> by divina_gracia, <a href="https://creativecommons.org/licenses/by/3.0">CC BY 3.0</a>; <a href="https://commons.wikimedia.org/w/index.php?curid=30447858">“Provincial Capitol of Negros Occidental in Bacolod”</a> by Paolobon140, <a href="https://creativecommons.org/licenses/by-sa/3.0/">CC BY-SA 3.0</a>; <a href="https://commons.wikimedia.org/w/index.php?curid=35110463">“Mount Talinis (Cuernos de Negros)”</a> by Princeofmagatas, <a href="https://creativecommons.org/licenses/by-sa/4.0/">CC BY-SA 4.0</a>; <a href="https://commons.wikimedia.org/w/index.php?curid=30952795">“Siquijor”</a> by RM Bulseco, <a href="https://creativecommons.org/licenses/by/2.0/">CC BY 2.0</a>; <a href="https://commons.wikimedia.org/wiki/File:Pigafetta%27s_map_of_the_island_of_Negros_in_the_Philippines.jpg">Pigafetta’s map of Negros</a>, public domain; <a href="https://commons.wikimedia.org/wiki/File:Sari-sari_Store.JPG">“Sari-sari Store”</a> by Exec8, public domain. All via Wikimedia Commons.</p>
        <p>© <?php echo htmlspecialchars($year); ?> Department of Labor and Employment · m1k0yw0rkz</p>
    </div>
</footer>

<script>
(() => {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // =========================================================
    // Entrances: hero on load, sections once as they enter
    // =========================================================
    const hero = document.querySelector('.hero');
    const heroImg = hero.querySelector('.hero__media img');
    // rAF lets the closed state paint first; the timeout covers tabs where rAF is paused
    const openHero = () => { requestAnimationFrame(() => hero.classList.add('is-in')); setTimeout(() => hero.classList.add('is-in'), 300); };
    if (heroImg.complete) openHero(); else { heroImg.addEventListener('load', openHero, { once: true }); setTimeout(openHero, 1200); }

    const revealed = document.querySelectorAll('[data-reveal], [data-stagebar]');
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => {
            for (const e of entries) if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
        }, { rootMargin: '0px 0px -10% 0px', threshold: 0.08 });
        revealed.forEach((el) => io.observe(el));
    } else {
        revealed.forEach((el) => el.classList.add('is-in'));
    }

    // =========================================================
    // The status badge in the headline walks through the stages
    // =========================================================
    const chip = document.querySelector('.chip');
    const chipText = chip.querySelector('.chip__text');
    const steps = [['pending', 'Pending'], ['approved', 'Approved'], ['implemented', 'Implemented'], ['liquidated', 'Liquidated']];
    if (!reduce) {
        let s = 0;
        setInterval(() => {
            if (document.hidden) return;
            s = (s + 1) % steps.length;
            chip.dataset.status = steps[s][0];
            chipText.textContent = steps[s][1];
        }, 1800);
    }

    // =========================================================
    // Liquidation deadline: turn-over + 10 days (LGU-associated) or + 60 days
    // =========================================================
    const months = ['january','february','march','april','may','june','july','august','september','october','november','december'];
    const manilaToday = () => {
        const p = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Manila', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
        const [y, m, d] = p.split('-').map(Number);
        return Date.UTC(y, m - 1, d);
    };
    const valid = (y, m, d) => { const t = Date.UTC(y, m, d); const x = new Date(t); return x.getUTCMonth() === m && x.getUTCDate() === d ? t : null; };
    const monthOf = (w) => { w = w.toLowerCase(); if (w.length < 3) return -1; return months.findIndex((m) => m.startsWith(w)); };

    function parseDate(raw) {
        const s = raw.trim().toLowerCase().replace(/,/g, ' ').replace(/\s+/g, ' ');
        const today = manilaToday();
        if (!s) return null;
        if (s === 'today') return today;
        if (s === 'yesterday') return today - 864e5;
        let m;
        if ((m = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/))) return valid(+m[1], +m[2] - 1, +m[3]);
        if ((m = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/))) return valid(+m[3], +m[1] - 1, +m[2]);
        const year = new Date(today).getUTCFullYear();
        if ((m = s.match(/^(\d{1,2}) ([a-z]+)\.?(?: (\d{4}))?$/))) { const mo = monthOf(m[2]); return mo < 0 ? null : valid(m[3] ? +m[3] : year, mo, +m[1]); }
        if ((m = s.match(/^([a-z]+)\.? (\d{1,2})(?: (\d{4}))?$/))) { const mo = monthOf(m[1]); return mo < 0 ? null : valid(m[3] ? +m[3] : year, mo, +m[2]); }
        return null;
    }

    const fmt = new Intl.DateTimeFormat('en-GB', { timeZone: 'UTC', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    const input = document.getElementById('turnover');
    const answer = document.getElementById('calc-answer');
    const rows = answer.innerHTML;

    function describe(due) {
        const left = Math.round((due - manilaToday()) / 864e5);
        if (left > 1) return [left + ' days left', false];
        if (left === 1) return ['Due tomorrow', false];
        if (left === 0) return ['Due today', false];
        return ['Overdue by ' + Math.abs(left) + ' day' + (left === -1 ? '' : 's'), true];
    }

    function update() {
        const t = parseDate(input.value);
        if (t === null) {
            answer.innerHTML = '<p class="calc__hint">Type a turn-over date, such as 3 March 2026 or “today”.</p>';
            return;
        }
        if (!answer.querySelector('.calc__row')) answer.innerHTML = rows;
        answer.querySelectorAll('.calc__row').forEach((row, i) => {
            const due = t + (i === 0 ? 10 : 60) * 864e5;
            const [when, late] = describe(due);
            row.querySelector('.calc__due').textContent = fmt.format(due).replace(/^(\w+) /, '$1, ');
            const w = row.querySelector('.calc__when');
            w.textContent = when;
            w.classList.toggle('is-late', late);
        });
    }
    input.addEventListener('input', update);
    document.querySelectorAll('[data-example]').forEach((b) => b.addEventListener('click', () => {
        input.value = b.dataset.example;
        update();
        input.focus({ preventScroll: true });
    }));

    // =========================================================
    // Province switcher: figures and pictures
    // =========================================================
    let sets = <?php echo json_encode($figureSets, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
    const figures = document.querySelector('[data-figures]');
    const places = document.querySelector('.places');
    const buttons = document.querySelectorAll('[data-province]');
    let refreshed = false;

    function show(key) {
        const f = sets[key];
        if (!f) return;
        const [whole, dec] = f.amount.split('.');
        const values = {
            amountWhole: whole, amountDec: dec ? '.' + dec : '',
            people: f.people, groups: f.groups, grantees: f.grantees,
            label: key === 'all' ? 'all three provinces' : f.label,
        };
        figures.classList.add('is-swapping');
        setTimeout(() => {
            figures.querySelectorAll('[data-k]').forEach((el) => { el.textContent = values[el.dataset.k]; });
            figures.classList.remove('is-swapping');
        }, reduce ? 0 : 200);
        places.dataset.show = key;
        buttons.forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.province === key)));
    }

    function toSet(row, label) {
        const nf = (n) => Number(n).toLocaleString('en-US');
        return {
            label,
            amount: (row.amount_released / 1e6).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
            people: nf(row.people_served), groups: nf(row.group_projects), grantees: nf(row.individual_grantees),
        };
    }

    buttons.forEach((b) => b.addEventListener('click', () => {
        const key = b.dataset.province;
        show(key);
        if (refreshed) return;
        refreshed = true;
        fetch('api/public-stats.php', { headers: { Accept: 'application/json' } })
            .then((r) => (r.ok ? r.json() : null))
            .then((j) => {
                if (!j || !j.success) return;
                const next = { all: toSet(j.data.all, 'All provinces') };
                for (const [name, row] of Object.entries(j.data.provinces)) next[name] = toSet(row, name);
                sets = next;
                show(places.dataset.show);
            })
            .catch(() => {});
    }));
})();
</script>
</body>
</html>
