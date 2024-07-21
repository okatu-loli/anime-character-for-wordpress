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
            <div class="card">
                <div class="card-image">
                    <div class="loading-overlay">
                        <div class="loading-animation">
                            <div class="circle"></div>
                            <p>少女祈祷中...</p>
                        </div>
                    </div>
                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_html($character_name); ?>" onload="hideLoading(this)" onerror="hideLoading(this)">
                    <div class="name-overlay">姓名: <?php echo esc_html($character_name); ?></div>
                </div>
                <div class="card-content">
                    <p>生日: <?php echo esc_html($birthday); ?></p>
                    <p>性别: <?php echo esc_html($gender); ?></p>
                    <a href="<?php echo esc_url($moegirl_link); ?>">萌娘百科</a>
                    <p class="description"><?php echo esc_html($description); ?></p>
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
    justify-content: space-around;
    gap: 20px; /* 添加间距 */
}

.card {
    display: flex;
    border: 1px solid #ccc;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    background-color: #fff;
    width: 200px; /* 设置初始宽度 */
    max-width: 600px;
    transition: width 0.3s ease, max-width 0.3s ease;
}

.card-image {
    position: relative;
    flex: 0 0 200px;
    height: 300px; /* 设置初始高度 */
    cursor: pointer;
}

.card-image img {
    width: 100%;
    height: 100%; /* 确保图片高度填充 */
    object-fit: cover; /* 确保图片按比例填充 */
}

.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10;
}

.loading-animation {
    text-align: center;
    color: white;
}

.circle {
    width: 50px;
    height: 50px;
    border: 5px solid transparent;
    border-top: 5px solid #fff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.name-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    text-align: center;
    padding: 8px 0;
    font-size: 16px;
}

.card-content {
    width: 0;
    height: 100%;
    background-color: #fff;
    overflow-y: hidden;
    transition: width 0.3s ease, height 0.3s ease;
    padding: 16px;
    box-sizing: border-box;
    display: none;
}

.card-content.open {
    width: calc(100% - 200px);
    overflow-y: auto;
    display: block;
}

.card-content p {
    margin: 4px 0;
}

.card-content a {
    display: block;
    margin: 8px 0;
    color: #007bff;
    text-decoration: none;
}

.card-content a:hover {
    text-decoration: underline;
}

.description {
    margin-top: 12px;
    padding: 8px;
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}
</style>
<script>
function hideLoading(imgElement) {
    const loadingOverlay = imgElement.closest('.card-image').querySelector('.loading-overlay');
    loadingOverlay.style.display = 'none';
}

document.querySelectorAll('.card-image').forEach(cardImage => {
    cardImage.addEventListener('click', function() {
        const cardContent = cardImage.nextElementSibling;
        const card = cardImage.closest('.card');
        if (cardContent.classList.contains('open')) {
            cardContent.classList.remove('open');
            cardContent.style.display = 'none';
            card.style.width = '200px';
        } else {
            cardContent.classList.add('open');
            cardContent.style.display = 'block';
            card.style.width = '600px';
            adjustCardContentHeight(cardContent, cardImage);
        }
    });
});

function adjustCardContentHeight(cardContent, cardImage) {
    cardContent.style.height = cardImage.offsetHeight + 'px';
}

// 设置 card-content 的高度与 card-image 相同
window.addEventListener('load', () => {
    document.querySelectorAll('.card').forEach(card => {
        const cardImage = card.querySelector('.card-image');
        const cardContent = card.querySelector('.card-content');
        adjustCardContentHeight(cardContent, cardImage);
    });
});

// 窗口调整时同步高度
window.addEventListener('resize', () => {
    document.querySelectorAll('.card').forEach(card => {
        const cardImage = card.querySelector('.card-image');
        const cardContent = card.querySelector('.card-content');
        adjustCardContentHeight(cardContent, cardImage);
    });
});
</script>
    <?php
    return ob_get_clean();
}
add_shortcode('anime_characters', 'anime_character_shortcode');
?>