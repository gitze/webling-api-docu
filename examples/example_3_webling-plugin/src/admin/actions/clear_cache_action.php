<?php
function webling_clear_cache_action() {
    webling_clear_cache();
    wp_redirect(admin_url('/admin.php?page=webling_page_settings'));
    exit;
}
