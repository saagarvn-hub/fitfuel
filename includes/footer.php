<?php
// ============================================
// MAIN FOOTER
// ============================================
?>
    </div><!-- .main-content -->
</div><!-- #app -->

<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
<script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            var loader = document.getElementById('loader');
            var app = document.getElementById('app');
            if (loader) loader.classList.add('hidden');
            if (app) app.style.opacity = '1';
        }, 300);
    });
    
    document.querySelectorAll('.progress-fill[data-pct]').forEach(function(el) {
        var pct = Math.min(parseFloat(el.dataset.pct) || 0, 100);
        setTimeout(function() { el.style.width = pct + '%'; }, 100);
        if (parseFloat(el.dataset.pct) > 100) el.classList.add('over');
    });
</script>
</body>
</html>