@extends('layouts.vertical', ['subtitle' => 'Dashboard'])

@section('content')
    @php
        $pct = function (int $now, int $prev): ?int {
            if ($prev === 0) return $now > 0 ? 100 : null;
            return (int) round((($now - $prev) / $prev) * 100);
        };
        $breakdownTrend = $pct($kpi['breakdowns'], $kpi['breakdowns_prev']);
        $repairTrend = $pct($kpi['repairs_done'], $kpi['repairs_done_prev']);

        // ---- donut (pure SVG: circumference of r=15.9155 is 100) ----
        $typeColors = ['#3B6FD8', '#139A7B', '#6B57D2', '#D9822B', '#8792A2'];
        $donutTotal = max(1, (int) $unitTypes->sum());
        $runningShare = 0; // the svg is rotated -90deg, so 0 starts at 12 o'clock
        $donutSegments = [];
        foreach ($unitTypes->values() as $i => $value) {
            $share = $value / $donutTotal * 100;
            $donutSegments[] = ['share' => $share, 'offset' => -$runningShare, 'color' => $typeColors[$i % count($typeColors)]];
            $runningShare += $share;
        }

        // ---- units by engineer ----
        $maxEngineer = max(1, (int) $unitsByEngineer->max('total'));
        $engineerColors = ['#3B6FD8', '#139A7B', '#D9822B', '#6B57D2', '#1489A8', '#D9484A'];

        // ---- breakdown chart (pure HTML bars, stacked by cause) ----
        $causeColors = ['#7FA8E6', '#86D1B5', '#F3C77E', '#B9A3EE', '#EE9A9A', '#9CCFEA', '#CDBE95', '#EBA6CE', '#9CCB93', '#CF9BEB', '#B5BCC7', '#D5D9DF'];
        $series = collect($breakdownSummary['series'])->values();
        $dayTotals = collect($breakdownSummary['dates'])->keys()->map(fn ($i) => $series->sum(fn ($s) => $s['data'][$i]));
        $chartMax = max(4, (int) ceil($dayTotals->max() / 2) * 2);

        // ---- calendar navigation ----
        $prevMonth = $monthStart->copy()->subMonthNoOverflow()->format('Y-m');
        $nextMonth = $monthStart->copy()->addMonthNoOverflow();
        $canGoNext = $nextMonth->lte(now()->startOfMonth());
    @endphp

    <style>
        .db { --kpi-gap: 14px; }
        .db-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px; margin: 8px 0 18px; }
        .db-greet { font-size: 26px; font-weight: 700; color: var(--ss-text); margin: 0; letter-spacing: -.01em; }
        .db-greet-sub { color: var(--ss-muted); font-size: 14px; margin: 4px 0 0; }
        .db-clock { display: flex; align-items: center; gap: 14px; background: var(--ss-surface); border: 1px solid var(--ss-border); border-radius: 14px; padding: 10px 16px; box-shadow: var(--ss-shadow); }
        .db-clock-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: color-mix(in srgb, var(--ss-primary) 12%, transparent); color: var(--ss-primary); }
        .db-clock-date { font-weight: 600; color: var(--ss-text); font-size: 13.5px; }
        .db-clock-time { color: var(--ss-muted); font-size: 13px; }
        .db-live-badge { color: var(--ss-green); font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; margin-left: 6px; }
        .db-pulse { width: 8px; height: 8px; border-radius: 50%; background: var(--ss-green); display: inline-block; animation: dbPulse 1.6s infinite; }
        @keyframes dbPulse { 0% { box-shadow: 0 0 0 0 rgba(30,158,116,.55); } 70% { box-shadow: 0 0 0 8px rgba(30,158,116,0); } 100% { box-shadow: 0 0 0 0 rgba(30,158,116,0); } }

        .db-month { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
        .db-month-label { font-size: 17px; font-weight: 700; color: var(--ss-text); }
        .db-month select { width: 170px; background-color: var(--ss-surface); border-color: var(--ss-border); color: var(--ss-text); }

        /* KPI cards */
        .db-kpis { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: var(--kpi-gap); margin-bottom: 18px; }
        @media (max-width: 1599.98px) { .db-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 767.98px) { .db-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 479.98px) { .db-kpis { grid-template-columns: 1fr; } }
        .db-kpi {
            --tint: color-mix(in srgb, var(--kpi) 7%, var(--ss-surface));
            display: flex; gap: 14px; align-items: center; padding: 18px; border-radius: 16px; text-decoration: none;
            background: var(--tint); border: 1px solid color-mix(in srgb, var(--kpi) 16%, var(--ss-border));
            box-shadow: var(--ss-shadow); transition: transform .15s ease, box-shadow .15s ease; min-height: 108px;
        }
        a.db-kpi:hover { transform: translateY(-2px); box-shadow: var(--ss-shadow-lg); }
        .db-kpi-icon { width: 52px; height: 52px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: color-mix(in srgb, var(--kpi) 16%, transparent); color: var(--kpi); }
        .db-kpi-body { min-width: 0; }
        .db-kpi-label { font-size: 13px; color: var(--ss-text-2); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .db-kpi-num { font-size: 30px; font-weight: 800; color: var(--ss-text); line-height: 1.1; margin: 2px 0; }
        .db-kpi-trend { font-size: 12px; font-weight: 600; color: var(--ss-green); display: flex; align-items: center; gap: 3px; white-space: nowrap; }
        .db-kpi-trend.bad { color: var(--ss-red); }
        .db-kpi-trend.muted { color: var(--ss-muted); }
        .db-kpi.priority { border: 2px solid var(--ss-red); box-shadow: 0 0 0 4px color-mix(in srgb, var(--ss-red) 12%, transparent); }
        .db-kpi.priority .db-kpi-num { color: var(--ss-red); }
        .db-kpi { position: relative; }
        /* sits on the card's top edge so it never covers the title */
        .db-flag { position: absolute; top: -9px; right: 14px; box-shadow: 0 2px 6px rgba(214,69,69,.35); font-size: 9px; font-weight: 800; letter-spacing: .06em; color: #fff; background: var(--ss-red); border-radius: 4px; padding: 2px 6px; }

        /* cards */
        .db-card { background: var(--ss-surface); border: 1px solid var(--ss-border); border-radius: 16px; box-shadow: var(--ss-shadow); padding: 20px; height: 100%; }
        .db-card-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 14px; }
        .db-card-title { font-size: 16px; font-weight: 700; color: var(--ss-text); margin: 0; display: flex; align-items: center; gap: 8px; }
        .db-card-link { color: var(--ss-muted); display: inline-flex; }
        .db-card-link:hover { color: var(--ss-primary); }
        .db-sub { font-size: 12px; color: var(--ss-muted); }

        /* donut */
        .db-donut-wrap { display: grid; grid-template-columns: 170px 1fr; gap: 18px; align-items: center; }
        @media (max-width: 575.98px) { .db-donut-wrap { grid-template-columns: 1fr; justify-items: center; } }
        .db-donut { position: relative; width: 170px; height: 170px; }
        .db-donut svg { width: 100%; height: 100%; transform: rotate(-90deg); }
        .db-donut-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .db-donut-center strong { font-size: 30px; font-weight: 800; color: var(--ss-text); line-height: 1; }
        .db-donut-center span { font-size: 12px; color: var(--ss-muted); margin-top: 4px; }
        .db-legend-row { display: grid; grid-template-columns: 12px 1fr auto 46px; gap: 10px; align-items: center; padding: 9px 0; font-size: 13.5px; color: var(--ss-text-2); border-bottom: 1px dashed var(--ss-border); }
        .db-legend-row:last-child { border-bottom: 0; }
        .db-legend-row strong { color: var(--ss-text); }
        .db-legend-dot { width: 12px; height: 12px; border-radius: 50%; }

        /* engineers */
        .db-eng { display: flex; gap: 12px; align-items: center; padding: 10px 0; }
        .db-eng img { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid var(--ss-border); }
        .db-eng-name { font-weight: 600; color: var(--ss-text); font-size: 14px; }
        .db-eng-count { font-size: 12px; color: var(--ss-muted); white-space: nowrap; }
        .db-eng-count strong { color: var(--ss-text); }
        .db-eng-bar { height: 8px; background: var(--ss-surface-3); border-radius: 6px; overflow: hidden; margin-top: 7px; }
        .db-eng-bar span { display: block; height: 100%; border-radius: 6px; }

        /* calendar */
        .db-cal-nav { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .db-cal-month { font-weight: 700; color: var(--ss-text); font-size: 15px; }
        .db-cal-arrows { display: flex; gap: 6px; }
        .db-cal-arrows a, .db-cal-arrows span { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--ss-text-2); border: 1px solid var(--ss-border); }
        .db-cal-arrows a:hover { background: var(--ss-surface-2); color: var(--ss-primary); }
        .db-cal-arrows span { opacity: .35; }
        .db-cal { width: 100%; table-layout: fixed; text-align: center; font-size: 13px; }
        .db-cal th { color: var(--ss-muted); font-weight: 600; padding: 4px 0 8px; font-size: 11.5px; }
        .db-cal td { padding: 3px 0; }
        .db-cal-day { width: 32px; height: 32px; margin: 0 auto; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: relative; color: var(--ss-text); }
        .db-cal-day.out { color: var(--ss-muted); opacity: .45; }
        .db-cal-day.today { background: var(--ss-green); color: #fff; font-weight: 700; }
        .db-cal-day.has-bd { cursor: pointer; font-weight: 700; }
        .db-cal-day.has-bd::after { content: ''; position: absolute; bottom: 2px; width: 5px; height: 5px; border-radius: 50%; background: var(--ss-red); }
        .db-cal-day.today.has-bd::after { background: #fff; }
        .db-cal-day.has-bd:hover { background: color-mix(in srgb, var(--ss-red) 14%, transparent); }

        /* live */
        /* 2 people stats on top, 4 job-type counts below — never cramped */
        .db-live-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-bottom: 14px; }
        .db-live-stats .db-live-stat { grid-column: span 2; }
        @media (max-width: 575.98px) { .db-live-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .db-live-stat, .db-live-type { border-radius: 12px; background: var(--ss-surface-2); border: 1px solid var(--ss-border); padding: 12px 14px; }
        .db-live-stat { display: flex; align-items: center; gap: 12px; }
        .db-live-type { text-align: center; }
        .db-live-icon { width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .db-live-label { font-size: 12px; color: var(--ss-text-2); font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 5px; white-space: nowrap; }
        .db-live-stat .db-live-label { justify-content: flex-start; }
        .db-live-num { font-size: 22px; font-weight: 800; color: var(--ss-text); line-height: 1.2; }
        .db-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; vertical-align: middle; }
        .db-live-table { width: 100%; font-size: 13px; border-collapse: separate; border-spacing: 0; }
        .db-live-table thead th { color: var(--ss-muted); font-weight: 600; font-size: 12px; padding: 10px 8px; background: var(--ss-surface-2); border-bottom: 1px solid var(--ss-border); white-space: nowrap; }
        .db-live-table tbody td { padding: 10px 8px !important; }
        .db-live-table thead th:first-child { border-radius: 10px 0 0 10px; }
        .db-live-table thead th:last-child { border-radius: 0 10px 10px 0; }
        .db-live-table tbody td { padding: 11px 10px; border-bottom: 1px solid var(--ss-border); vertical-align: middle; color: var(--ss-text-2); }
        .db-live-table tbody tr:last-child td { border-bottom: 0; }
        /* Rev 8: finished jobs sit on a darker band so the running ones stand out */
        .db-live-table tbody tr.db-row-done td { background: color-mix(in srgb, var(--ss-text) 9%, var(--ss-surface)); }
        .db-live-table tbody tr.db-row-done td:first-child { border-radius: 10px 0 0 10px; }
        .db-live-table tbody tr.db-row-done td:last-child { border-radius: 0 10px 10px 0; }
        .db-live-table tbody tr.db-row-leave td { opacity: .75; }
        .db-leave-note { font-size: 11px; font-weight: 700; color: #7A6FD0; background: rgba(122,111,208,0.14); border-radius: 6px; padding: 2px 7px; vertical-align: middle; margin-left: 4px; }
        .db-st-leave { background: rgba(122,111,208,0.14); color: #7A6FD0; }
        .db-idle-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; margin: 22px 0 10px; padding-top: 16px; border-top: 1px solid var(--ss-border); }
        .db-idle-title { font-weight: 700; color: var(--ss-text); display: flex; align-items: center; gap: 6px; }
        .db-idle-total { font-size: 20px; font-weight: 800; color: var(--ss-text); line-height: 1.1; }
        .db-gap { display: inline-block; font-size: 11px; font-weight: 600; background: var(--ss-surface-2); border: 1px solid var(--ss-border); border-radius: 6px; padding: 2px 7px; margin: 1px 4px 1px 0; white-space: nowrap; }
        .db-unit-ic { width: 32px; height: 32px; border-radius: 8px; background: var(--ss-surface-2); color: var(--ss-muted); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .db-unit-name { font-weight: 600; color: var(--ss-text); }
        .db-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; }
        .db-type-pill { display: inline-block; padding: 3px 11px; border-radius: 20px; font-size: 11px; font-weight: 700; color: #fff; white-space: nowrap; }
        .db-status { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap; }
        .db-st-progress { background: color-mix(in srgb, var(--ss-green) 15%, transparent); color: var(--ss-green); }
        .db-st-hold { background: color-mix(in srgb, var(--ss-muted) 18%, transparent); color: var(--ss-text-2); }
        .db-st-pending { background: color-mix(in srgb, var(--ss-amber) 16%, transparent); color: var(--ss-amber); }
        .db-st-done { background: color-mix(in srgb, var(--ss-blue) 15%, transparent); color: var(--ss-blue); }
        .db-row-link { color: var(--ss-muted); display: inline-flex; }
        .db-row-link:hover { color: var(--ss-primary); }

        /* breakdown chart */
        .db-total-chip { background: color-mix(in srgb, var(--ss-purple) 15%, transparent); color: var(--ss-purple); font-weight: 700; border-radius: 8px; padding: 4px 12px; font-size: 13px; }
        .db-bd-tools { display: flex; gap: 8px; margin-bottom: 8px; }
        .db-bd-tools select { max-width: 200px; background-color: var(--ss-surface); border-color: var(--ss-border); color: var(--ss-text); }
        .db-legend { display: flex; flex-wrap: wrap; gap: 6px 14px; margin: 8px 0 12px; font-size: 11.5px; color: var(--ss-text-2); }
        .db-legend span { display: inline-flex; align-items: center; gap: 5px; }
        .db-legend i { width: 10px; height: 10px; border-radius: 3px; display: inline-block; }
        .db-chart { position: relative; height: 240px; display: grid; grid-template-columns: 26px 1fr; gap: 6px; }
        .db-chart-y { display: flex; flex-direction: column; justify-content: space-between; align-items: flex-end; font-size: 10.5px; color: var(--ss-muted); padding-bottom: 20px; }
        .db-chart-plot { position: relative; display: flex; align-items: flex-end; gap: 3px; padding-bottom: 20px;
            background-image: linear-gradient(to top, var(--ss-border) 1px, transparent 1px); background-size: 100% 25%; background-position: 0 -20px; }
        .db-bar { flex: 1; height: 100%; display: flex; flex-direction: column-reverse; position: relative; cursor: default; border-radius: 3px 3px 0 0; }
        .db-bar.has { cursor: pointer; }
        .db-bar.has:hover { outline: 2px solid color-mix(in srgb, var(--ss-primary) 45%, transparent); outline-offset: 1px; }
        .db-bar i { display: block; width: 100%; }
        .db-bar i:last-child { border-radius: 3px 3px 0 0; }
        .db-bar-x { position: absolute; bottom: -19px; left: 50%; transform: translateX(-50%); font-size: 9.5px; color: var(--ss-muted); white-space: nowrap; }
        .db-bar.today .db-bar-x { color: var(--ss-green); font-weight: 700; }

        .bd-modal-table { width: 100%; font-size: 13px; }
        .bd-modal-table th { color: var(--ss-muted); font-weight: 600; padding: 8px; border-bottom: 1px solid var(--ss-border); }
        .bd-modal-table td { padding: 9px 8px; border-bottom: 1px solid var(--ss-border); vertical-align: top; color: var(--ss-text-2); }

        /* =====================================================================
           Polish pass: quiet white surfaces, hairline borders, one soft shadow,
           clear type hierarchy, and a gentle hover highlight on every section
           (same idea as the sidebar: a light tint, never a jump).
           ===================================================================== */
        .db {
            --db-line: color-mix(in srgb, var(--ss-border) 85%, transparent);
            --db-shadow: 0 1px 2px rgba(15, 42, 67, 0.04), 0 2px 8px rgba(15, 42, 67, 0.04);
            --db-shadow-hover: 0 2px 4px rgba(15, 42, 67, 0.05), 0 8px 24px rgba(15, 42, 67, 0.08);
            --db-hover-bg: color-mix(in srgb, var(--ss-primary) 4.5%, var(--ss-surface));
            --db-hover-line: color-mix(in srgb, var(--ss-primary) 32%, var(--ss-border));
            --db-ease: cubic-bezier(.2, .7, .2, 1);
            font-variant-numeric: tabular-nums;
        }
        .db-greet { font-size: 24px; font-weight: 700; letter-spacing: -.02em; }
        .db-greet-sub { font-size: 13.5px; }
        .db-clock { border-color: var(--db-line); box-shadow: var(--db-shadow); border-radius: 14px; }
        .db-month-label { font-size: 15px; font-weight: 600; letter-spacing: -.01em; }
        .db-month select { width: 160px; border-radius: 9px; border-color: var(--db-line); box-shadow: none; font-weight: 500; }

        /* KPI tiles: white, colour lives only in the icon */
        .db-kpis { gap: 12px; }
        .db-kpi {
            background: var(--ss-surface); border: 1px solid var(--db-line); border-radius: 16px; box-shadow: var(--db-shadow);
            padding: 16px 18px; min-height: 100px; gap: 14px;
            transition: background-color .2s var(--db-ease), border-color .2s var(--db-ease), box-shadow .2s var(--db-ease), transform .2s var(--db-ease);
        }
        a.db-kpi:hover { transform: translateY(-1px); background: var(--db-hover-bg); border-color: var(--db-hover-line); box-shadow: var(--db-shadow-hover); }
        a.db-kpi:focus-visible { outline: 2px solid var(--db-hover-line); outline-offset: 2px; }
        .db-kpi-icon { width: 44px; height: 44px; border-radius: 12px; background: color-mix(in srgb, var(--kpi) 11%, transparent); }
        .db-kpi-icon svg { width: 21px; height: 21px; }
        .db-kpi-label { font-size: 12.5px; font-weight: 500; color: var(--ss-muted); }
        .db-kpi-num { font-size: 28px; font-weight: 700; letter-spacing: -.02em; margin: 1px 0 2px; }
        .db-kpi-trend { font-size: 11.5px; font-weight: 500; }
        .db-kpi.priority { border: 1px solid color-mix(in srgb, var(--ss-red) 45%, var(--ss-border)); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ss-red) 9%, transparent), var(--db-shadow); }
        a.db-kpi.priority:hover { border-color: var(--ss-red); background: color-mix(in srgb, var(--ss-red) 3%, var(--ss-surface)); }
        .db-flag { border-radius: 6px; font-size: 9px; letter-spacing: .08em; padding: 2px 7px; box-shadow: 0 2px 6px rgba(214, 69, 69, .25); }

        /* section cards */
        .db-card {
            border: 1px solid var(--db-line); border-radius: 18px; box-shadow: var(--db-shadow); padding: 20px 22px;
            transition: background-color .2s var(--db-ease), border-color .2s var(--db-ease), box-shadow .2s var(--db-ease);
        }
        .db-card:hover { background: var(--db-hover-bg); border-color: var(--db-hover-line); box-shadow: var(--db-shadow-hover); }
        .db-card-head { margin-bottom: 16px; }
        .db-card-title { font-size: 15px; font-weight: 600; letter-spacing: -.01em; }
        .db-card-link { width: 28px; height: 28px; border-radius: 8px; align-items: center; justify-content: center; transition: background-color .15s, color .15s; }
        .db-card-link:hover { background: var(--ss-surface-3); }

        /* donut + legend */
        .db-legend-row { border-bottom: 1px solid var(--db-line); padding: 10px 0; font-size: 13px; }
        .db-legend-dot { width: 10px; height: 10px; }

        /* engineers */
        .db-eng { padding: 10px 8px; margin: 0 -8px; border-radius: 12px; transition: background-color .15s; }
        .db-eng:hover { background: color-mix(in srgb, var(--ss-text) 4%, transparent); }
        .db-eng img { border: 0; box-shadow: 0 0 0 2px var(--ss-surface), 0 0 0 3px var(--db-line); }
        .db-eng-bar { height: 6px; background: var(--ss-surface-3); }

        /* calendar */
        .db-cal-month { font-size: 14px; font-weight: 600; }
        .db-cal-arrows a, .db-cal-arrows span { border: 0; background: var(--ss-surface-3); }
        .db-cal th { font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: .04em; }
        .db-cal-day { transition: background-color .15s; }
        .db-cal-day:not(.out):not(.today):hover { background: var(--ss-surface-3); }

        /* live activity: tiles without borders, table header without a box */
        .db-live-stats { gap: 10px; }
        .db-live-stat, .db-live-type { border: 0; background: color-mix(in srgb, var(--ss-text) 3.5%, transparent); border-radius: 14px; transition: background-color .15s; }
        .db-live-stat:hover, .db-live-type:hover { background: color-mix(in srgb, var(--ss-text) 6%, transparent); }
        .db-live-icon { width: 40px; height: 40px; border-radius: 12px; }
        .db-live-label { font-weight: 500; color: var(--ss-muted); }
        .db-live-num { font-weight: 700; letter-spacing: -.02em; }
        .db-live-table thead th { background: transparent; border-bottom: 1px solid var(--db-line); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; padding: 8px 10px; }
        .db-live-table thead th:first-child, .db-live-table thead th:last-child { border-radius: 0; }
        .db-live-table tbody td { border-bottom: 1px solid var(--db-line); transition: background-color .15s; }
        .db-live-table tbody tr:hover td { background: color-mix(in srgb, var(--ss-text) 3%, transparent); }
        .db-live-table tbody tr.db-row-done td { background: color-mix(in srgb, var(--ss-text) 6%, transparent); }
        .db-live-table tbody tr.db-row-done:hover td { background: color-mix(in srgb, var(--ss-text) 8%, transparent); }
        .db-unit-ic { border-radius: 10px; background: color-mix(in srgb, var(--ss-text) 4%, transparent); }
        .db-type-pill, .db-status { font-weight: 600; letter-spacing: .01em; }
        .db-idle-head { border-top-color: var(--db-line); }
        .db-gap { border: 0; background: color-mix(in srgb, var(--ss-text) 5%, transparent); }

        /* breakdown summary */
        .db-total-chip { border-radius: 20px; font-weight: 600; font-size: 12px; padding: 4px 12px; }
        .db-bd-tools select { border-radius: 9px; border-color: var(--db-line); }
        .db-chart-plot { background-image: linear-gradient(to top, var(--db-line) 1px, transparent 1px); }

        /* KPI tiles: soft tint of their own colour, refined and consistent */
        .db-kpi {
            background: color-mix(in srgb, var(--kpi) 5.5%, var(--ss-surface));
            border-color: color-mix(in srgb, var(--kpi) 16%, var(--ss-border));
        }
        a.db-kpi:hover {
            background: color-mix(in srgb, var(--kpi) 9%, var(--ss-surface));
            border-color: color-mix(in srgb, var(--kpi) 38%, var(--ss-border));
        }
        .db-kpi-icon { background: color-mix(in srgb, var(--kpi) 14%, var(--ss-surface)); box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--kpi) 12%, transparent); }
        .db-kpi.priority { background: color-mix(in srgb, var(--ss-red) 6%, var(--ss-surface)); }

        /* every card fills its row: content uses the free space, footers sit on the bottom edge */
        .db-card { display: flex; flex-direction: column; }
        .db-fill { flex: 1 1 auto; display: flex; flex-direction: column; justify-content: center; min-height: 0; }
        .db-fill-top { justify-content: flex-start; }
        .db-card-foot { margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--db-line); display: flex; }
        .db-card-foot-col { flex-direction: column; gap: 12px; }
        .db-foot-stat { flex: 1; display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .db-foot-stat + .db-foot-stat { padding-left: 14px; border-left: 1px solid var(--db-line); margin-left: 14px; }
        .db-foot-stat span { font-size: 11.5px; color: var(--ss-muted); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .db-foot-stat strong { font-size: 18px; font-weight: 700; color: var(--ss-text); letter-spacing: -.01em; line-height: 1.2; }
        .db-share { display: flex; height: 8px; border-radius: 6px; overflow: hidden; gap: 2px; background: var(--ss-surface-3); }
        .db-share span { display: block; height: 100%; }
        .db-chart { flex: 1 1 auto; height: auto; min-height: 240px; }

        @media (prefers-reduced-motion: reduce) {
            .db-kpi, .db-card, .db-eng, .db-live-stat, .db-live-type { transition: none; }
            a.db-kpi:hover { transform: none; }
        }
    </style>

    <div class="db">
        {{-- Greeting + clock --}}
        <div class="db-head">
            <div>
                <h3 class="db-greet">{{ $greeting }}, {{ \Illuminate\Support\Str::of($user->name)->before(' ') }} 👋</h3>
                <p class="db-greet-sub">Here's what's happening with your maintenance operations today.</p>
            </div>
            <div class="db-clock">
                <div class="db-clock-icon"><x-ui-icon name="calendar" size="20" /></div>
                <div>
                    <div class="db-clock-date">{{ now()->format('l, d F Y') }}</div>
                    <div class="db-clock-time" id="dbClock">{{ now()->format('h:i A') }}</div>
                </div>
                <span class="db-live-badge"><span class="db-pulse"></span> Live</span>
            </div>
        </div>

        {{-- Month selector --}}
        <form method="GET" action="{{ route('dashboard') }}" class="db-month">
            <span class="db-month-label">{{ $monthStart->format('F') }} operation</span>
            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Month">
                @foreach ($monthOptions as $value => $label)
                    <option value="{{ $value }}" {{ $monthStart->format('Y-m') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>

        {{-- KPI cards --}}
        <div class="db-kpis">
            <a href="{{ route('admin.contracts.index', ['view' => 'grid']) }}" class="db-kpi" style="--kpi:#3B6FD8;">
                <div class="db-kpi-icon"><x-ui-icon name="building" size="24" /></div>
                <div class="db-kpi-body">
                    <div class="db-kpi-label">Total Projects</div>
                    <div class="db-kpi-num">{{ number_format($kpi['projects']) }}</div>
                    <div class="db-kpi-trend {{ $kpi['projects_new'] ? '' : 'muted' }}"><x-ui-icon name="arrow-up" size="13" stroke="2.4" /> {{ $kpi['projects_new'] }} this month</div>
                </div>
            </a>
            <a href="{{ route('admin.contracts.index') }}" class="db-kpi" style="--kpi:#139A7B;">
                <div class="db-kpi-icon"><x-ui-icon name="layers" size="24" /></div>
                <div class="db-kpi-body">
                    <div class="db-kpi-label">Total Units</div>
                    <div class="db-kpi-num">{{ number_format($kpi['units']) }}</div>
                    <div class="db-kpi-trend {{ $kpi['units_new'] ? '' : 'muted' }}"><x-ui-icon name="arrow-up" size="13" stroke="2.4" /> {{ $kpi['units_new'] }} this month</div>
                </div>
            </a>
            <a href="{{ route('admin.job-types.breakdown', ['month' => $monthStart->month, 'year' => $monthStart->year]) }}" class="db-kpi" style="--kpi:#6B57D2;">
                <div class="db-kpi-icon"><x-ui-icon name="activity" size="24" /></div>
                <div class="db-kpi-body">
                    <div class="db-kpi-label">Breakdown</div>
                    <div class="db-kpi-num">{{ $kpi['breakdowns'] }}</div>
                    @if (! is_null($breakdownTrend))
                        {{-- more breakdowns = bad --}}
                        <div class="db-kpi-trend {{ $breakdownTrend > 0 ? 'bad' : '' }}">
                            <x-ui-icon :name="$breakdownTrend > 0 ? 'arrow-up' : 'arrow-down'" size="13" stroke="2.4" /> {{ abs($breakdownTrend) }}% vs last month
                        </div>
                    @else
                        <div class="db-kpi-trend muted">recorded this month</div>
                    @endif
                </div>
            </a>
            <a href="{{ route('admin.shutdown-units.index') }}" class="db-kpi {{ $kpi['shutdown'] > 0 ? 'priority' : '' }}" style="--kpi:#D9484A;"
                title="Click to see every elevator that is currently shut down">
                <div class="db-kpi-icon"><x-ui-icon name="alert" size="24" /></div>
                @if ($kpi['shutdown'] > 0)<span class="db-flag">PRIORITY</span>@endif
                <div class="db-kpi-body">
                    <div class="db-kpi-label">Shutdown Units</div>
                    <div class="db-kpi-num">{{ $kpi['shutdown'] }}</div>
                    <div class="db-kpi-trend {{ $kpi['shutdown'] > 0 ? 'bad' : 'muted' }}">{{ $kpi['shutdown'] > 0 ? 'view the list' : 'all working' }}</div>
                </div>
            </a>
            <a href="{{ route('admin.job-types.casual-job') }}" class="db-kpi" style="--kpi:#D9822B;">
                <div class="db-kpi-icon"><x-ui-icon name="wrench" size="24" /></div>
                <div class="db-kpi-body">
                    <div class="db-kpi-label">Casual Jobs</div>
                    <div class="db-kpi-num">{{ $kpi['casual_open'] }}</div>
                    <div class="db-kpi-trend muted">pending / in progress</div>
                </div>
            </a>
            <a href="{{ route('admin.job-types.repair', ['month' => $monthStart->month, 'year' => $monthStart->year, 'status' => 'completed']) }}" class="db-kpi" style="--kpi:#1489A8;">
                <div class="db-kpi-icon"><x-ui-icon name="settings" size="24" /></div>
                <div class="db-kpi-body">
                    <div class="db-kpi-label">Repair</div>
                    <div class="db-kpi-num">{{ $kpi['repairs_done'] }}</div>
                    @if (! is_null($repairTrend))
                        <div class="db-kpi-trend {{ $repairTrend < 0 ? 'bad' : '' }}">
                            <x-ui-icon :name="$repairTrend >= 0 ? 'arrow-up' : 'arrow-down'" size="13" stroke="2.4" /> {{ abs($repairTrend) }}% vs last month
                        </div>
                    @else
                        <div class="db-kpi-trend muted">completed this month</div>
                    @endif
                </div>
            </a>
        </div>

        {{-- Units by type · Units by engineer · Calendar --}}
        <div class="row g-3 mb-3">
            <div class="col-xxl-4 col-lg-6">
                <div class="db-card">
                    <div class="db-card-head">
                        <p class="db-card-title">Total Units by Equipment Type</p>
                        <a href="{{ route('admin.contracts.index') }}" class="db-card-link" aria-label="Open master list"><x-ui-icon name="chevron-right" size="18" /></a>
                    </div>
                    @if ($unitTypes->isEmpty())
                        <div class="db-fill"><p class="text-muted small mb-0">No units yet.</p></div>
                    @else
                        <div class="db-fill">
                        <div class="db-donut-wrap">
                            <div class="db-donut">
                                <svg viewBox="0 0 42 42" aria-hidden="true">
                                    <circle cx="21" cy="21" r="15.9155" fill="none" stroke="var(--ss-surface-3)" stroke-width="5"></circle>
                                    @foreach ($donutSegments as $seg)
                                        <circle cx="21" cy="21" r="15.9155" fill="none" stroke="{{ $seg['color'] }}" stroke-width="5"
                                            stroke-dasharray="{{ max(0, $seg['share'] - 0.6) }} {{ 100 - max(0, $seg['share'] - 0.6) }}"
                                            stroke-dashoffset="{{ $seg['offset'] }}"></circle>
                                    @endforeach
                                </svg>
                                <div class="db-donut-center">
                                    <strong>{{ number_format($kpi['units']) }}</strong>
                                    <span>Total Units</span>
                                </div>
                            </div>
                            <div>
                                @foreach ($unitTypes as $type => $total)
                                    <div class="db-legend-row">
                                        <span class="db-legend-dot" style="background: {{ $typeColors[$loop->index % count($typeColors)] }};"></span>
                                        <span>{{ $type }}</span>
                                        <strong>{{ $total }}</strong>
                                        <span class="db-sub text-end">{{ round($total / $donutTotal * 100, 1) }}%</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        </div>
                        @php
                            $working = max(0, $kpi['units'] - $kpi['shutdown']);
                            $workingPct = $kpi['units'] ? round($working / $kpi['units'] * 100) : 0;
                        @endphp
                        <div class="db-card-foot">
                            <div class="db-foot-stat"><span>Working</span><strong>{{ number_format($working) }}</strong></div>
                            <div class="db-foot-stat"><span>Shut down</span><strong class="{{ $kpi['shutdown'] ? 'text-danger' : '' }}">{{ $kpi['shutdown'] }}</strong></div>
                            <div class="db-foot-stat"><span>Availability</span><strong>{{ $workingPct }}%</strong></div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-xxl-4 col-lg-6">
                <div class="db-card">
                    <div class="db-card-head">
                        <p class="db-card-title">Units by Engineer</p>
                        <a href="{{ route('admin.team.index') }}" class="db-card-link" aria-label="Open team"><x-ui-icon name="chevron-right" size="18" /></a>
                    </div>
                    <div class="db-fill">
                    @forelse ($unitsByEngineer as $engineer)
                        @php $color = $engineerColors[$loop->index % count($engineerColors)]; @endphp
                        <div class="db-eng">
                            <img src="{{ user_avatar($engineer->image_path) }}" alt="">
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex justify-content-between align-items-baseline gap-2">
                                    <span class="db-eng-name text-truncate">{{ $engineer->name }}</span>
                                    <span class="db-eng-count"><strong>{{ $engineer->total }}</strong> units · {{ $kpi['units'] ? round($engineer->total / $kpi['units'] * 100) : 0 }}%</span>
                                </div>
                                <div class="db-eng-bar"><span style="width: {{ round($engineer->total / $maxEngineer * 100) }}%; background: {{ $color }};"></span></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No units assigned to engineers yet.</p>
                    @endforelse
                    </div>
                    @if ($unitsByEngineer->isNotEmpty())
                        @php
                            $assignedUnits = (int) $unitsByEngineer->whereNotNull('id')->sum('total');
                            $engineerCount = $unitsByEngineer->whereNotNull('id')->count();
                            $shareTotal = max(1, (int) $unitsByEngineer->sum('total'));
                        @endphp
                        <div class="db-card-foot db-card-foot-col">
                            <div class="db-share" aria-hidden="true">
                                @foreach ($unitsByEngineer as $engineer)
                                    <span style="width: {{ $engineer->total / $shareTotal * 100 }}%; background: {{ $engineerColors[$loop->index % count($engineerColors)] }};" title="{{ $engineer->name }}: {{ $engineer->total }}"></span>
                                @endforeach
                            </div>
                            <div class="d-flex">
                                <div class="db-foot-stat"><span>Engineers</span><strong>{{ $engineerCount }}</strong></div>
                                <div class="db-foot-stat"><span>Units assigned</span><strong>{{ number_format($assignedUnits) }}</strong></div>
                                <div class="db-foot-stat"><span>Avg per engineer</span><strong>{{ $engineerCount ? round($assignedUnits / $engineerCount) : 0 }}</strong></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-xxl-4 col-lg-12">
                <div class="db-card">
                    <div class="db-card-head mb-2">
                        <p class="db-card-title">Calendar</p>
                        <span class="db-sub d-inline-flex align-items-center gap-1"><span class="db-dot" style="background: var(--ss-red);"></span> breakdown day — click to view</span>
                    </div>
                    <div class="db-cal-nav">
                        <span class="db-cal-month">{{ $monthStart->format('F Y') }}</span>
                        <div class="db-cal-arrows">
                            <a href="{{ route('dashboard', ['month' => $prevMonth]) }}" aria-label="Previous month"><x-ui-icon name="chevron-left" size="16" /></a>
                            @if ($canGoNext)
                                <a href="{{ route('dashboard', ['month' => $nextMonth->format('Y-m')]) }}" aria-label="Next month"><x-ui-icon name="chevron-right" size="16" /></a>
                            @else
                                <span><x-ui-icon name="chevron-right" size="16" /></span>
                            @endif
                        </div>
                    </div>
                    <table class="db-cal">
                        <thead><tr>@foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d)<th>{{ $d }}</th>@endforeach</tr></thead>
                        <tbody>
                            @foreach ($calendar as $week)
                                <tr>
                                    @foreach ($week as $day)
                                        <td>
                                            <div class="db-cal-day {{ $day['inMonth'] ? '' : 'out' }} {{ $day['isToday'] ? 'today' : '' }} {{ $day['breakdowns'] ? 'has-bd' : '' }}"
                                                @if ($day['breakdowns']) data-date="{{ $day['date']->toDateString() }}" title="{{ $day['breakdowns'] }} breakdown(s) — click for causes" @endif>
                                                {{ $day['date']->day }}
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Live activity · Breakdown summary --}}
        <div class="row g-3">
            <div class="col-xxl-7">
                <div class="db-card">
                    <div class="db-card-head">
                        <div>
                            <p class="db-card-title"><span class="db-pulse"></span> Live Maintenance Activity</p>
                            <div class="db-sub mt-1">Real-time view of today's work across all units</div>
                        </div>
                        <a href="{{ route('admin.tracking.index', ['from' => now()->toDateString(), 'to' => now()->toDateString()]) }}"
                            class="small fw-semibold d-inline-flex align-items-center" style="color: var(--ss-primary);">View All <x-ui-icon name="chevron-right" size="14" /></a>
                    </div>
                    <div id="liveActivity">
                        @include('dashboard._live', ['live' => $live])
                    </div>
                </div>
            </div>

            <div class="col-xxl-5">
                <div class="db-card">
                    <div class="db-card-head">
                        <p class="db-card-title">Breakdown Summary</p>
                        <span class="db-total-chip">Total {{ $breakdownSummary['total'] }}</span>
                    </div>
                    <div class="db-bd-tools">
                        <select id="bdDaySelect" class="form-select form-select-sm" aria-label="Day">
                            <option value="">Pick a day…</option>
                            @foreach ($breakdownSummary['dates'] as $i => $date)
                                @if ($dayTotals[$i] > 0)
                                    <option value="{{ $date }}">{{ $breakdownSummary['labels'][$i] }} — {{ $dayTotals[$i] }} breakdown{{ $dayTotals[$i] > 1 ? 's' : '' }}</option>
                                @endif
                            @endforeach
                        </select>
                        <button type="button" id="bdDayBtn" class="btn btn-sm btn-primary text-nowrap">View causes</button>
                    </div>

                    @if ($breakdownSummary['total'] > 0)
                        <div class="db-legend">
                            @foreach ($series as $s)
                                <span><i style="background: {{ $causeColors[$loop->index % count($causeColors)] }};"></i>{{ $s['name'] }}</span>
                            @endforeach
                        </div>
                        <div class="db-chart">
                            <div class="db-chart-y">
                                @foreach ([$chartMax, $chartMax * 0.75, $chartMax * 0.5, $chartMax * 0.25, 0] as $tick)
                                    <span>{{ rtrim(rtrim(number_format($tick, 1), '0'), '.') }}</span>
                                @endforeach
                            </div>
                            <div class="db-chart-plot">
                                @foreach ($breakdownSummary['dates'] as $i => $date)
                                    @php
                                        $tip = $series->filter(fn ($s) => $s['data'][$i] > 0)->map(fn ($s) => $s['name'] . ': ' . $s['data'][$i])->implode("\n");
                                        $dayNum = $i + 1;
                                    @endphp
                                    <div class="db-bar {{ $dayTotals[$i] ? 'has' : '' }} {{ $date === now()->toDateString() ? 'today' : '' }}"
                                        @if ($dayTotals[$i]) data-date="{{ $date }}" title="{{ $breakdownSummary['labels'][$i] }} — {{ $dayTotals[$i] }} breakdown(s)&#10;{{ $tip }}" @endif>
                                        @foreach ($series as $s)
                                            @if ($s['data'][$i] > 0)
                                                <i style="height: {{ $s['data'][$i] / $chartMax * 100 }}%; background: {{ $causeColors[$loop->index % count($causeColors)] }};"></i>
                                            @endif
                                        @endforeach
                                        @if ($dayNum === 1 || $dayNum % 3 === 0)
                                            <span class="db-bar-x">{{ $dayNum }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="db-sub mt-3">Breakdowns per day, coloured by cause — click a bar for that day's details.</div>
                    @else
                        <div class="db-fill text-center py-5">
                            <div class="db-unit-ic mx-auto mb-2" style="width:48px;height:48px;border-radius:50%;"><x-ui-icon name="check-circle" size="24" /></div>
                            <p class="mb-0" style="color: var(--ss-muted);">No breakdowns recorded in {{ $monthStart->format('F Y') }}.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Daily Breakdown Details modal --}}
    <div class="modal fade" id="bdDayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Breakdown Details</h5>
                        <div class="db-sub" id="bdDayTitle"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="bdDayBody"></div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ----- clock (Qatar time) -----
            const clock = document.getElementById('dbClock');
            const tick = () => clock.textContent = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Qatar' });
            setInterval(tick, 15000);

            // ----- daily breakdown details -----
            const modalEl = document.getElementById('bdDayModal');
            const modal = window.bootstrap ? new bootstrap.Modal(modalEl) : null;
            const esc = v => { const d = document.createElement('div'); d.textContent = v ?? ''; return d.innerHTML; };

            window.openBreakdownDay = function(date) {
                if (!date || !modal) return;
                document.getElementById('bdDayTitle').textContent = 'Loading…';
                document.getElementById('bdDayBody').innerHTML = '<p class="text-muted mb-0">Loading…</p>';
                modal.show();

                fetch(`{{ route('admin.dashboard.breakdowns') }}?date=${date}`, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => {
                        document.getElementById('bdDayTitle').textContent = `${data.date} — ${data.count} breakdown${data.count === 1 ? '' : 's'}`;
                        if (!data.count) {
                            document.getElementById('bdDayBody').innerHTML = '<p class="text-muted mb-0">No breakdowns on this day.</p>';
                            return;
                        }
                        document.getElementById('bdDayBody').innerHTML = `
                            <table class="bd-modal-table">
                                <thead><tr><th>Job</th><th>Unit / Project</th><th>Cause of Breakdown</th><th>Technician(s)</th><th>Status</th></tr></thead>
                                <tbody>${data.rows.map(r => `
                                    <tr>
                                        <td><a href="${r.url}" class="fw-semibold">${esc(r.code)}</a></td>
                                        <td><div class="db-unit-name">${esc(r.unit)}</div><div class="db-sub">${esc(r.project)}${r.location ? ' — ' + esc(r.location) : ''}</div></td>
                                        <td class="db-unit-name">${esc(r.cause)}</td>
                                        <td>${esc(r.technicians) || '—'}</td>
                                        <td>${esc(r.status)}${r.unit_status ? `<div class="db-sub" style="color:${r.unit_status === 'Shutdown' ? 'var(--ss-red)' : 'var(--ss-green)'}; font-weight:700;">${esc(r.unit_status)}</div>` : ''}</td>
                                    </tr>`).join('')}
                                </tbody>
                            </table>`;
                    });
            };

            document.getElementById('bdDayBtn').addEventListener('click', () => openBreakdownDay(document.getElementById('bdDaySelect').value));
            document.querySelectorAll('.db-cal-day.has-bd, .db-bar.has').forEach(el => el.addEventListener('click', () => openBreakdownDay(el.dataset.date)));

            // ----- live activity: refresh every minute -----
            setInterval(() => {
                if (document.hidden) return;
                fetch("{{ route('admin.dashboard.live') }}", { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.ok ? r.text() : null)
                    .then(html => { if (html) document.getElementById('liveActivity').innerHTML = html; });
            }, 60000);
        });
    </script>
@endsection
