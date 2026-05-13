<?php

// phpcs:ignoreFile
if (!isset($_REQUEST['quiid'])) {
    exit;
}

define('QUIQQER_SYSTEM', true);

$dir = dirname(__FILE__, 7);

require $dir . '/header.php';

$Icons = new QUI\Icons\Handler();

QUI::getEvents()->fireEvent('onIconListLoading', [$Icons]);

$icons    = $Icons->toArray();
$iconData = $Icons->getIconData();
$cssFiles = $Icons->getCSSFiles();

$Locale = QUI::getLocale();
$lg     = 'quiqqer/core';

// Locale strings are resolved on the parent side (the iframe does not
// have reliable access to the locale cache) and passed via URL parameter.
// PHP-side resolution is kept as a fallback only.
$defaults = [
    'searchPlaceholder' => $Locale->get($lg, 'control.icons.confirm.filterIcons'),
    'noResults'         => $Locale->get($lg, 'control.icons.confirm.noResultsInfo'),
    'categoryAll'       => $Locale->get($lg, 'control.icons.confirm.category.all'),
    'previewPlaceholder' => $Locale->get($lg, 'control.icons.confirm.preview.placeholder'),
    'cssClassLabel'     => $Locale->get($lg, 'control.icons.confirm.preview.cssClass'),
    'copyHtml'          => $Locale->get($lg, 'control.icons.confirm.preview.copyHtml'),
    'copied'            => $Locale->get($lg, 'control.icons.confirm.preview.copied'),
    'cssClassHint'      => $Locale->get($lg, 'control.icons.confirm.cssClassHint'),
];

$locale = $defaults;

if (!empty($_REQUEST['locale'])) {
    $passed = json_decode((string)$_REQUEST['locale'], true);

    if (is_array($passed)) {
        foreach ($defaults as $key => $fallback) {
            if (!empty($passed[$key]) && is_string($passed[$key])) {
                $locale[$key] = $passed[$key];
            }
        }
    }
}

header_remove('X-Frame-Options');
header('X-Frame-Options: SAMEORIGIN');

?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($Locale->getCurrent()); ?>">
<head>
    <meta charset="utf-8">
    <title>Icon List</title>

    <?php foreach ($cssFiles as $file) { ?>
        <link href="<?php echo htmlspecialchars($file); ?>" rel="stylesheet" type="text/css"/>
    <?php } ?>

    <style>
        :root {
            --qip-color-accent:        #f60;
            --qip-color-accent-soft:   #ffe7d6;
            --qip-color-accent-hover:  #e65a00;
            --qip-color-border:        rgba(0, 0, 0, 0.08);
            --qip-color-border-strong: rgba(0, 0, 0, 0.16);
            --qip-color-text:          #1a1a1a;
            --qip-color-text-muted:    #888;
            --qip-color-bg:            #fff;
            --qip-color-bg-soft:       #fafafa;
            --qip-radius:              12px;
            --qip-radius-sm:           8px;
            --qip-gap:                 16px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            background: var(--qip-color-bg);
            color: var(--qip-color-text);
            font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 14px;
        }

        .qui-icon-picker {
            display: flex;
            height: 100%;
            gap: 0;
        }

        /* ---- Preview column ---- */

        .qui-icon-picker-preview {
            width: 280px;
            flex-shrink: 0;
            padding: 24px 20px;
            background-color: var(--qip-color-bg-soft);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            overflow: hidden;
            margin: 1rem;
            border-radius: 1rem;
            background-image: radial-gradient(circle at 90% 100%, color-mix(in oklab, var(--qip-color-bg-soft), black 2%), transparent 50%), radial-gradient(circle at 50% 20%, var(--qip-color-accent-soft) 0%, transparent 30%);
        }

        .qui-icon-picker-preview-icon {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            color: var(--qip-color-accent);
            font-size: 80px;
            line-height: 1;
        }

        .qui-icon-picker-preview-icon .placeholder {
            font-size: 16px;
            color: var(--qip-color-text-muted);
            padding: 0 16px;
        }

        .qui-icon-picker-preview-name {
            font-size: 20px;
            font-weight: 600;
            color: var(--qip-color-text);
            margin: 0;
            word-break: break-word;
        }

        .qui-icon-picker-preview-label {
            font-size: 13px;
            color: var(--qip-color-text-muted);
            margin: 4px 0 20px;
        }

        .qui-icon-picker-preview-class-label {
            align-self: stretch;
            text-align: left;
            font-size: 12px;
            color: var(--qip-color-text-muted);
            margin-bottom: 6px;
        }

        .qui-icon-picker-preview-class {
            align-self: stretch;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border: 1px solid var(--qip-color-border);
            border-radius: var(--qip-radius-sm);
            background: var(--qip-color-bg);
        }

        .qui-icon-picker-preview-class-text {
            flex: 1;
            font-family: 'Menlo', monospace;
            font-size: 13px;
            color: var(--qip-color-accent);
            text-align: left;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .qui-icon-picker-copy-class {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            color: var(--qip-color-text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qui-icon-picker-copy-class:hover {
            color: var(--qip-color-accent);
            background: var(--qip-color-accent-soft);
        }

        .qui-icon-picker-preview-hint {
            align-self: stretch;
            text-align: left;
            font-size: 12px;
            color: var(--qip-color-text-muted);
            margin: 8px 0 20px;
            line-height: 1.4;
        }

        .qui-icon-picker-copy-html {
            align-self: stretch;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 14px;
            border: 1px solid var(--qip-color-border-strong);
            background: var(--qip-color-bg);
            border-radius: var(--qip-radius-sm);
            cursor: pointer;
            font-size: 14px;
            color: var(--qip-color-text);
            font-family: inherit;
        }

        .qui-icon-picker-copy-html:hover {
            border-color: var(--qip-color-accent);
            color: var(--qip-color-accent);
        }

        .qui-icon-picker-copy-html.is-copied {
            border-color: var(--qip-color-accent);
            color: var(--qip-color-accent);
            background: var(--qip-color-accent-soft);
        }

        .qui-icon-picker-preview.is-empty .qui-icon-picker-preview-name,
        .qui-icon-picker-preview.is-empty .qui-icon-picker-preview-label,
        .qui-icon-picker-preview.is-empty .qui-icon-picker-preview-class-label,
        .qui-icon-picker-preview.is-empty .qui-icon-picker-preview-class,
        .qui-icon-picker-preview.is-empty .qui-icon-picker-preview-hint,
        .qui-icon-picker-preview.is-empty .qui-icon-picker-copy-html {
            visibility: hidden;
        }

        /* ---- Main column ---- */

        .qui-icon-picker-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px 20px 20px 0;
            gap: 14px;
            min-width: 0;
        }

        .qui-icon-picker-search {
            position: relative;
            flex-shrink: 0;
        }

        .qui-icon-picker-search-svg {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--qip-color-text-muted);
            pointer-events: none;
        }

        .qui-icon-picker-search-input {
            width: 100%;
            padding: 12px 80px 12px 40px;
            border: 1px solid var(--qip-color-border-strong);
            border-radius: var(--qip-radius-sm);
            font-size: 14px;
            background: var(--qip-color-bg);
            color: var(--qip-color-text);
            font-family: inherit;
            outline: none;
        }

        .qui-icon-picker-search-input:focus {
            border-color: var(--qip-color-accent);
        }

        .qui-icon-picker-search-count {
            position: absolute;
            top: 50%;
            right: 14px;
            transform: translateY(-50%);
            font-size: 12px;
            color: var(--qip-color-text-muted);
            pointer-events: none;
        }

        /* ---- Categories ---- */

        .qui-icon-picker-categories {
            position: relative;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .qui-icon-picker-cat-track {
            flex: 1;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            scroll-behavior: smooth;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .qui-icon-picker-cat-track::-webkit-scrollbar {
            display: none;
        }

        .qui-icon-picker-cat-item {
            flex-shrink: 0;
            padding: 8px 14px;
            border: 1px solid var(--qip-color-border);
            border-radius: var(--qip-radius-sm);
            background: var(--qip-color-bg);
            cursor: pointer;
            font-size: 13px;
            color: var(--qip-color-text);
            font-family: inherit;
            white-space: nowrap;
            transition: background-color .12s, border-color .12s, color .12s;
        }

        .qui-icon-picker-cat-item:hover {
            border-color: var(--qip-color-accent);
            color: var(--qip-color-accent);
        }

        .qui-icon-picker-cat-item.is-active {
            background: var(--qip-color-accent);
            border-color: var(--qip-color-accent);
            color: #fff;
        }

        .qui-icon-picker-cat-scroll {
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            display: none;
            align-items: center;
            justify-content: center;
            background: var(--qip-color-bg);
            border: 1px solid var(--qip-color-border);
            border-radius: 50%;
            cursor: pointer;
            color: var(--qip-color-text-muted);
            font-family: inherit;
        }

        .qui-icon-picker-cat-scroll:hover {
            color: var(--qip-color-accent);
            border-color: var(--qip-color-accent);
        }

        @media (hover: hover) and (pointer: fine) {
            .qui-icon-picker-cat-scroll {
                display: flex;
            }
        }

        /* ---- Grid ---- */

        .qui-icon-picker-grid {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            padding: 4px;
            align-content: start;
            padding-block: 1rem;
            mask-image: linear-gradient(to top, transparent 0rem, black 1.5rem, black calc(100% - 1.5rem), transparent);
        }

        .qui-icon-picker-tile {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 16px 8px 12px;
            border: 1px solid var(--qip-color-border);
            border-radius: var(--qip-radius-sm);
            background: var(--qip-color-bg);
            cursor: pointer;
            transition: border-color .12s, background-color .12s, color .12s;
            min-height: 96px;
            color: var(--qip-color-text);
            text-align: center;
        }

        .qip-badge {
            display: inline-block;
            font-size: 10px;
            line-height: 1;
            font-weight: 600;
            letter-spacing: .02em;
            padding: 3px 8px;
            border-radius: 999px;
            border: 1px solid transparent;
            white-space: nowrap;
            font-family: inherit;
        }

        .qip-badge[data-variant="muted"] {
            background: rgba(0, 0, 0, 0.06);
            color: var(--qip-color-text-muted);
            border-color: rgba(0, 0, 0, 0.06);
        }

        .qip-badge[data-variant="accent"] {
            background: var(--qip-color-accent-soft);
            color: var(--qip-color-accent);
            border-color: var(--qip-color-accent-soft);
        }

        .qui-icon-picker-tile .qip-badge {
            position: absolute;
            top: 6px;
            right: 6px;
        }

        .qui-icon-picker-preview-badge {
            margin-top: -16px;
            margin-bottom: 16px;
        }

        .qui-icon-picker-preview-badge:empty {
            display: none;
        }

        .qui-icon-picker-tile:hover {
            border-color: var(--qip-color-accent);
            color: var(--qip-color-accent);
        }

        .qui-icon-picker-tile.is-active {
            border-color: var(--qip-color-accent);
            background: var(--qip-color-accent-soft);
            color: var(--qip-color-accent);
        }

        .qui-icon-picker-tile-icon {
            font-size: 28px;
            line-height: 1;
            margin-bottom: 8px;
            min-height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qui-icon-picker-tile-label {
            font-size: 11px;
            color: var(--qip-color-text-muted);
            font-family: 'Menlo', monospace;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 100%;
        }

        .qui-icon-picker-tile.is-active .qui-icon-picker-tile-label {
            color: var(--qip-color-accent);
        }

        .qui-icon-picker-no-results {
            grid-column: 1 / -1;
            text-align: center;
            color: var(--qip-color-text-muted);
            padding: 40px 20px;
        }
    </style>
</head>
<body>

<div class="qui-icon-picker">

    <aside class="qui-icon-picker-preview is-empty" id="preview">
        <div class="qui-icon-picker-preview-icon" id="previewIcon">
            <span class="placeholder"><?php echo htmlspecialchars($locale['previewPlaceholder']); ?></span>
        </div>
        <p class="qui-icon-picker-preview-name" id="previewName"></p>
        <p class="qui-icon-picker-preview-label" id="previewLabel"></p>
        <div class="qui-icon-picker-preview-badge" id="previewBadge"></div>

        <div class="qui-icon-picker-preview-class-label">
            <?php echo htmlspecialchars($locale['cssClassLabel']); ?>
        </div>

        <div class="qui-icon-picker-preview-class">
            <span class="qui-icon-picker-preview-class-text" id="previewClass"></span>
            <button type="button"
                    class="qui-icon-picker-copy-class"
                    id="copyClassBtn"
                    title="<?php echo htmlspecialchars($locale['cssClassLabel']); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="currentColor"
                          d="M9.116 17q-.691 0-1.153-.462T7.5 15.385V4.615q0-.69.463-1.153T9.116 3h7.769q.69 0 1.153.462t.462 1.153v10.77q0 .69-.462 1.152T16.884 17zm0-1h7.769q.23 0 .423-.192t.192-.423V4.615q0-.23-.192-.423T16.884 4H9.116q-.231 0-.424.192t-.192.423v10.77q0 .23.192.423t.423.192m-3 4q-.69 0-1.153-.462T4.5 18.385V7.115q0-.213.143-.356T5 6.616t.357.143t.143.357v11.269q0 .23.192.423t.423.192h8.27q.213 0 .356.143t.143.357t-.143.357t-.357.143zM8.5 16V4z"/>
                </svg>
            </button>
        </div>

        <p class="qui-icon-picker-preview-hint">
            <?php echo htmlspecialchars($locale['cssClassHint']); ?>
        </p>

        <button type="button" class="qui-icon-picker-copy-html" id="copyHtmlBtn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="currentColor"
                      d="M6 16.289L1.712 12L6 7.712l.708.688L3.114 12l3.594 3.6zm4.123 3.376l-.938-.292l4.692-15.038l.958.292zM18 16.29l-.708-.689l3.595-3.6l-3.595-3.6l.708-.689L22.288 12z"/>
            </svg>
            <span id="copyHtmlText"><?php echo htmlspecialchars($locale['copyHtml']); ?></span>
        </button>
    </aside>

    <section class="qui-icon-picker-main">

        <div class="qui-icon-picker-search">
            <svg class="qui-icon-picker-search-svg"
                 xmlns="http://www.w3.org/2000/svg"
                 viewBox="0 0 24 24"
                 aria-hidden="true">
                <path fill="currentColor"
                      d="m19.485 20.154l-6.262-6.262q-.75.639-1.725.989t-1.96.35q-2.398 0-4.064-1.666Q3.808 11.898 3.808 9.5t1.666-4.064t4.064-1.667t4.065 1.667T15.269 9.5q0 1.042-.369 2.017t-.97 1.668l6.262 6.261zM9.539 14.23q1.99 0 3.36-1.37t1.37-3.361t-1.37-3.36t-3.36-1.37t-3.361 1.37t-1.37 3.36t1.37 3.36t3.36 1.37"/>
            </svg>
            <input type="text"
                   id="search"
                   class="qui-icon-picker-search-input"
                   placeholder="<?php echo htmlspecialchars($locale['searchPlaceholder']); ?>"
                   autocomplete="off"
                   autofocus/>
            <span class="qui-icon-picker-search-count" id="searchCount" aria-live="polite"></span>
        </div>

        <div class="qui-icon-picker-categories" id="categories" style="display: none;">
            <button type="button" class="qui-icon-picker-cat-scroll" data-scroll="left" aria-label="◀">‹</button>
            <div class="qui-icon-picker-cat-track" id="catTrack"></div>
            <button type="button" class="qui-icon-picker-cat-scroll" data-scroll="right" aria-label="▶">›</button>
        </div>

        <div class="qui-icon-picker-grid" id="grid"></div>

    </section>
</div>

<script>
    (function () {
        'use strict';

        var QUI_ID = <?php echo json_encode($_REQUEST['quiid']); ?>;
        var ICONS = <?php echo json_encode(array_values($icons)); ?>;
        var ICON_DATA = <?php echo json_encode($iconData); ?>;
        var LOCALE = <?php echo json_encode($locale); ?>;

        var hasIconData = ICON_DATA.length > 0;

        // Build canonical entries list. Start with the structured ICON_DATA
        // entries (label/categories/aliases/searchTerms). Then append every
        // class from the flat ICONS list that is not already covered –
        // typically v4 shim classes like fa-comment-o and entries from
        // providers that did not register addIconData(). These extras are
        // shown without category/alias metadata.
        var entries = ICON_DATA.slice();
        var seenClass = Object.create(null);
        entries.forEach(function (entry) {
            seenClass[entry['class']] = true;
        });

        ICONS.forEach(function (cls) {
            if (seenClass[cls]) {
                return;
            }
            seenClass[cls] = true;
            entries.push({
                'class':       cls,
                'label':       '',
                'categories':  [],
                'aliases':     [],
                'searchTerms': []
            });
        });

        // Precompute a lowercase search blob per entry.
        entries.forEach(function (entry) {
            var blob = (entry['class'] || '') + ' '
                     + (entry.label || '') + ' '
                     + (entry.aliases || []).join(' ') + ' '
                     + (entry.searchTerms || []).join(' ');
            entry._search = blob.toLowerCase();
        });

        // Collect category labels in their original order.
        var categoryList = [];
        if (hasIconData) {
            var seen = Object.create(null);
            entries.forEach(function (entry) {
                (entry.categories || []).forEach(function (cat) {
                    if (!seen[cat]) {
                        seen[cat] = true;
                        categoryList.push(cat);
                    }
                });
            });
            categoryList.sort();
        }

        var state = {
            selected:        null,  // entry object or null
            activeCategory:  null,  // string or null = "all"
            searchTerm:      '',
            searchTimer:     null
        };

        var elPreview      = document.getElementById('preview');
        var elPreviewIcon  = document.getElementById('previewIcon');
        var elPreviewName  = document.getElementById('previewName');
        var elPreviewLabel = document.getElementById('previewLabel');
        var elPreviewBadge = document.getElementById('previewBadge');
        var elPreviewClass = document.getElementById('previewClass');
        var elCopyClass    = document.getElementById('copyClassBtn');
        var elCopyHtml     = document.getElementById('copyHtmlBtn');
        var elCopyHtmlText = document.getElementById('copyHtmlText');
        var elSearch       = document.getElementById('search');
        var elSearchCount  = document.getElementById('searchCount');
        var elCategories   = document.getElementById('categories');
        var elCatTrack     = document.getElementById('catTrack');
        var elGrid         = document.getElementById('grid');

        // ---- Render categories ----

        function renderCategories() {
            if (!hasIconData || categoryList.length === 0) {
                return;
            }

            elCategories.style.display = '';

            var allBtn = document.createElement('button');
            allBtn.type = 'button';
            allBtn.className = 'qui-icon-picker-cat-item is-active';
            allBtn.textContent = LOCALE.categoryAll;
            allBtn.dataset.category = '';
            elCatTrack.appendChild(allBtn);

            categoryList.forEach(function (cat) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'qui-icon-picker-cat-item';
                btn.textContent = cat;
                btn.dataset.category = cat;
                elCatTrack.appendChild(btn);
            });

            elCatTrack.addEventListener('click', function (event) {
                var btn = event.target.closest('.qui-icon-picker-cat-item');
                if (!btn) return;

                elCatTrack.querySelectorAll('.qui-icon-picker-cat-item').forEach(function (b) {
                    b.classList.remove('is-active');
                });
                btn.classList.add('is-active');

                state.activeCategory = btn.dataset.category || null;
                renderGrid();
            });

            elCategories.querySelectorAll('.qui-icon-picker-cat-scroll').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var dir = btn.dataset.scroll === 'left' ? -1 : 1;
                    elCatTrack.scrollBy({ left: dir * 240, behavior: 'smooth' });
                });
            });
        }

        // ---- Render grid ----

        function entryMatches(entry) {
            if (state.activeCategory && (entry.categories || []).indexOf(state.activeCategory) === -1) {
                return false;
            }
            if (state.searchTerm && entry._search.indexOf(state.searchTerm) === -1) {
                return false;
            }
            return true;
        }

        function renderGrid() {
            elGrid.innerHTML = '';

            var frag = document.createDocumentFragment();
            var count = 0;

            for (var i = 0; i < entries.length; i++) {
                var entry = entries[i];
                if (!entryMatches(entry)) continue;

                var tile = document.createElement('div');
                tile.className = 'qui-icon-picker-tile';
                tile.dataset.index = i;
                if (state.selected === entry) {
                    tile.classList.add('is-active');
                }

                var iconWrap = document.createElement('div');
                iconWrap.className = 'qui-icon-picker-tile-icon';

                var icon = document.createElement('i');
                icon.className = entry['class'];
                icon.setAttribute('aria-hidden', 'true');
                iconWrap.appendChild(icon);

                var label = document.createElement('div');
                label.className = 'qui-icon-picker-tile-label';
                label.textContent = lastClassToken(entry['class']);

                tile.appendChild(iconWrap);
                tile.appendChild(label);

                var tileBadge = buildBadge(entry.badge);
                if (tileBadge) {
                    tile.appendChild(tileBadge);
                }

                frag.appendChild(tile);
                count++;
            }

            if (count === 0) {
                var none = document.createElement('div');
                none.className = 'qui-icon-picker-no-results';
                none.textContent = LOCALE.noResults;
                frag.appendChild(none);
            }

            elGrid.appendChild(frag);

            elSearchCount.textContent = '(' + count + ')';
        }

        function lastClassToken(cls) {
            var parts = (cls || '').split(/\s+/);
            return parts[parts.length - 1] || cls;
        }

        function buildBadge(badge) {
            if (!badge || typeof badge !== 'object' || !badge.label) {
                return null;
            }
            var span = document.createElement('span');
            span.className = 'qip-badge';
            span.dataset.variant = badge.variant || 'default';
            if (badge.title) {
                span.title = badge.title;
            }
            span.textContent = badge.label;
            return span;
        }

        // ---- Selection / preview ----

        function selectEntry(entry) {
            state.selected = entry;

            elGrid.querySelectorAll('.qui-icon-picker-tile').forEach(function (t) {
                t.classList.remove('is-active');
            });

            if (!entry) {
                elPreview.classList.add('is-empty');
                elPreviewIcon.innerHTML = '<span class="placeholder">'
                    + escapeHtml(LOCALE.previewPlaceholder) + '</span>';
                elPreviewBadge.innerHTML = '';
                return;
            }

            // mark active tile
            var activeTile = elGrid.querySelector('.qui-icon-picker-tile[data-index]');
            elGrid.querySelectorAll('.qui-icon-picker-tile').forEach(function (t) {
                var idx = parseInt(t.dataset.index, 10);
                if (entries[idx] === entry) {
                    t.classList.add('is-active');
                }
            });

            elPreview.classList.remove('is-empty');
            elPreviewIcon.innerHTML = '';
            var bigIcon = document.createElement('i');
            bigIcon.className = entry['class'];
            bigIcon.setAttribute('aria-hidden', 'true');
            elPreviewIcon.appendChild(bigIcon);

            var name = lastClassToken(entry['class']);
            elPreviewName.textContent = name;
            elPreviewLabel.textContent = entry.label && entry.label !== name ? entry.label : '';
            elPreviewClass.textContent = entry['class'];

            elPreviewBadge.innerHTML = '';
            var previewBadge = buildBadge(entry.badge);
            if (previewBadge) {
                elPreviewBadge.appendChild(previewBadge);
            }
        }

        function escapeHtml(s) {
            var div = document.createElement('div');
            div.textContent = s == null ? '' : String(s);
            return div.innerHTML;
        }

        // ---- Grid events ----

        elGrid.addEventListener('click', function (event) {
            var tile = event.target.closest('.qui-icon-picker-tile');
            if (!tile) return;
            var idx = parseInt(tile.dataset.index, 10);
            selectEntry(entries[idx]);
        });

        elGrid.addEventListener('dblclick', function (event) {
            var tile = event.target.closest('.qui-icon-picker-tile');
            if (!tile) return;
            var idx = parseInt(tile.dataset.index, 10);
            selectEntry(entries[idx]);

            var Confirm = window.parent && window.parent.QUI
                ? window.parent.QUI.Controls.getById(QUI_ID)
                : null;

            if (Confirm) {
                Confirm.submit();
            }
        });

        // ---- Search ----

        elSearch.addEventListener('input', function () {
            var value = elSearch.value.trim().toLowerCase();

            if (state.searchTimer) {
                clearTimeout(state.searchTimer);
            }

            state.searchTimer = setTimeout(function () {
                state.searchTerm = value;
                renderGrid();
            }, 200);
        });

        elSearch.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' || event.key === 'Esc') {
                event.preventDefault();
                elSearch.value = '';
                state.searchTerm = '';
                renderGrid();
            }
        });

        // ---- Copy actions ----

        function copyToClipboard(text, btn) {
            var done = function () {
                if (!btn) return;
                btn.classList.add('is-copied');
                if (elCopyHtmlText && btn === elCopyHtml) {
                    var originalText = elCopyHtmlText.textContent;
                    elCopyHtmlText.textContent = LOCALE.copied;
                    setTimeout(function () {
                        btn.classList.remove('is-copied');
                        elCopyHtmlText.textContent = originalText;
                    }, 1200);
                } else {
                    setTimeout(function () {
                        btn.classList.remove('is-copied');
                    }, 1200);
                }
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done, function () {
                    fallbackCopy(text);
                    done();
                });
            } else {
                fallbackCopy(text);
                done();
            }
        }

        function fallbackCopy(text) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) { /* ignore */ }
            document.body.removeChild(ta);
        }

        elCopyClass.addEventListener('click', function () {
            if (!state.selected) return;
            copyToClipboard(state.selected['class'], elCopyClass);
        });

        elCopyHtml.addEventListener('click', function () {
            if (!state.selected) return;
            var html = '<i class="' + state.selected['class'] + '" aria-hidden="true"></i>';
            copyToClipboard(html, elCopyHtml);
        });

        // ---- Bridge to parent Confirm ----

        window.getSelected = function () {
            return state.selected ? [state.selected['class']] : [];
        };

        // ---- Init ----

        renderCategories();
        renderGrid();

        if (entries.length > 0) {
            selectEntry(entries[0]);
        }

        // Try to grab focus when running inside an iframe.
        try {
            window.focus();
            elSearch.focus();
        } catch (e) {
            // ignore – Confirm.js will also call focus() on load
        }
    })();
</script>

</body>
</html>
