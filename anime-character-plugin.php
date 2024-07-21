<?php
/*
Plugin Name: 动漫角色管理
Plugin URI: https://github.com/okatu-loli/anime-character-for-wordpress
Description: 一个管理动漫角色的插件，并在前端展示角色。
Version: 1.0
Author: 千石
Author URI: https://cnqs.moe
License: GPL-3.0+
License URI: https://www.gnu.org/licenses/gpl-3.0.txt
*/

// 创建自定义文章类型
function create_anime_character_cpt() {
    $labels = array(
        'name' => '动漫角色',
        'singular_name' => '动漫角色',
        'menu_name' => '动漫角色',
        'name_admin_bar' => '动漫角色',
        'add_new' => '添加新角色',
        'add_new_item' => '添加新动漫角色',
        'new_item' => '新动漫角色',
        'edit_item' => '编辑动漫角色',
        'view_item' => '查看动漫角色',
        'all_items' => '所有动漫角色',
        'search_items' => '搜索动漫角色',
        'parent_item_colon' => '父级动漫角色:',
        'not_found' => '未找到动漫角色。',
        'not_found_in_trash' => '垃圾箱中未找到动漫角色。',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => false,
        'query_var' => true,
        'rewrite' => array('slug' => 'anime-character'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title', 'editor', 'thumbnail'),
    );

    register_post_type('anime_character', $args);
}
add_action('init', 'create_anime_character_cpt');

// 添加自定义管理页面
function anime_character_admin_menu() {
    add_menu_page(
        '动漫角色管理',
        '动漫角色',
        'manage_options',
        'anime-character-admin',
        'anime_character_list_page',
        'dashicons-admin-users',
        6
    );
    add_submenu_page(
        'anime-character-admin',
        '角色列表',
        '角色列表',
        'manage_options',
        'anime-character-list',
        'anime_character_list_page'
    );
    add_submenu_page(
        'anime-character-admin',
        '添加新角色',
        '添加新角色',
        'manage_options',
        'anime-character-add',
        'anime_character_add_page'
    );
}
add_action('admin_menu', 'anime_character_admin_menu');

// 删除角色列表子菜单（避免重复）
function remove_duplicate_submenu() {
    remove_submenu_page('anime-character-admin', 'anime-character-admin');
}
add_action('admin_menu', 'remove_duplicate_submenu', 999);

// 显示角色列表页面内容
function anime_character_list_page() {
    ?>
    <div class="wrap">
        <h1>角色列表</h1>
        <a href="<?php echo admin_url('admin.php?page=anime-character-add'); ?>" class="button button-primary">添加新角色</a>
        <form method="get" action="">
            <input type="hidden" name="page" value="anime-character-list">
            <input type="text" name="s" value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>" placeholder="搜索角色">
            <select name="gender">
                <option value="">所有性别</option>
                <option value="male" <?php selected(isset($_GET['gender']) ? $_GET['gender'] : '', 'male'); ?>>男</option>
                <option value="female" <?php selected(isset($_GET['gender']) ? $_GET['gender'] : '', 'female'); ?>>女</option>
                <option value="other" <?php selected(isset($_GET['gender']) ? $_GET['gender'] : '', 'other'); ?>>其他</option>
            </select>
            <button type="submit" class="button">筛选</button>
        </form>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>角色名称</th>
                    <th>萌娘百科链接</th>
                    <th>角色图片</th>
                    <th>描述</th>
                    <th>生日</th>
                    <th>性别</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="character-list">
                <?php
                $args = array(
                    'post_type' => 'anime_character',
                    'posts_per_page' => -1,
                );

                if (isset($_GET['s']) && !empty($_GET['s'])) {
                    $args['s'] = sanitize_text_field($_GET['s']);
                }

                if (isset($_GET['gender']) && !empty($_GET['gender'])) {
                    $args['meta_query'] = array(
                        array(
                            'key' => 'anime_character_gender',
                            'value' => sanitize_text_field($_GET['gender']),
                            'compare' => '='
                        )
                    );
                }

                $query = new WP_Query($args);
                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        $character_id = get_the_ID();
                        $character_name = get_the_title();
                        $moegirl_link = get_post_meta($character_id, 'anime_character_moegirl_link', true);
                        $image = get_post_meta($character_id, 'anime_character_image', true);
                        $description = get_post_meta($character_id, 'anime_character_description', true);
                        $description_short = wp_trim_words($description, 20, '...');
                        $birthday = get_post_meta($character_id, 'anime_character_birthday', true);
                        $gender = get_post_meta($character_id, 'anime_character_gender', true);
                        $gender = ($gender === 'male') ? '男' : (($gender === 'female') ? '女' : '其他');
                        ?>
                        <tr data-id="<?php echo esc_attr($character_id); ?>">
                            <td><?php echo esc_html($character_name); ?></td>
                            <td><?php echo esc_url($moegirl_link); ?></td>
                            <td><img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($character_name); ?>" style="max-width: 50px;"></td>
                            <td><?php echo esc_html($description_short); ?></td>
                            <td><?php echo esc_html($birthday); ?></td>
                            <td><?php echo esc_html($gender); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=anime-character-add&character_id=' . $character_id); ?>" class="button">编辑</a>
                                <button class="button delete-character">删除</button>
                            </td>
                        </tr>
                        <?php
                    }
                    wp_reset_postdata();
                } else {
                    echo '<tr><td colspan="7">未找到动漫角色。</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('.delete-character').click(function() {
            if (confirm('确定要删除这个角色吗？')) {
                var row = $(this).closest('tr');
                var characterId = row.data('id');
                $.post(ajaxurl, { action: 'delete_anime_character', character_id: characterId }, function(response) {
                    if (response.success) {
                        row.remove();
                    } else {
                        alert('删除失败：' + response.data);
                    }
                });
            }
        });
    });
    </script>
    <?php
}

// 显示角色添加/编辑页面内容
function anime_character_add_page() {
    $character_id = isset($_GET['character_id']) ? intval($_GET['character_id']) : 0;
    $character_name = '';
    $moegirl_link = '';
    $image = '';
    $description = '';
    $birthday = '';
    $gender = 'male';

    if ($character_id) {
        $post = get_post($character_id);
        if ($post) {
            $character_name = $post->post_title;
            $moegirl_link = get_post_meta($character_id, 'anime_character_moegirl_link', true);
            $image = get_post_meta($character_id, 'anime_character_image', true);
            $description = get_post_meta($character_id, 'anime_character_description', true);
            $birthday = get_post_meta($character_id, 'anime_character_birthday', true);
            $gender = get_post_meta($character_id, 'anime_character_gender', true);
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo $character_id ? '编辑角色' : '添加新角色'; ?></h1>
        <form id="anime-character-form">
            <input type="hidden" id="character-id" name="character_id" value="<?php echo esc_attr($character_id); ?>">
            <table class="form-table">
                <tr>
                    <th><label for="character-name">角色名称</label></th>
                    <td><input type="text" id="character-name" name="character_name" value="<?php echo esc_attr($character_name); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="character-moegirl-link">萌娘百科链接</label></th>
                    <td><input type="url" id="character-moegirl-link" name="character_moegirl_link" value="<?php echo esc_url($moegirl_link); ?>"></td>
                </tr>
                <tr>
                    <th><label for="character-image">角色图片</label></th>
                    <td>
                        <input type="text" id="character-image" name="character_image" value="<?php echo esc_url($image); ?>">
                        <button class="button" id="upload-image-button">上传图片</button>
                    </td>
                </tr>
                <tr>
                    <th><label for="character-description">描述</label></th>
                    <td><textarea id="character-description" name="character_description" rows="5" cols="50"><?php echo esc_textarea($description); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="character-birthday">生日</label></th>
                    <td><input type="text" id="character-birthday" name="character_birthday" value="<?php echo esc_attr($birthday); ?>"></td>
                </tr>
                <tr>
                    <th><label for="character-gender">性别</label></th>
                    <td>
                        <select id="character-gender" name="character_gender">
                            <option value="male" <?php selected($gender, 'male'); ?>>男</option>
                            <option value="female" <?php selected($gender, 'female'); ?>>女</option>
                            <option value="other" <?php selected($gender, 'other'); ?>>其他</option>
                        </select>
                    </td>
                </tr>
            </table>
            <button type="submit" class="button button-primary">保存角色</button>
            <a href="<?php echo admin_url('admin.php?page=anime-character-list'); ?>" class="button">取消</a>
        </form>
    </div>
    <script>
    jQuery(document).ready(function($) {
        var mediaUploader;

        $('#upload-image-button').click(function(e) {
            e.preventDefault();
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: '选择图片',
                button: {
                    text: '选择图片'
                },
                multiple: false
            });
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#character-image').val(attachment.url);
            });
            mediaUploader.open();
        });

        $('#anime-character-form').submit(function(e) {
            e.preventDefault();
            var characterData = {
                action: 'save_anime_character',
                character_id: $('#character-id').val(),
                character_name: $('#character-name').val(),
                character_moegirl_link: $('#character-moegirl-link').val(),
                character_image: $('#character-image').val(),
                character_description: $('#character-description').val(),
                character_birthday: $('#character-birthday').val(),
                character_gender: $('#character-gender').val(),
            };
            $.post(ajaxurl, characterData, function(response) {
                if (response.success) {
                    window.location.href = '<?php echo admin_url('admin.php?page=anime-character-list'); ?>';
                } else {
                    alert('保存失败：' + response.data);
                }
            });
        });
    });
    </script>
    <?php
}

// AJAX 处理保存动漫角色
function save_anime_character() {
    $character_id = isset($_POST['character_id']) ? intval($_POST['character_id']) : 0;
    $character_name = sanitize_text_field($_POST['character_name']);
    $character_moegirl_link = esc_url_raw($_POST['character_moegirl_link']);
    $character_image = esc_url_raw($_POST['character_image']);
    $character_description = sanitize_textarea_field($_POST['character_description']);
    $character_birthday = sanitize_text_field($_POST['character_birthday']);
    $character_gender = sanitize_text_field($_POST['character_gender']);

    if ($character_id) {
        $post_id = wp_update_post(array(
            'ID' => $character_id,
            'post_title' => $character_name,
            'post_type' => 'anime_character',
        ));
    } else {
        $post_id = wp_insert_post(array(
            'post_title' => $character_name,
            'post_type' => 'anime_character',
            'post_status' => 'publish',
        ));
    }

    if (is_wp_error($post_id)) {
        wp_send_json_error($post_id->get_error_message());
    } else {
        update_post_meta($post_id, 'anime_character_moegirl_link', $character_moegirl_link);
        update_post_meta($post_id, 'anime_character_image', $character_image);
        update_post_meta($post_id, 'anime_character_description', $character_description);
        update_post_meta($post_id, 'anime_character_birthday', $character_birthday);
        update_post_meta($post_id, 'anime_character_gender', $character_gender);
        wp_send_json_success();
    }
}
add_action('wp_ajax_save_anime_character', 'save_anime_character');

// AJAX 处理删除动漫角色
function delete_anime_character() {
    $character_id = isset($_POST['character_id']) ? intval($_POST['character_id']) : 0;
    if ($character_id) {
        $result = wp_delete_post($character_id, true);
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('删除失败');
        }
    } else {
        wp_send_json_error('无效的角色ID');
    }
}
add_action('wp_ajax_delete_anime_character', 'delete_anime_character');

// 创建短代码
function anime_character_shortcode($atts) {
    ob_start();
    ?>
    <div class="anime-card-qianshi-characters">
        <?php
        $args = array(
            'post_type' => 'anime_character',
            'posts_per_page' => -1,
        );
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $character_id = get_the_ID();
                $character_name = get_the_title();
                $moegirl_link = get_post_meta($character_id, 'anime_character_moegirl_link', true);
                $image = get_post_meta($character_id, 'anime_character_image', true);
                $description = get_post_meta($character_id, 'anime_character_description', true);
                $birthday = get_post_meta($character_id, 'anime_character_birthday', true);
                $gender = get_post_meta($character_id, 'anime_character_gender', true);
                $gender = ($gender === 'male') ? '男' : (($gender === 'female') ? '女' : '其他');
                ?>
                <div class="card" onclick="window.location.href='<?php echo esc_url($moegirl_link); ?>'">
                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_html($character_name); ?>">
                    <div class="overlay">
                        <div class="name">
                            <?php echo esc_html($character_name); ?>
                        </div>
                        <div class="overlay-content">
                            <p>生日: <?php echo esc_html($birthday); ?></p>
                            <p>性别: <?php echo esc_html($gender); ?></p>
                            <p>简介: <?php echo esc_html($description); ?></p>
                        </div>
                    </div>
                </div>
                <?php
            }
            wp_reset_postdata();
        } else {
            echo '<p>未找到动漫角色。</p>';
        }
        ?>
    </div>
    <style>
        .anime-card-qianshi-characters {
            display: flex;
            flex-wrap: wrap;
            gap: 60px; /* 设置卡片之间的间距 */
            justify-content: center; /* 居中对齐 */
        }

        .card {
            width: 214px;
            height: 285.33px;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            transition: transform 0.3s;
            flex-shrink: 0; /* 防止卡片缩小 */
        }

        .card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            height: 30px; /* 初始高度只显示姓名 */
            overflow: hidden;
            transition: height 0.5s ease-in-out; /* 更优雅的动画 */
        }

        .card:hover .overlay {
            height: 100%;
        }

        .overlay-content {
            position: absolute;
            top: 30px; /* 初始状态下内容紧跟在姓名之后 */
            bottom: 2%;
            left: 0;
            right: 0;
            padding: 10px;
            max-height: calc(100% - 30px); /* 确保内容不会超出遮罩层 */
            overflow-y: auto;
        }

        .card:hover .overlay-content {
            top: 30px; /* 鼠标悬停时，内容上升到距离顶部30px的位置 */
        }

        .overlay-content p {
            margin: 5px 0;
        }

        .name {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            height: 30px;
            line-height: 30px;
            transition: top 0.5s ease-in-out, transform 0.5s ease-in-out;
        }

        .card:hover .name {
            top: 10%;
            transform: translateX(-50%) translateY(-50%);
        }

        /* 媒体查询 */
        @media (max-width: 1200px) {
            .card {
                width: 180px;
                height: 240px;
            }
        }

        @media (max-width: 992px) {
            .card {
                width: 160px;
                height: 213px;
            }
        }

        @media (max-width: 768px) {
            .card {
                width: 140px;
                height: 186px;
            }
        }

        @media (max-width: 576px) {
            .card {
                width: 120px;
                height: 160px;
            }
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('anime_character', 'anime_character_shortcode');