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
    $locations = get_terms(array('taxonomy' => 'location', 'hide_empty' => false));
    $technologies = get_terms(array('taxonomy' => 'technology', 'hide_empty' => false));
	
	$total_offers = count_job_offers();

    ob_start();
    ?>
    <div class="filter-section">
        <form id="job-filter-form">
            <!--<input type="text" id="job-title" name="job_title" placeholder="Job Title" value="<?php echo isset($_POST['job_title']) ? esc_attr($_POST['job_title']) : ''; ?>">-->
			
			<div class="filter-top">
				<span style="font-weight: bold;">Filtry</span>
				<a href="#" id="clear-filters" class="text-only">Resetuj</a>
			</div>
            
            <div class="collapsible">
                <span style="font-weight: bold;">Lokacje<i class="fas fa-chevron-down"></i></span>
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
                <span style="font-weight: bold;">Technologie<i class="fas fa-chevron-down"></i></span>
                <div class="content">
                    <?php foreach ($technologies as $technology) : ?>
                        <label>
                            <input type="checkbox" name="job_technologies[]" value="<?php echo esc_attr($technology->slug); ?>" <?php echo (isset($_POST['job_technologies']) && in_array($technology->slug, $_POST['job_technologies'])) ? 'checked' : ''; ?>>
                            <?php echo esc_html($technology->name); ?>
                        </label><br>
                    <?php endforeach; ?>
                </div>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

function job_offers_manager_list() {
    $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;

	$total_offers = count_job_offers($filters);
	
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
		<p id="job-offer-count"><?php echo $total_offers; ?> offers found</p>
        <?php
        if ($job_offers->have_posts()) {
            echo '<ul>';
            while ($job_offers->have_posts()) {
                $job_offers->the_post();
                echo '<li>';
                echo '<strong>' . get_the_title() . '</strong>';
				
				echo '<div class="offer-tech-loc">';
				$locations = get_the_terms(get_the_ID(), 'location');
                if ($locations && !is_wp_error($locations)) {
                    $location_names = wp_list_pluck($locations, 'name');
                    echo '<span class="dashicons dashicons-location"></span>' . implode(', ', $location_names) . '&nbsp;&nbsp;';
                }
				
				$technologies = get_the_terms(get_the_ID(), 'technology');
                if ($technologies && !is_wp_error($technologies)) {
                    $technology_names = wp_list_pluck($technologies, 'name');
                    echo '<span class="dashicons dashicons-lightbulb"></span>' . implode(', ', $technology_names) . '<br>';
                }
				echo '</div>';
				
                echo '<div class="offer-description">' . get_the_content() . '</div>';
                
				echo '<div class="offer-buttons">';
                $apply_url = site_url('/formularz-aplikacyjny?title=' . urlencode(get_the_title()));
                echo '<a href="' . esc_url($apply_url) . '" style="font-weight: bold;" class="button offer apply" target="_blank">Aplikuj</a>';
				echo '<a href="' . get_permalink() . '" style="font-weight: bold;" class="button offer details">Szczegóły</a> ';
				echo '</div>';

                echo '</li>';
            }
            echo '</ul>';

            // Add pagination
            $total_pages = $job_offers->max_num_pages;
            if ($total_pages > 1) {
                $current_page = max(1, $paged);
                echo '<div id="pagination">';
                if ($current_page > 3) {
                    echo '<button class="page-numbers" data-page="1" style="font-weight: bold;">1</button>';
                    if ($current_page > 4) {
                        echo '<span class="dots">...</span>';
                    }
                }

                for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
                    if ($i == $current_page) {
                        echo '<button class="page-numbers current" style="font-weight: bold;" data-page="' . $i . '">' . $i . '</button>';
                    } else {
                        echo '<button class="page-numbers" style="font-weight: bold;" data-page="' . $i . '">' . $i . '</button>';
                    }
                }

                if ($current_page < $total_pages - 2) {
                    if ($current_page < $total_pages - 3) {
                        echo '<span class="dots" style="font-weight: bold;">...</span>';
                    }
                    echo '<button class="page-numbers" style="font-weight: bold;" data-page="' . $total_pages . '">' . $total_pages . '</button>';
                }
                echo '</div>';
            }

            wp_reset_postdata();
        } else {
            echo '<p>No job offers found.</p>';
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
}

function count_job_offers($filters = []) {
    $args = array(
        'post_type' => 'job_offer',
        'posts_per_page' => -1, // Get all posts
    );

    if (!empty($filters['job_title'])) {
        $args['s'] = sanitize_text_field($filters['job_title']);
    }

    if (!empty($filters['job_locations'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'location',
            'field' => 'slug',
            'terms' => array_map('sanitize_text_field', $filters['job_locations']),
        );
    }

    if (!empty($filters['job_technologies'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'technology',
            'field' => 'slug',
            'terms' => array_map('sanitize_text_field', $filters['job_technologies']),
        );
    }

    $job_offers = new WP_Query($args);
    return $job_offers->found_posts; // Return the count of posts found
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

function enqueue_job_offers_scripts() {
    wp_enqueue_script('job-offers-build', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), null, true);
    wp_localize_script('job-offers-build', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}

add_action('wp_enqueue_scripts', 'enqueue_job_offers_scripts');

function enqueue_job_offers_styles() {
    wp_enqueue_style('job-offers-styles', plugin_dir_url(__FILE__) . 'css/style.css');
	wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
	wp_enqueue_style('open-sans-font', 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap', false);
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
