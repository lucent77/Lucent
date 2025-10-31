<?php
/**
 * Swissturn Tool Management System
 * Footer Component
 */

if (!defined('SYSTEM_INIT')) {
    die('Direct access not permitted');
}
?>
    </div> <!-- Close flex container from header -->

    <!-- Main JavaScript -->
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>

    <?php if (isset($page_scripts) && is_array($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo ASSETS_URL; ?>/js/<?php echo $script; ?>.js"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
