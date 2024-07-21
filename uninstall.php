<?php
// 如果直接访问该文件，则退出
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 删除自定义文章类型 'anime_character' 的所有文章
$anime_characters = get_posts(array(
    'post_type' => 'anime_character',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($anime_characters as $character) {
    wp_delete_post($character->ID, true);
}

// 删除插件使用的所有选项
delete_option('anime_character_options');

// 删除插件使用的所有元数据
global $wpdb;
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'anime_character_%'");

?>
