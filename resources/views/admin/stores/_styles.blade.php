{{-- Shared styles for the Stores pages (inventory, material requests, analytics) --}}
<style>
    .st-card { border: none; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); }
    .st-stat { border-radius: 12px; background: var(--ss-surface, #fff); border: 1px solid var(--ss-border, #e7e9ee); padding: 14px 16px; height: 100%; display: flex; align-items: center; gap: 12px; }
    .st-stat .ic { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: color-mix(in srgb, var(--c) 14%, transparent); color: var(--c); }
    .st-stat .lbl { font-size: 12px; color: var(--ss-text-2, #56606f); font-weight: 600; }
    .st-stat .num { font-size: 20px; font-weight: 800; color: var(--ss-text, #16233b); line-height: 1.2; }
    a.st-stat { text-decoration: none; }
    a.st-stat:hover { border-color: var(--c); }
    .st-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; }
    .st-table thead th { background: var(--ss-surface-2, #F5F7FA); color: var(--ss-text-2, #56606f); font-weight: 600; padding: 10px 12px; border-bottom: 1px solid var(--ss-border, #e7e9ee); white-space: nowrap; }
    .st-table tbody td { padding: 10px 12px; border-bottom: 1px solid var(--ss-border, #f0f1f4); vertical-align: middle; color: var(--ss-text-2, #3d4757); }
    .st-table tbody tr:last-child td { border-bottom: 0; }
    .st-table tbody tr.is-low td { background: color-mix(in srgb, #E08A00 7%, transparent); }
    .st-table tbody tr.is-out td { background: color-mix(in srgb, #D64545 7%, transparent); }
    .st-pill { display: inline-block; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 6px; color: #fff; white-space: nowrap; }
    .st-soft { display: inline-block; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px; white-space: nowrap; background: color-mix(in srgb, var(--c) 14%, transparent); color: var(--c); }
    .st-name { font-weight: 600; color: var(--ss-text, #16233b); }
    .st-sub { font-size: 11px; color: var(--ss-muted, #8792a2); }
    .st-qty-in { color: #2E9E5B; font-weight: 700; }
    .st-qty-out { color: #D64545; font-weight: 700; }
    /* segmented status filter: one soft track, the active option is a raised white chip */
    .st-tabs { display: inline-flex; gap: 2px; padding: 3px; border-radius: 10px; background: color-mix(in srgb, var(--ss-text, #16233b) 6%, transparent); max-width: 100%; overflow-x: auto; scrollbar-width: none; }
    .st-tabs::-webkit-scrollbar { display: none; }
    .st-tabs a { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 500; padding: 6px 14px; border-radius: 8px; color: var(--ss-text-2, #56606f); text-decoration: none; white-space: nowrap; transition: background .15s, color .15s; }
    .st-tabs a:hover { color: var(--ss-text, #16233b); }
    .st-tabs a.active { background: var(--ss-surface, #fff); color: var(--ss-text, #16233b); font-weight: 600; box-shadow: 0 1px 2px rgba(15,42,67,0.08), 0 1px 1px rgba(15,42,67,0.04); }
    .st-tabs a .cnt { min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9px; background: var(--ss-border, #e2e6ec); color: var(--ss-text-2, #56606f); font-size: 11px; font-weight: 600; line-height: 18px; text-align: center; }
    .st-tabs a.active .cnt { background: var(--ss-primary, #1F8A70); color: #fff; }
    .st-field-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; color: var(--ss-muted, #8792a2); margin-bottom: 2px; }
    .st-field-value { font-weight: 600; color: var(--ss-text, #16233b); margin-bottom: 12px; }
    .st-timeline { list-style: none; padding: 0; margin: 0; position: relative; }
    .st-timeline li { position: relative; padding: 0 0 18px 30px; }
    .st-timeline li::before { content: ''; position: absolute; left: 9px; top: 20px; bottom: 0; width: 2px; background: var(--ss-border, #e7e9ee); }
    .st-timeline li:last-child::before { display: none; }
    .st-timeline .dot { position: absolute; left: 0; top: 0; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; background: var(--c, #8792a2); }
    .st-timeline .dot.todo { background: var(--ss-surface-2, #F5F7FA); border: 2px dashed var(--ss-border, #cfd5de); }
    .st-timeline .t { font-weight: 700; color: var(--ss-text, #16233b); font-size: 13px; }
    .st-timeline .m { font-size: 12px; color: var(--ss-text-2, #56606f); }
    .st-part-results { position: absolute; z-index: 30; left: 0; right: 0; top: 100%; max-height: 320px; overflow: auto; background: var(--ss-surface, #fff); border: 1px solid var(--ss-border, #e7e9ee); border-radius: 10px; box-shadow: 0 10px 30px rgba(15,42,67,0.15); margin-top: 4px; }
    .st-part-results button { display: flex; width: 100%; justify-content: space-between; gap: 10px; text-align: left; border: 0; background: transparent; padding: 9px 12px; border-bottom: 1px solid var(--ss-border, #f0f1f4); color: var(--ss-text, #16233b); }
    .st-part-results button:hover { background: var(--ss-surface-2, #F5F7FA); }
    .st-picked { border: 1px solid var(--ss-border, #e7e9ee); border-radius: 10px; padding: 10px 12px; background: var(--ss-surface-2, #F5F7FA); }
</style>
