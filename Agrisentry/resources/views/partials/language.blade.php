<script src="/js/system-translations.js?v=2"></script>
<script src="/js/dashboard-language.js?v=2"></script>
<style>
.system-language-control { display:flex; flex-direction:column; gap:5px; font:500 12px/1.4 sans-serif; color:var(--text-1,#183b2d); }
.system-language-control select { min-height:40px; max-width:100%; padding:8px 12px; border:1px solid var(--border,#dce5df); border-radius:8px; background:var(--bg-card,#fff); color:var(--text-1,#183b2d); font:inherit; }
.system-language-floating { position:fixed; top:12px; left:12px; z-index:100; padding:8px; border:1px solid var(--border,#dce5df); border-radius:10px; background:var(--bg-card,#fff); }
.account-nav .system-language-control { margin-right:12px; }
html:not([lang="en"]) body:has(.app) .nav-item { height:auto; min-height:48px; line-height:1.35; }
html:not([lang="en"]) body:has(.app) .goat-profile-btn { white-space:normal; }
@media(max-width:600px) { .system-language-floating { position:relative; top:auto; left:auto; margin:8px; width:max-content; } }
@media print { .system-language-control { display:none; } }
</style>
