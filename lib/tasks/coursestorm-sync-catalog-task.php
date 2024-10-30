<?php
	
require_once(dirname(__FILE__).'/../wp-async-task.php');

class CourseStorm_SyncCatalog_Async_Task extends WP_Async_Task {

	protected $action = 'coursestorm_sync_catalog';

	/**
	 * Prepare data for the asynchronous request
	 *
	 * @throws Exception If for any reason the request should not happen
	 *
	 * @param array $data An array of data sent to the hook
	 *
	 * @return array
	 */
	protected function prepare_data( $data ) {		
		return [];
	}

	/**
	 * Run the async task action
	 */
	protected function run_action() {
		do_action( 'wp_async_task_'.preg_replace('/nopriv_/', '', $this->action) );
	}

}