<?php

class CourseStorm_Search {
    const COURSESTORM_SEARCH_PREFIX = 'coursestorm_search_';
    protected static $api_params_whitelist = [
        'search',
        'near_zip',
        'near_city',
        'radius',
        'featured_only',
        'currently_listed_only',
        'registrable_only',
        'page',
        'per_page',
        'sort'
    ];

    public static function network_radius_search($query) {
        $field_prefix = CourseStorm_Search::COURSESTORM_SEARCH_PREFIX;
        $search = $query->get($field_prefix . 'term');
        $location = $query->get($field_prefix . 'location');
        $radius = $query->get($field_prefix . 'radius');
        
        if (
            $query->is_main_query()
            && (strlen($location) > 0)
        ) {
            // Lookup course IDs for radius.
            if ($courseIds = self::get_courses_ids($search, $location, $radius)) {
                $query->set( 'meta_query',  array(
                    // Search for the course id stored in `post_meta`.
                    array(
                        'key'     => 'id',
                        'value'   => $courseIds,
                        'compare' => 'IN',
                    ),
                ) );
            } else {
                // Force no results
                $query->set( 'post__in', array(0) );
            }
        } elseif ( strlen( $search ) > 0 ) {
            // Fallback to standard WP search w/o radius search
            $query->set( 's', $search );
        }
    }

    public static function add_query_vars_filter( $vars ) {
        $field_prefix = CourseStorm_Search::COURSESTORM_SEARCH_PREFIX;

        $query_variables = [
            $field_prefix . 'term',
            $field_prefix . 'location',
            $field_prefix . 'radius'
        ];

        foreach ($query_variables as $var) {
            $vars[] = $var;
        }
        
        return $vars;
    }

    private static function get_api_handler() {
        $credentials = CourseStorm_Synchronize::get_credentials();

        if( !isset( $credentials['subdomain'] ) ) {
            return false;
        }

        $api = new \CourseStorm_WP_API( $credentials['subdomain'], 'live' );
        $api->setTimeoutInSeconds( 10 );

        return $api;
    }

    private static function get_courses_ids($search, $location, $radius) {
        $api = self::get_api_handler();

        $page = 1;
        $per_page = 15;
        $courses = [];

        $params = $_GET;
        $params['per_page'] = 15;
        

        $query_params = $api->getQueryParams( $params, CourseStorm_Search::COURSESTORM_SEARCH_PREFIX, self::$api_params_whitelist );

        do {
            $query_params['page'] = $page;

            $results = $api->get( '/courses', $query_params );

            if ($results && count($results) > 0) {
                $courses = array_merge($courses, $results);
            }
            $page++;
        } while ($results && count($results) > 0);
        
        if (count($courses)) {
            foreach ($courses as $course) {
                $courseIds[] = $course->id;
            }
            
            return $courseIds;
        }

        return false;
    }
}