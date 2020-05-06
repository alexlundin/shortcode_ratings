<?php

/**
 * Plugin Name: Кнопка Like шорткодом by Alex Lundin
 * Description: Плагин позволяет создавать рейтинги и размещать и через шорткод как
 * Version:     1.0.2
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Alex Lundin
 */


if (!defined('WPINC')) {
    die;
}


require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/alexlundin/shortcode_ratings/',
	__FILE__,
	'shortcode_ratings_by_alex-lundin'
);

// //Optional: If you're using a private repository, specify the access token like this:
// $myUpdateChecker->setAuthentication('your-token-here');

//Optional: Set the branch that contains the stable release.
// $myUpdateChecker- > > setBranch('master');

require 'plugin-update-checker/plugin-update-checker.php';
$MyUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://alexlundin.com/wp-update-server?action=get_metadata&slug=shortcode_ratings_by_alex-lundin', //Metadata URL.
    __FILE__, //Full path to the main plugin file.
    'shortcode_ratings_by_alex-lundin' //Plugin slug. Usually it's the same as the name of the directory.
);

define('rating_path', plugin_dir_url(__FILE__));

class Rating
{
    public function __construct()
    {
        add_shortcode('rating', array($this, 'shortcodes'));
        add_action('init', array($this, 'add_rating_post'));
        add_action('admin_menu', array($this, 'rating_metabox'));
        add_action('save_post', array($this, 'save_rating'));
        add_action('wp_enqueue_scripts', array($this, 'script'));
        add_action('admin_footer', array($this, 'get_rating'));
        add_action('admin_footer', array($this, 'script_admin_popup'));
        add_filter('add_menu_classes', array($this, 'rating_show_pending'));
        add_filter('mce_external_plugins', array($this, 'rating_add_btn'));
        add_filter('mce_buttons_2', array($this, 'rating_reg_btn'));
        add_action('wp_enqueue_scripts', array($this, 'myajax_data'));
        add_action('wp_ajax_my_action', array($this, 'my_action_callback'));
        add_action('wp_ajax_nopriv_my_action', array($this, 'my_action_callback'));
    }

    public function my_action_callback()
    {
        $whatever = intval($_POST['whatever']);

        $id = sanitize_text_field($_POST['id']);
        $count = get_post_meta($id, 'count-rating', true);

        if (!$count):
            $count = 1;
        else:
            $count++;
        endif;

        update_post_meta($id, 'count-rating', $count);
        echo $count;

        // выход нужен для того, чтобы в ответе не было ничего лишнего, только то что возвращает функция
        wp_die();
    }

    public function myajax_data()
    {
        wp_localize_script('shortcode_rating_by_alex_lundin', 'myajax',
            array(
                'url' => admin_url('admin-ajax.php')
            )
        );
    }

    public function add_rating_post()
    {
        $label = array(
            'name' => __('Likes'),
            'singular_name' => __('Like'),
            'add_new' => __('Add New'),
            'add_new_item' => __('Add New Like'),
            'edit_item' => __('Edit Like'),
            'new_item' => __('Add New Like'),
            'view_item' => __('View Like'),
            'search_items' => __('Search Like'),
            'not_found' => __('Like Not Found'),
            'not_found_in_trash' => __('Like  Not Found uin Trash'),
            'all_items' => __('All Likes'),
            'item_updated' => __('Like Updated'),
            'item_published' => __('Like Published'),
            'item_published_privately' => __('Rating Published Privately'),
            'menu_name' => __('Likes')
        );
        $rating = array(
            'labels' => $label,
            'singular_name' => 'rating',
            'public' => true,
            'exclude_from_search' => true,
            'show_ui' => true,
            'publicly_queryable' => false,
            'show_in_menu' => true,
            'show_in_nav_menus' => false,
            'query_var' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'rewrite' => array('slug' => 'rating', 'with_front' => true),
            'supports' => array('title'),
            'menu_icon' => 'dashicons-chart-bar',
            'show_in_rest' => false,
            'taxonomies' => array('category')
        );
        register_taxonomy_for_object_type('category', 'rating');
        register_post_type('rating', $rating);
    }

    public $metabox = array(
        'id' => 'rating_meta',
        'title' => 'Additional Information',
        'page' => array('rating'),
        'context' => 'normal',
        'priority' => 'default',
        'fields' => array(
            array(
                'name' => 'Count Likes',
                'id' => 'count-rating',
                'type' => 'text'
            )
        )
    );

    public function rating_metabox()
    {

        foreach ($this->metabox['page'] as $item) {
            add_meta_box($this->metabox['id'], $this->metabox['title'], array($this, 'show_meta'), $this->metabox['page'], 'normal', 'high', $this->metabox);
        }
    }

    public function show_meta()
    {
        global $post;
        echo '<input type="hidden" name="rating_meta_nonce" value="' . wp_create_nonce(basename(__FILE__)) . '"/>';
        echo '<p><b>Shortcode</b> - [rating id="' . $post->ID . '"]</p>   
              <table class="form-table">';
        foreach ($this->metabox['fields'] as $field) {
            $meta = stripslashes(get_post_meta($post->ID, $field['id'], true));
            echo '<tr>',
            '<th style="width:20%;"><label for="', $field["id"], '">', $field["name"], '</label></th>',
                '<td class="field_type_' . str_replace(' ', '_', $field['type']) . '">';
            switch ($field['type']) {
                case 'text':
                    echo "<input type='text' name='", $field["id"], "' value='", $meta, "' size='30', style='width:97%'/><br/>";
                    break;
            }
            echo '<td>',
            '</tr>';
        }
        echo '</table>';
    }

    public function save_rating($post_id)
    {
        if (!wp_verify_nonce($_POST['rating_meta_nonce'], basename(__FILE__))) {
            return $post_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }
        if ('page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return $post_id;
            }
        } elseif (!current_user_can('edit_page', $post_id)) {
            return $post_id;
        }
        foreach ($this->metabox['fields'] as $field) {
            $old = get_post_meta($post_id, $field['id'], true);
            $new = $_POST[$field['id']];
            if (($new || $new === '0') && $new !== $old) {
                if (is_string($new)) {
                    $new = $new;
                }
                update_post_meta($post_id, $field['id'], $new);
            } elseif ('' == $new && $old) {
                delete_post_meta($post_id, $field['id'], $old);
            }
        }
    }

    public function shortcodes($atts)
    {
        extract(shortcode_atts(array('id' => 0), $atts));
        $count = get_post_meta($id, 'count-rating', true);
        if ($count == null) {
            $count = 0;
        }
        return '<div class="rating-wrap"><span class="rating_btn" onclick="return Change(this)"  id="' . $id . '" ><span class="icon"></span><span class="count">' . $count . '</span></span></div>';
    }

    public function script()
    {
        wp_enqueue_style('shortcode_rating_by_alex_lundin', rating_path . 'assets/css/shortcode_rating_by_alex_lundin.css');
        wp_enqueue_script('shortcode_rating_by_alex_lundin', rating_path . 'assets/js/shortcode_rating_by_alex_lundin.js');
    }

    public function script_admin_popup()
    {
        wp_enqueue_script('magnific-rating', rating_path . 'assets/magnific/jquery.magnific-popup.js', array('jquery'), '1.0', true);
        wp_enqueue_style('magnific-style-rating', rating_path . 'assets/magnific/magnific-popup.css');
        wp_enqueue_style('popup-styles-rating', rating_path . 'assets/css/window_popup.css');
    }

    public function rating_add_btn($plugin_array)
    {
        $args = array(
            'post_type' => 'rating',
            'post_status' => 'publish',
            'posts_per_page' => -1
        );
        $list_tags = get_posts($args);
        if ($list_tags) {
            $plugin_array['btn_rating'] = rating_path . 'assets/js/mce_btn.js';
        }
        return $plugin_array;
    }

    public function rating_reg_btn($btn)
    {
        array_push($btn, 'btn_rating');
        return $btn;
    }

    public function get_rating()
    {
        $args = array(
            'post_type' => 'rating',
            'post_status' => 'publish',
            'post_per_page' => -1
        );
        $list_tags = get_posts($args);

        ob_start();

        ?>

        <div id="rating-wrap" style="display:none">
            <div id="popup_rating" class="popup_table">
                <div class="rating_search_wrap">
                    <input type="text" class="search" id="rating_search" placeholder="Enter the name of the product"
                           autofocus>
                    <div class="small-text">
                        <div class="small-left">Type text in lowercase only</div>
                        <div class="small-right"></div>
                    </div>
                </div>
                <table>
                    <thead>
                    <tr class="fixed">
                        <th class="tb_first">ID</th>
                        <th class="tb_second">Title Rating</th>
                        <th class="tb_last">Category</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php

                    $c = 0;
                    foreach ($list_tags as $el) {
                        $el_name = get_the_category($el->ID);

                        echo '
                        
                        <tr class="select_rating" id=' . $el->ID . '>
                            <td class="tb_first">' . $el->ID . '</td>
                            <td class="tb_second">' . get_the_title($el->ID) . '</td>';
                        if (!empty($el_name)) {
                            foreach ($el_name as $el_n) {
                                $cat_n = $el_n->name;

                                echo '<td class="tb_last">' . $cat_n . '</td>';
                                $c++;

                            }
                        } else {
                            echo '<td class="tb_last">Без категории</td>';
                        }

                        echo '</tr>';
                    } ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        $rating = ob_get_contents();
        ob_end_clean();
        echo $rating;
    }

    public function rating_show_pending($menu)
    {
        $type = "post";
        $status = "draft";
        $num_posts = wp_count_posts($type, 'readable');
        $pending_count = 0;
        if (!empty($num_posts->$status))
            $pending_count = $num_posts->$status;
        if ($type == 'post') {
            $menu_str = 'edit.php';
        } else {
            $menu_str = 'edit.php?post_type=' . $type;
        }
        foreach ($menu as $menu_key => $menu_data) {
            if ($menu_str != $menu_data[2])
                continue;
            $menu[$menu_key][0] .= " <span class='update-plugins count-$pending_count'><span class='plugin-count'>+" . number_format_i18n($pending_count) . '</span></span>';
        }
        return $menu;
    }
}

global $rating;
$rating = new Rating();