<?php
/**
 * Plugin Name: Job Offers Plugin
 * Description: A plugin to manage job offers.
 * Version: 1.0
 * Author: Damian Wasiak
 */

if (!defined('ABSPATH')) {
    exit;
}

function job_offers_manager_post_type() {
   register_post_type('job_offer',
       array(
           'labels'      => array(
               'name'          => __('Job Offers', 'textdomain'),
               'singular_name' => __('Job Offer', 'textdomain'),
           ),
           'public'      => true,
           'has_archive' => true,
           'supports'    => array('title', 'editor', 'custom-fields'),
           'menu_icon'   => 'dashicons-portfolio',
       )
   );
}
add_action('init', 'job_offers_manager_post_type');

function job_offers_manager_taxonomies() {
   register_taxonomy('location', 'job_offer', array(
       'label'        => __('Locations', 'textdomain'),
       'rewrite'      => array('slug' => 'location'),
       'hierarchical' => true,
   ));

   register_taxonomy('technology', 'job_offer', array(
       'label'        => __('Technologies', 'textdomain'),
       'rewrite'      => array('slug' => 'technology'),
       'hierarchical' => true,
   ));
}
add_action('init', 'job_offers_manager_taxonomies');

function job_offers_manager_list() {
    $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;

    // Get all locations and technologies for the filters
    $locations = get_terms(array('taxonomy' => 'location', 'hide_empty' => false));
    $technologies = get_terms(array('taxonomy' => 'technology', 'hide_empty' => false));
	
    $args = array(
        'post_type' => 'job_offer',
        'posts_per_page' => 4,
        'paged' => $paged,
    );

    $job_offers = new WP_Query($args);
    $output = '<div id="job-offers-container">';
	
    if ($job_offers->have_posts()) {
        $output .= '<ul>';
        while ($job_offers->have_posts()) {
            $job_offers->the_post();
            $output .= '<li>';
            $output .= '<strong>' . get_the_title() . '</strong><br>';
            $output .= get_the_content() . '<br>';

            // Get location terms
            $locations = get_the_terms(get_the_ID(), 'location');
            if ($locations && !is_wp_error($locations)) {
                $location_names = wp_list_pluck($locations, 'name');
                $output .= 'Location: ' . implode(', ', $location_names) . '<br>';
            }

            // Get technology terms
            $technologies = get_the_terms(get_the_ID(), 'technology');
            if ($technologies && !is_wp_error($technologies)) {
                $technology_names = wp_list_pluck($technologies, 'name');
                $output .= 'Technologies: ' . implode(', ', $technology_names) . '<br>';
            }

            // Details button
            $output .= '<a href="' . get_permalink() . '" class="button">View Details</a> ';
            
            // Apply button
            $apply_url = site_url('/formularz-aplikacyjny?title=' . urlencode(get_the_title()));
            $output .= '<a href="' . esc_url($apply_url) . '" class="button" target="_blank">Apply</a>';

            $output .= '</li>';
        }
        $output .= '</ul>';

        // Add pagination
        $output .= '<div id="pagination">';
        if ($paged > 1) {
            $output .= '<button class="prev-page" data-page="' . ($paged - 1) . '">Previous</button>';
        }
        if ($paged < $job_offers->max_num_pages) {
            $output .= '<button class="next-page" data-page="' . ($paged + 1) . '">Next</button>';
        }
        $output .= '</div>';

        wp_reset_postdata();
    } else {
        $output .= '<p>No job offers found.</p>';
    }

    $output .= '</div>'; // Close container

    return $output;
}

add_shortcode('job_offers_list', 'job_offers_manager_list');

function enqueue_job_offers_scripts() {
    wp_enqueue_script('job-offers-pagination', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), null, true);
    wp_localize_script('job-offers-pagination', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}

add_action('wp_enqueue_scripts', 'enqueue_job_offers_scripts');

function job_offers_pagination() {
	echo do_shortcode('[job_offers_list]');
    wp_die();
}
add_action('wp_ajax_job_offers_pagination', 'job_offers_pagination');
add_action('wp_ajax_nopriv_job_offers_pagination', 'job_offers_pagination');