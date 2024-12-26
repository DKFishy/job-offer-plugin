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

function job_offers_filter_form() {
    // Get all locations and technologies for the filters
    $locations = get_terms(array('taxonomy' => 'location', 'hide_empty' => false));
    $technologies = get_terms(array('taxonomy' => 'technology', 'hide_empty' => false));

    ob_start();
    ?>
    <div class="filter-section">
        <form id="job-filter-form">
            <!--<input type="text" id="job-title" name="job_title" placeholder="Job Title" value="<?php echo isset($_POST['job_title']) ? esc_attr($_POST['job_title']) : ''; ?>">-->
            
            <div class="collapsible">
                <h3>Locations</h3>
                <div class="content">
                    <?php foreach ($locations as $location) : ?>
                        <label>
                            <input type="checkbox" name="job_locations[]" value="<?php echo esc_attr($location->slug); ?>" <?php echo (isset($_POST['job_locations']) && in_array($location->slug, $_POST['job_locations'])) ? 'checked' : ''; ?>>
                            <?php echo esc_html($location->name); ?>
                        </label><br>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="collapsible">
                <h3>Technologies</h3>
                <div class="content">
                    <?php foreach ($technologies as $technology) : ?>
                        <label>
                            <input type="checkbox" name="job_technologies[]" value="<?php echo esc_attr($technology->slug); ?>" <?php echo (isset($_POST['job_technologies']) && in_array($technology->slug, $_POST['job_technologies'])) ? 'checked' : ''; ?>>
                            <?php echo esc_html($technology->name); ?>
                        </label><br>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit">Filter Jobs</button>
            <button type="button" id="clear-filters">Clear Filters</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

function job_offers_manager_list() {
    $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;

    $args = array(
        'post_type' => 'job_offer',
        'posts_per_page' => 4,
        'paged' => $paged,
    );

    if (isset($_POST['job_title']) && !empty($_POST['job_title'])) {
        $args['s'] = sanitize_text_field($_POST['job_title']);
    }

    if (isset($_POST['job_locations']) && !empty($_POST['job_locations'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'location',
            'field' => 'slug',
            'terms' => array_map('sanitize_text_field', $_POST['job_locations']),
        );
    }

    if (isset($_POST['job_technologies']) && !empty($_POST['job_technologies'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'technology',
            'field' => 'slug',
            'terms' => array_map('sanitize_text_field', $_POST['job_technologies']),
        );
    }

    $job_offers = new WP_Query($args);

    ob_start();
    ?>
    <div id="job-offers-container">
        <?php
        if ($job_offers->have_posts()) {
            echo '<ul>';
            while ($job_offers->have_posts()) {
                $job_offers->the_post();
                echo '<li>';
                echo '<strong>' . get_the_title() . '</strong>';
				
				$locations = get_the_terms(get_the_ID(), 'location');
                if ($locations && !is_wp_error($locations)) {
                    $location_names = wp_list_pluck($locations, 'name');
                    echo '<span class="dashicons dashicons-location"></span>' . implode(', ', $location_names) . '&nbsp;&nbsp;';
                }
				
				$technologies = get_the_terms(get_the_ID(), 'technology');
                if ($technologies && !is_wp_error($technologies)) {
                    $technology_names = wp_list_pluck($technologies, 'name');
                    echo '<span class="dashicons dashicons-lightbulb"></span>' . implode(', ', $technology_names) . '<br><br>';
                }
				
                echo get_the_content() . '<br><br>';
                

                // Details button
                echo '<a href="' . get_permalink() . '" class="button">View Details</a> ';

                // Apply button
                $apply_url = site_url('/formularz-aplikacyjny?title=' . urlencode(get_the_title()));
                echo '<a href="' . esc_url($apply_url) . '" class="button" target="_blank">Apply</a>';

                echo '</li>';
            }
            echo '</ul>';

            // Add pagination
            echo '<div id="pagination">';
            if ($paged > 1) {
                echo '<button class="prev-page" data-page="' . ($paged - 1) . '">Previous</button>';
            }
            if ($paged < $job_offers->max_num_pages) {
                echo '<button class="next-page" data-page="' . ($paged + 1) . '">Next</button>';
            }
            echo '</div>';

            wp_reset_postdata();
        } else {
            echo '<p>No job offers found.</p>';
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
}

function job_offers_shortcode() {
    ob_start();
    ?>
    <div class="job-offers-container">
        <?php echo job_offers_filter_form(); ?>
        <div id="job-results"><?php echo job_offers_manager_list(); ?></div>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('job_offers_list', 'job_offers_shortcode');

add_shortcode('job_offers_list', 'job_offers_shortcode');

function enqueue_job_offers_scripts() {
    wp_enqueue_script('job-offers-build', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), null, true);
    wp_localize_script('job-offers-build', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}

add_action('wp_enqueue_scripts', 'enqueue_job_offers_scripts');

function enqueue_job_offers_styles() {
    wp_enqueue_style('job-offers-styles', plugin_dir_url(__FILE__) . 'css/style.css');
}
add_action('wp_enqueue_scripts', 'enqueue_job_offers_styles');

function enqueue_dashicons() {
    wp_enqueue_style('dashicons');
}
add_action('wp_enqueue_scripts', 'enqueue_dashicons');


function job_offers_build() {
	parse_str($_POST['form_data'], $form_data);
	$_POST = $form_data; // Populate the $_POST array with the form data

    	echo job_offers_manager_list();
    	wp_die();
}
add_action('wp_ajax_job_offers_build', 'job_offers_build');
add_action('wp_ajax_nopriv_job_offers_build', 'job_offers_build');
