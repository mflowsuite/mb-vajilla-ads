<?php
// MB Vajilla Panel — Configuration
// NEVER expose this file publicly. Protected by .htaccess.

// === Panel login ===
define('PANEL_USER', 'lorena');
define('PANEL_PASS_HASH', '$2y$10$v0XLHKasHnsr1jsi7NA6/uDoeL9CvT60NgHG23t4CyXk1EmED.KB2');
define('SESSION_TIMEOUT', 28800); // 8 hours in seconds

// === WordPress REST API ===
define('WP_BASE_URL', 'https://www.mbvajilla.com.ar');
define('WP_API_USER', 'martinbauni@gmail.com');
define('WP_API_PASS', 'ld7R EqYd PAMV 1OcR JgWH sQ6x');
define('WP_AUTH_HEADER', 'Basic ' . base64_encode(WP_API_USER . ':' . WP_API_PASS));

// === Translation ===
define('MYMEMORY_EMAIL', 'bazarcotidiano@gmail.com');

// === CSRF ===
define('CSRF_TOKEN_LENGTH', 32);
