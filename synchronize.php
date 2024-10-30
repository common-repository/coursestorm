<?php
if( !class_exists( 'CourseStorm_Admin' ) ) {
	require_once dirname( __FILE__ ). '/admin/coursestorm-options.php';
}

require_once dirname( __FILE__ ).'/lib/tasks/coursestorm-sync-catalog-task.php';
require_once dirname( __FILE__ ).'/lib/tasks/coursestorm-sync-categories-task.php';
require_once dirname( __FILE__ ).'/lib/tasks/coursestorm-sync-courses-fetch-task.php';
require_once dirname( __FILE__ ).'/lib/tasks/coursestorm-sync-courses-save-task.php';
require_once dirname( __FILE__ ).'/lib/tasks/coursestorm-sync-courses-delete-task.php';

class CourseStorm_Synchronize {
	static private $_num_per_page = 15;
	static private $_pages_per_batch = 5;

	/**
	 * Start Sync
	 *
	 * Kicks off the sync to be handled by background tasks.
	 *
	 * Supports syncing records in 3 scenarios:
	 *   1. The user is adding/updating the value on the options page
	 *   2. The hourly cron is running
	 *   3. The user triggers the sync manually on the options page
	 *
	 * @param array $args Arguments
	 */
	public static function start_sync( $args = [] ) {
		self::_clear_current_sync_crons();
		if (is_array($args) && !isset($args['run_wp_cron'])) {
			self::clean_up_sync();
		}
		self::_trigger_job('sync_catalog');
		exit;
	}

	/**
	 * Synchronize Catalog
	 * 
	 * Update our local course records.
	 * 
	 * Synchronization is based on a paging and async task system with multiple stages.
	 * 
	 * Stage 1: Fetch and save down categories
	 * Stage 2: Fetch classes (15 per page, 5 pages per job) and store in transients
	 * Stage 3: Page through transients and save them down as full-fledged post objects (1 page per job)
	 * Stage 4: Clean up
	 * 
	 * @param array $args Arguments passed from the options page.
	 */
	public static function synchronize_catalog($args = []) {
		set_transient( 'coursestorm_course_import_start_time', date('Y-m-d H:i:s'), HOUR_IN_SECONDS * 4);
		
		$previous_coursestorm_subdomain_setting = isset($args['subdomain']) ? $args['subdomain'] : $args;
		$credentials = self::get_credentials();
        $stored_coursestorm_subdomain_setting = isset( $credentials['subdomain'] ) ? $credentials['subdomain'] : null;
        // Sync CourseStorm Settings
        CourseStorm_Admin::sync_coursestorm_settings($stored_coursestorm_subdomain_setting, false);
		$was_sync_triggered_from_options_page = get_transient( 'coursestorm_sync_triggered_from_options_page' );
		$was_sync_triggered_from_plugin_activation = get_transient( 'coursestorm_sync_triggered_from_plugin_activation' );
		$was_sync_triggered_manually = get_transient( 'coursestorm_sync_triggered_manually' );
		$should_run_sync = self::check_runnable_status( $stored_coursestorm_subdomain_setting, $previous_coursestorm_subdomain_setting, $credentials, $was_sync_triggered_from_plugin_activation );

		if( $should_run_sync ) {
			// Set transient to track import status
			if( strlen( $stored_coursestorm_subdomain_setting ) > 0 ) {
				set_transient( 'coursestorm_course_import_status', 'incomplete', HOUR_IN_SECONDS * 4 );
			} else {
				delete_transient( 'coursestorm_course_import_status' );
			}
			
			// Set the transient to track import progress
			if( false === ( $course_import_progress_transient = get_transient( 'coursestorm_course_import_progress' ) ) ) {
				set_transient( 'coursestorm_course_import_progress', 0, HOUR_IN_SECONDS * 4 );
			}

			// Determine if we need to delete old records
			// because we are changing sub-domains
			if (
				'yes' === $was_sync_triggered_from_options_page
				&& self::is_changing_subdomain( $previous_coursestorm_subdomain_setting, $stored_coursestorm_subdomain_setting )
				&& ! 'yes' === $was_sync_triggered_manually
			) {
				$old_subdomain_records_deleted = self::delete_old_subdomain_records();
				if ( get_transient( 'coursestorm_sync_triggered_from_options_page' ) ) {
					delete_transient( 'coursestorm_sync_triggered_from_options_page' );
				}
			}

			// Get data from the API starting with categories
			self::_trigger_job('sync_categories');
			exit;
		} elseif ( strlen( $stored_coursestorm_subdomain_setting ) <= 0 ) {
			// Delete the records if the subdomain was removed
			$old_subdomain_records_deleted = self::delete_old_subdomain_records();
			self::clean_up_sync();
		} else {
			self::clean_up_sync();
		}
		
		return true;
	}

	public static function synchronize_categories( $args = [] ) {
		// Delete CourseStorm categories before syncing
		$termIds = get_terms( array( 'taxonomy' => 'coursestorm_categories', 'fields' => 'ids', 'hide_empty' => false ) );
		self::delete_categories($termIds);

		self::register_categories();

		// Trigger the next step in the import: download courses
		self::_trigger_job( 'fetch_courses', ['page' => 1] );
		exit;
	}

	/**
	 * Fetch a list of available courses.
	 * 
	 * @param array $args
	 * @return void
	 */
	public static function fetch_courses( $args = [] ) {
		$page = isset( $args['page'] ) ? $args['page'] : 1;

		set_transient( 'coursestorm_import_step_transient', 'fetching_courses', HOUR_IN_SECONDS );
		$api = self::get_api_handler();

		$total_courses_fetched = get_transient( 'coursestorm_import_total_courses_fetched' );
		if (!$total_courses_fetched) {
			$total_courses_fetched = 0;
		}
		
		$max_page_to_fetch = $page + self::$_pages_per_batch;

		for ($page; $page < $max_page_to_fetch; $page++) {
			$result = $api->get( '/courses?featured_only=0&per_page=' . self::$_num_per_page . '&page=' . $page );

			if ( !count($result) ) {
				// All done fetching.
				// We've gone through all pages at this point, so start saving.
				self::_trigger_job( 'save_courses', ['page' => 1] );
				exit;
			}

			if ($page == 1) {
				// Store how many total courses there are.
				set_transient( 'coursestorm_import_total_courses_to_import', $api->getLastResult()->resultsTotalAvailableItems, HOUR_IN_SECONDS * 4 );
			}

			set_transient( 'coursestorm_import_current_fetch_course_page', $page, HOUR_IN_SECONDS);
			$total_courses_fetched += count($result);
			set_transient( 'coursestorm_import_courses_raw_course_list_page_' . $page, $result, HOUR_IN_SECONDS);
			set_transient( 'coursestorm_import_total_courses_fetched', $total_courses_fetched, HOUR_IN_SECONDS);
			self::_update_progress();
		}
		
		if ( $total_courses_fetched % self::$_num_per_page == 0 ) {
			// There may be more pages to attempt.
			// Note: $page has already been incremented to the next page at this point by the "for" loop above.
			self::_trigger_job( 'fetch_courses', ['page' => $page] );
			exit;
		}
		
		// We've gone through all pages at this point, so start saving.
		self::_trigger_job( 'save_courses', ['page' => 1] );
		exit;
	}

	/**
	 * Save Courses
	 * 
	 * Loop through all retrieved courses and save them
	 * as posts with post meta.
	 *
	 * @param array $args
	 * @return bool
	 */
	public static function save_courses( $args = [] ) {
		$page = isset( $args['page'] ) ? $args['page'] : 1;

		set_transient('coursestorm_import_step_transient', 'saving_courses', HOUR_IN_SECONDS);

		$transient_key_prefix = 'coursestorm_import_courses_raw_course_list_page_';

		$total_courses_saved = get_transient( 'coursestorm_import_total_courses_saved' );
		if (!$total_courses_saved) {
			$total_courses_saved = 0;
		}

		$max_page_to_save = $page + self::$_pages_per_batch;

		for ($page; $page < $max_page_to_save; $page++) {
			$courses = get_transient( $transient_key_prefix . $page );
			
			if (!$courses) {
				// All done saving courses.
				break;
			}

			set_transient( 'coursestorm_import_current_save_course_page', $page, HOUR_IN_SECONDS);

			foreach ( $courses as $key => $course_details ) {
				$course_id = $course_details->id;
				
				// Search for any existing posts that are associated with this course ID.
				$args = array(
					'meta_key'    => 'id',
					'meta_value'  => $course_id,
					'post_type'   => 'coursestorm_class'
				);
				$posts = get_posts( $args );
				
				// If we find a match, save this post's post ID.
				$post_id = count( $posts ) > 0 ? $posts[0]->ID : 0;

				$post_details = array(
					'ID'            => $post_id, // 0 = new post, POST_ID = existing post
					'post_title'    => $course_details->name != null ? $course_details->name : '',
					'post_content'  => $course_details->description != null ? $course_details->description : '',
					'post_type'     => 'coursestorm_class',
					'post_status'   => 'publish'
				);
				
				$post_id = wp_insert_post( $post_details, true );

				// Save all of the additional potentially interesting data for the post...
				foreach( $course_details as $detail_name => $detail_value ) {
					switch ($detail_name) {
						case 'categories' :
							break;
						case 'sessions' :
							if( isset( $detail_value[0] ) ) {
								update_post_meta( $post_id, 'sessions', $detail_value );
								$first_session = $detail_value[0];
								$first_session_start_time_str = self::get_session_datetime($first_session);
								// We are storing the next and upcoming session IDs and dates
								// because the next session, may not be upcoming due to being cancelled.
								// This allows us to filter out cancelled sessions when querying for upcoming classes.
								$next_session = coursestorm_get_next_course_session($detail_value, false);
								$next_session_start_time_str = self::get_session_datetime($next_session);
								$upcoming_session = coursestorm_get_next_course_session($detail_value, true);
								$upcoming_session_start_time_str = self::get_session_datetime($upcoming_session);
								update_post_meta( $post_id, 'first_session_id', $first_session ? $first_session->id : null);
								update_post_meta( $post_id, 'first_session_date', $first_session_start_time_str );
								update_post_meta( $post_id, 'next_session_id', $next_session ? $next_session->id : null);
								update_post_meta( $post_id, 'next_session_date', $next_session_start_time_str );
								update_post_meta( $post_id, 'upcoming_session_id', $upcoming_session ? $upcoming_session->id : null );
								update_post_meta( $post_id, 'upcoming_session_date', $upcoming_session_start_time_str );
							} else {
								update_post_meta( $post_id, 'sessions', [] );
								update_post_meta( $post_id, 'first_session_id', null );
								update_post_meta( $post_id, 'first_session_date', null );
								update_post_meta( $post_id, 'next_session_id', null);
								update_post_meta( $post_id, 'next_session_date', null );
								update_post_meta( $post_id, 'upcoming_session_id', null );
								update_post_meta( $post_id, 'upcoming_session_date', null );
							}
							break;               
						default :
							update_post_meta( $post_id, $detail_name, $detail_value );
							break;            
					}
				}
				
				update_post_meta( $post_id, 'import_touched_at', date('Y-m-d H:i:s') );

				// Add this course to the appropriate categories.
				$post_categories = array();
				if( isset( $course_details->categories ) ) {
					foreach( $course_details->categories as $category_details ) {
						$post_categories[] = self::get_category_slug($category_details->url);
					}
					$post_categories = array_unique( $post_categories );
	
					wp_set_object_terms( $post_id, $post_categories, 'coursestorm_categories' );
				}
	
				// Update import progress status transient
				$total_courses_saved++;
				set_transient( 'coursestorm_import_total_courses_saved', $total_courses_saved, HOUR_IN_SECONDS );
				self::_update_progress();
			}

			// Cleanup the transient
			delete_transient( $transient_key_prefix . $page );
		}

		if (get_transient( $transient_key_prefix . $page)) {
			// There are more courses to import, work on the next page.
			// Note: $page has already been incremented to the next page at this point by the "for" loop above.
			self::_trigger_job('save_courses', ['page' => $page]);
			exit;
		}

        // We have saved all of the courses, time to clean up old ones
		self::_trigger_job('delete_old_courses');
        exit;
	}

	/**
	 * Delete old courses
	 * 
	 * Delete courses that were not touched during the last import.
	 *
	 * @param array $args
	 * @return bool
	 */
	public static function delete_old_courses( $args = [] ) {
		$import_start_time = get_transient( 'coursestorm_course_import_start_time' );
		set_transient( 'coursestorm_import_step_transient', 'deleting_old_courses', HOUR_IN_SECONDS );

		// Finally, remove any posts that we've previously saved that weren't in this batch.
		$args = array(
			'post_type'       => 'coursestorm_class',
			'meta_query' => [
				'relation' => 'OR',
				[
					'key'			=> 'import_touched_at',
					'compare'		=> 'NOT EXISTS'
				],
				[
					'key'			=> 'import_touched_at',
					'compare'		=> '<',
					'value'		=> $import_start_time
				]
			],
			'posts_per_page'  => self::$_num_per_page
		);

		$posts_to_delete = new WP_Query( $args );
		
		// If we have courses to delete, delete them.
		if ( $posts_to_delete->have_posts() ) {
			while( $posts_to_delete->have_posts() ) {
				$posts_to_delete->the_post();

				// Using "force" here deletes the meta and taxonomy as well.
				wp_delete_post( get_the_ID(), true );
			}

			// Start a new job.
            self::_trigger_job('delete_old_courses');
            exit;
		// Otherwise, we don't have anything to delete, complete the import.
		} else {
			set_transient( 'coursestorm_course_import_status', 'complete', HOUR_IN_SECONDS );
			self::clean_up_sync();
		}

		return true;
	}

	/**
	 * Registers the full category hierarchy for the available courses.
	 * 
	 * @todo This should include a job to retrigger itself for long running queries
	 */
	public static function register_categories() {
		set_transient('coursestorm_import_step_transient', 'syncing_categories', HOUR_IN_SECONDS * 4);
		$api = self::get_api_handler();
		$result = $api->get( '/categories/hierarchical' );
		self::register_category_children( 0, $result );
		
		delete_transient( 'coursestorm_import_step_transient' );
	}

	/**
	 * Recursively registers children for a given category.
	 *
	 * @param int $parent_term_id The ID of the term that will be the parent of the given category.
	 * @param array An array of subcategories that will exist in the parent.
	 */
	private static function register_category_children( $parent_term_id, $subcategory_information ) {
		foreach( $subcategory_information as $term_data ) {
			$termSlug = self::get_category_slug($term_data->url);
			// Find the parent term (or create it if it doesn't exist)
			$maybe_term_data = term_exists( $termSlug, 'coursestorm_categories', (int) $parent_term_id );
			if( !is_array( $maybe_term_data ) ) {
				$maybe_term_data = wp_insert_term( $term_data->name, 'coursestorm_categories', array( 'parent' => $parent_term_id, 'slug' => $termSlug ) );
			}

			// Associate subcategories of this term
			if( isset( $term_data->subcategories ) && count( $term_data->subcategories ) ) {
				self::register_category_children( $maybe_term_data['term_id'], $term_data->subcategories );
			}
		}
	}
	
	/**
	 * Trigger Job
	 * 
	 * Triggers a given job asynchronously with the given args.
	 * 
	 * @param string $job
	 * @param array $args
	 * @return void
	 */
	private static function _trigger_job($job, $args = []) {
		switch ($job) {
			case 'sync_catalog':
				$task = new CourseStorm_SyncCatalog_Async_Task();
				$task->launch($args);
				break;
			case 'sync_categories' :
				$task = new CourseStorm_SyncCategories_Async_Task(WP_Async_Task::LOGGED_OUT);
				$task->launch($args);
				break;
			case 'fetch_courses' :
				$task = new CourseStorm_SyncCoursesFetch_Async_Task(WP_Async_Task::LOGGED_OUT);
				$task->launch($args);
				break;
			case 'save_courses' :
				$task = new CourseStorm_SyncCoursesSave_Async_Task(WP_Async_Task::LOGGED_OUT);
				$task->launch($args);
				break;
			case 'delete_old_courses' :
				$task = new CourseStorm_SyncCoursesDelete_Async_Task(WP_Async_Task::LOGGED_OUT);
				$task->launch($args);
				break;
		}
	}

	private static function _update_progress() {
		// Using the info we have about the status of the fetch and the save processes,
		// update the progress to indicate our relative placement in the process.
		$total_courses_to_import = get_transient( 'coursestorm_import_total_courses_to_import' );
		if (!$total_courses_to_import) {
			$total_courses_to_import = 0;
		}
		
		$total_courses_fetched = get_transient( 'coursestorm_import_total_courses_fetched' );
		if (!$total_courses_fetched) {
			$total_courses_fetched = 0;
		}
		
		$total_courses_saved = get_transient( 'coursestorm_import_total_courses_saved' );
		if (!$total_courses_saved) {
			$total_courses_saved = 0;
		}
		
		$percent_complete = ($total_courses_fetched + $total_courses_saved) / ($total_courses_to_import * 2);	  
		set_transient('coursestorm_course_import_progress', $percent_complete, HOUR_IN_SECONDS * 4);
	}

	private static function _clear_current_sync_crons() {
		wp_clear_scheduled_hook('coursestorm_sync_catalog');
	}

	/**
	 * Clean up transients
	 *
	 * @return void
	 */
	public static function clean_up_sync() {
		delete_transient( 'coursestorm_course_import_start_time' );
		delete_transient( 'coursestorm_course_import_progress' );
		delete_transient( 'coursestorm_sync_triggered_from_ajax_call' );
		delete_transient( 'coursestorm_sync_triggered_manually' );
		delete_transient( 'coursestorm_import_step_transient' );
		delete_transient( 'coursestorm_import_current_fetch_course_page' );
		delete_transient( 'coursestorm_import_current_save_course_page' );
		delete_transient( 'coursestorm_sync_triggered_from_options_page' );
		delete_transient( 'coursestorm_import_total_courses_to_import' );
		delete_transient( 'coursestorm_import_total_courses_fetched' );
		delete_transient( 'coursestorm_import_total_courses_saved' );

		flush_rewrite_rules();
	}

	/**
	 * Run WP Cron
	 * 
	 * Trigger a one off cron instance of coursestorm_sync
	 * 
	 * @param array $args
	 * @return bool|null $status
	 */
	public static function run_wp_cron($args = []) {
		$is_cron_running = self::_is_in_progress();
		$status = false;

		if (!$is_cron_running) {
			$status = true;
			$plugin_options = self::get_credentials();
			$hook = 'coursestorm_sync';

			$subdomain = isset( $args['subdomain'] ) ? $args['subdomain'] : $args;

			// Clean up an remnance from the previous cron
			self::_clear_current_sync_crons();
			self::clean_up_sync();
			
			// Only run the cron if we are updating the sub
			if ( $plugin_options['subdomain'] != $subdomain ) {
				set_transient( 'coursestorm_course_import_status', 'incomplete', HOUR_IN_SECONDS );
			
				if( wp_doing_ajax() ) {
					set_transient( 'coursestorm_sync_triggered_from_ajax_call', 'yes', HOUR_IN_SECONDS);
					
					if (!empty($_POST['manual'])) {
						set_transient( 'coursestorm_sync_triggered_manually', 'yes', HOUR_IN_SECONDS);
					}
				}
				set_transient( 'coursestorm_sync_triggered_from_options_page', 'yes', HOUR_IN_SECONDS);
				
				if (is_array($args)) {
					$args['run_wp_cron'] = true;
				}
				self::start_sync($args);

				// If we changed the cron schedule, unschedule the previous event
				// and create a new event with the new schedule.
				if ( !empty( $plugin_options['cron_schedule'] && !empty($args['cron_schedule']) ) && $plugin_options['cron_schedule'] != $args['cron_schedule'] ) {
					if ( $timestamp = wp_next_scheduled( $hook ) ) {
						wp_unschedule_event( $timestamp, $hook );
					}
	
					wp_schedule_event( time(), $plugin_options['cron_schedule'], $hook );
				}
			}
		}

		return $status;
	}

	/**
	 * Check Runnable Status
	 * 
	 * Check whether the sync should run or not
	 * 
	 * @param string $stored option value that is stored in the database
	 * @param string $passed option value that is passed from the options page
	 * @param array $credentials CourseStorm API Credentials
	 * @param string|null $triggered_from_plugin_activation Was the sync was triggered from the plugin activation?
	 * @return bool
	 */
	private static function check_runnable_status( $stored, $passed, $credentials, $triggered_from_plugin_activation = null ) {
		// If the stored coursestorm subdomain setting
		// is already set, then this is not a new setup
		$is_newly_setup_or_updating = self::is_newly_setup($stored);

		// If the passed subdomain value does not equal
		// the stored subdomain value, then we are changing
		// subdomains
		$is_changing_subdomain = self::is_changing_subdomain($passed, $stored);

		$has_subdomain = isset( $credentials['subdomain'] ) && strlen( $credentials['subdomain'] ) > 0 ? true : false;
		
		$sync_triggered_from_plugin_activation = 'yes' === $triggered_from_plugin_activation ? true : false;

		return $has_subdomain && ( $is_newly_setup_or_updating || $is_changing_subdomain ) ? true : false;
	}

	/**
	 * Is Newly Setup
	 * 
	 * Check if the configuration is newly setup
	 * 
	 * @param string $value option value that is stored in the database
	 * @return bool
	 */
	private static function is_newly_setup($value) {
		return isset( $value ) || empty( $value ) ? false : true;
	}

	private static function _is_in_progress() {
		$status = get_transient('coursestorm_course_import_status');

		return (!empty($status) && $status != 'complete');
	}
	
	/**
	 * Is Changing Subdomain
	 * 
	 * Check if we are changing subdomains
	 * 
	 * @param string $passed option value that is passed from the options page
	 * @param string $stored option value that is stored in the database
	 * @return bool
	 */
	public static function is_changing_subdomain($passed, $stored) {
		return ( $passed != $stored ) ? true : false;
	}

	private static function delete_old_subdomain_records() {
		// Search for any existing posts that are associated with this custom post type.
		$args = array(
			'nopaging' => true,
			'post_type'   => 'coursestorm_class',
		);
		$posts = get_posts( $args );
		$terms = get_terms( 'coursestorm_categories', array( 'fields' => 'ids', 'hide_empty' => false ) );

		$posts_deleted = self::delete_posts( $posts );
		$categories_deleted = self::delete_categories( $terms );

		return $posts_deleted && $categories_deleted ? true : false;
	}

	private static function delete_categories($terms) {
		foreach ( $terms as $value ) {
			wp_delete_term( $value, 'coursestorm_categories' );
		}

		return true;
	}

	private static function delete_posts($posts) {
		foreach( $posts as $post ) {
			wp_delete_post( $post->ID, true );
		}

		return true;
	}

	/**
	 * Fetch our credentials from the options table.
	 *
	 * @return array
	 */
	public static function get_credentials() {
		return (array) get_option( 'coursestorm-settings' );
	}

	/**
	 * Fetch an instantiated instance of our API client.
	 */
	private static function get_api_handler() {
		$credentials = self::get_credentials();
		if( !isset( $credentials['subdomain'] ) ) {
			return false;
		}

		$api = new \CourseStorm_WP_API( $credentials['subdomain'], COURSESTORM_ENVIRONMENT );
		$api->setTimeoutInSeconds( 10 );

		return $api;
	}

	/**
	 * Get session datetime
	 *
	 * @param object $session
	 * @return null|datetime The datetime of the next session
	 */
	private static function get_session_datetime( $session = null ) {
		if ( !empty($session->start_date ) ) {
			$start_time = !empty( $session->start_time ) ? $session->start_time : '00:00:00';
			$session_datetime = $session->start_date . ' ' . $start_time;
			if ( trim($session_datetime) ) {
				return $session_datetime;
			}
		}
		return null;
	}

	/**
	 * Get Category Slug
	 *
	 * @param string $category_url The category url.
	 * @return string The category slug.
	 */
	private static function get_category_slug( string $category_url ) {
		// Get the slug for the category based on the CourseStorm category URL
		preg_match( '/^.+\/(.+)$/', $category_url, $matches );
		return $matches[1];
	}
}
