<?php
/**
 * Khởi tạo chung cho mọi trang của module QLHC.
 * Tương thích PHP 7.4 – 8.3.
 */

define('QLHC_ROOT', dirname(__DIR__));
define('QLHC_VERSION', '1.0.0');

if (!is_file(QLHC_ROOT . '/config.php')) {
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'install.php') {
        header('Location: install.php');
        exit;
    }
    $CONFIG = null;
} else {
    $CONFIG = require QLHC_ROOT . '/config.php';
}

$debug = !empty($CONFIG['debug']);
ini_set('display_errors', $debug ? '1' : '0');
error_reporting($debug ? E_ALL : (E_ALL & ~E_DEPRECATED & ~E_NOTICE));
date_default_timezone_set($CONFIG['timezone'] ?? 'Asia/Ho_Chi_Minh');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

if ($CONFIG !== null) {
    qlhc_session_start();
}
