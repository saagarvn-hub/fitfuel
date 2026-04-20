<?php
// ============================================
// MAIN FOOTER
// ============================================
// Get JS version for cache busting
$js_file = $_SERVER['DOCUMENT_ROOT'] . '/assets/js/script.js';
$js_version = file_exists($js_file) ? filemtime($js_file) : '1';
?>
    </div><!-- .main-content -->
</div><!-- #app -->

<script src="/assets/js/script.js?v=<?= $js_version ?>"></script>
<script>
    // Auto-hide loader and show app
    window.addEventListener('load', function() {
        setTimeout(function() {
            var loader = document.getElementById('loader');
            var app = document.getElementById('app');
            if (loader) loader.classList.add('hidden');
            if (app) app.style.opacity = '1';
        }, 300);
    });
    
    // Animate progress bars
    document.querySelectorAll('.progress-fill[data-pct]').forEach(function(el) {
        var pct = Math.min(parseFloat(el.dataset.pct) || 0, 100);
        setTimeout(function() { el.style.width = pct + '%'; }, 100);
        if (parseFloat(el.dataset.pct) > 100) el.classList.add('over');
    });
</script>
</body>
</html>