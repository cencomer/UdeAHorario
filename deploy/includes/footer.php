</main>
<?php include __DIR__ . '/tabbar.php'; ?>
<script src="/js/app.js"></script>
<script src="/js/pdf.js"></script>
<script>
// Registrar Service Worker para PWA
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js').catch(function(){});
}
</script>
</body>
</html>
