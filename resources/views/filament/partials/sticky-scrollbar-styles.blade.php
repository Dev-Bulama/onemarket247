{{--
    Forces a persistent, always-rendered scrollbar (see Priority 8 item 20 —
    "the admin page scrollbar only becomes visible when the user reaches
    the bottom"), which is the OS/browser's auto-hiding overlay-scrollbar
    behavior rather than anything Filament's own layout controls. Filament
    already gives the dashboard fixed/sticky chrome out of the box
    (.fi-sidebar is `position: fixed`, .fi-topbar-ctn is `position: sticky`,
    .fi-sidebar-nav scrolls independently via its own overflow-y-auto) — this
    only adds the missing "always visible, classic (non-overlay) scrollbar"
    styling on top of that, site-wide, for both light and dark mode.
--}}
<style>
    html.fi {
        overflow-y: scroll;
        scrollbar-gutter: stable;
        scrollbar-width: thin;
        scrollbar-color: rgb(156 163 175) transparent;
    }

    html.fi.dark {
        scrollbar-color: rgb(75 85 99) transparent;
    }

    html.fi::-webkit-scrollbar,
    .fi-sidebar-nav::-webkit-scrollbar,
    .fi-ta-content-ctn::-webkit-scrollbar,
    .fi-modal-window::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    html.fi::-webkit-scrollbar-track,
    .fi-sidebar-nav::-webkit-scrollbar-track,
    .fi-ta-content-ctn::-webkit-scrollbar-track,
    .fi-modal-window::-webkit-scrollbar-track {
        background: transparent;
    }

    html.fi::-webkit-scrollbar-thumb,
    .fi-sidebar-nav::-webkit-scrollbar-thumb,
    .fi-ta-content-ctn::-webkit-scrollbar-thumb,
    .fi-modal-window::-webkit-scrollbar-thumb {
        background-color: rgb(156 163 175);
        border-radius: 9999px;
        border: 2px solid transparent;
        background-clip: content-box;
    }

    html.fi.dark::-webkit-scrollbar-thumb,
    html.fi.dark .fi-sidebar-nav::-webkit-scrollbar-thumb,
    html.fi.dark .fi-ta-content-ctn::-webkit-scrollbar-thumb,
    html.fi.dark .fi-modal-window::-webkit-scrollbar-thumb {
        background-color: rgb(75 85 99);
    }
</style>
