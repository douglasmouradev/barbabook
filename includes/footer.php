  </main>
  <footer class="site-footer">
    <div class="container">
      <p>&copy; <?= date('Y') ?> BarbaBook - tdesksolutions.com.br. Agendamento para barbeiros e nail design.</p>
    </div>
  </footer>
  <?php if (!isset($base)) { if (!defined('SITE_BASE')) require_once __DIR__ . '/../config/app.php'; $base = SITE_BASE ?: ''; } ?>
  <script src="<?= $base ?>/assets/js/app.js"></script>
</body>
</html>
