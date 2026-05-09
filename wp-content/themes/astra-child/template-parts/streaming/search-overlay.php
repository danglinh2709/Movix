<?php
/**
 * Sitewide quick search overlay (opened with Ctrl/⌘+K via movie-ui.js).
 */
defined('ABSPATH') || exit;
?>
<div class="mu-modal mu-search mu-search-shell" data-mu-search aria-hidden="true">
    <div class="mu-search__backdrop" data-mu-close-search></div>
    <div class="mu-search__panel mu-search-panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Search', 'astra-child'); ?>">
        <div class="mu-searchbar">
            <span class="mu-ico-search" aria-hidden="true"></span>
            <input type="search" autocomplete="off" placeholder="<?php esc_attr_e('Titles, casts, moods…', 'astra-child'); ?>" data-mu-search-input/>
            <button type="button" class="mu-btn-close" data-mu-close-search>&times;</button>
        </div>
        <div class="mu-chip-row" data-mu-suggest></div>
        <div class="mu-search__body mu-search-results-scroll">
            <div class="mu-skel mu-grid mu-grid--dense" data-mu-skel style="display:none"></div>
            <div data-mu-search-results></div>
        </div>
    </div>
</div>
