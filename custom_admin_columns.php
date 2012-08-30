<?php

/*
  Plugin Name: Custom Admin Columns
  Plugin URI: http://www.bunchacode.com/programming/custom-admin-columns/
  Description: allows user to add additional columns to admin posts, pages and custom post type page.
  Version: 1.3.1
  Author: Jiong Ye, updates by Voltage Creative
  Author URI: http://www.bunchacode.com, http://voltagecreative.com
  License: GPL2
 */
/*  Copyright 2011  Jiong Ye  (email : dexxaye@gmail.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// http://wordpress.org/extend/plugins/custom-admin-column/

define('CAC_VERSION', '1.3.1');
define('CAC_DIR', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'custom_admin_columns' . DIRECTORY_SEPARATOR);

$defaultColumns = array(
    'thumbnail' => 'Featured Image',
    'slug' => 'Permalink',
    'modified_date' => 'Last Modified',
);

function cac_admin_init() {
    wp_enqueue_style('custom_admin_columns.css', plugins_url('custom_admin_columns.css', __FILE__));
}

add_action('admin_init', 'cac_admin_init');

/*
 * filter Pages columns
 */

function cac_page_column_filter($columns) {
    global $defaultColumns;

    $defaultColumns['parent'] = 'Parent';
    $defaultColumns['children'] = 'Children';
	unset($defaultColumns['comments']);
	unset($defaultColumns['custom-fields']);
    return array_merge($columns, $defaultColumns);
}

add_filter('manage_pages_columns', 'cac_page_column_filter');

/*
 * filter Posts page and custom post type columns
 */

function cac_other_column_filter($columns, $type) {
    global $defaultColumns;

    switch ($type) {
        case 'post':
            break;
        default:
            break;
    }

    return array_merge($columns, $defaultColumns);
}

add_filter('manage_posts_columns', 'cac_other_column_filter', 10, 2);

/*
 * Filter Media page columns
 */

function cac_media_column_filter($columns) {
    $columns['ID'] = 'ID';
    $columns['title'] = 'Title';
    $columns['alt'] = 'Alternative Text';
    $columns['caption'] = 'Captions';
    $columns['description'] = 'Description';
    unset($columns['comments']);
    $columns['file'] = 'File URL';
    return $columns;
}

add_filter('manage_media_columns', 'cac_media_column_filter');

/*
 * output column value
 */

function cac_column_value($name, $id, $other_id='') {
	if(strpos($name,'taxonomy') === 0) {
		list($name, $taxonomy) = explode('|',$name);
	}
	if(strpos($name,'filter') === 0) {
		list($name, $filter) = explode('|',$name);
	}
    switch ($name) {
        case 'ID':
            echo $id;
            break;
        case 'slug':
            $permalink = get_permalink($id);
            echo '<a href="' . $permalink . '" target="_blank">' . $permalink . '</a>';
            break;
        case 'thumbnail':
            if (function_exists('get_the_post_thumbnail'))
                echo get_the_post_thumbnail($id, array(75, 75));
            break;
        case 'parent':
            $p = get_post($id);
            $p = get_post($p->post_parent);
            if(!empty($p))
                echo '<a href="'.get_permalink($p->ID).'">'.$p->post_title.'</a>';
            break;
        case 'children':
            echo '<ul>' . wp_list_pages(array(
                'title_li' => '',
                'child_of' => $id,
                'echo' => false,
                'depth' => 1
            )) . '</ul>';
            break;
        case 'file':
            $fileURL = wp_get_attachment_url($id);
            echo '<a href="' . $fileURL . '" target="_blank">' . $fileURL . '</a>';
            break;
        case 'alt':
            echo get_post_meta($id, '_wp_attachment_image_alt', true);
        case 'caption':
        case 'description':
            $media = get_post($id);

            if ($name == 'caption')
                echo $media->post_excerpt;
            else
                echo $media->post_content;

            break;
        case 'comment_status':
            $p = get_post($id);
            echo $p->comment_status;
            break;
        case 'ping_status':
            $p = get_post($id);
            echo $p->ping_status;
            break;
        case 'modified_date':
            $p = get_post($id);
            echo date('M j, Y g:i a', strtotime($p->post_modified));
            break;
        case 'comment_count':
            $counts = get_comment_count($id);

            echo 'Approved: ' . $counts['approved'] . '<br />' .
            'Awaiting Moderation: ' . $counts['awaiting_moderation'] . '<br />' .
            'Spam: ' . $counts['spam'] . '<br />' .
            'Total: ' . $counts['total_comments'];
            break;
		case 'taxonomy':
			$categories = get_object_term_cache( $id, $taxonomy );
			if ( false === $categories ) {
				$categories = wp_get_object_terms( $id, $taxonomy );
				wp_cache_add($id, $categories, $taxonomy.'_relationships');
			}
			if ( !empty( $categories ) ) usort( $categories, '_usort_terms_by_name' );
			else $categories = array();
			foreach ( (array) array_keys( $categories ) as $key ) {
				_make_cat_compat( $categories[$key] );
			}
			if(count($categories)) {
				foreach($categories as $cat)
					$category_names[] = $cat->name;
				echo implode(', ',$category_names);
			}
			break;
		case 'filter':
			echo apply_filters($filter, '', $id);
			break;
        default:
        	$screen = get_current_screen();
        	if($screen->id == 'users') {
        		$name = $id;
        		$id = $other_id;
        		$customs = get_user_meta($id);
        		echo $customs[$name][0];
        	}
        	else {
	        	$customs = get_post_custom($id);
	        	echo $customs[$name][0];
	        }
            break;
    }
}

add_action('manage_posts_custom_column', 'cac_column_value', 10, 2);
add_action('manage_pages_custom_column', 'cac_column_value', 10, 2);
add_action('manage_media_custom_column', 'cac_column_value', 10, 2);
add_filter('manage_users_custom_column', 'cac_column_value', 10, 3);
