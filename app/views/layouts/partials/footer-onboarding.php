<?php
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if ($base === '/' || $base === '\\' || $base === '.') {
    $base = '';
}
?>
    </main>

    <script type="module" src="<?= $base ?>/assets/js/app.js"></script>
</body>
</html>
