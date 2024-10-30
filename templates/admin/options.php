
<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<a href="<?php echo rtrim(get_bloginfo('wpurl'), '/'); ?>/classes/" class="page-title-action" target="_blank">View Catalog</a>
    <p>Welcome to CourseStorm for WordPress! To get started, enter the URL of your CourseStorm catalog below.</p>

	<div id="poststuff">

		<div id="post-body" class="metabox-holder columns-2">

			<!-- main content -->
			<div id="post-body-content">

				<div class="meta-box-sortables ui-sortable">

					<div class="postbox">
						<h2 class=""><span><?php esc_attr_e( 'Settings' ); ?></span></h2>

						<div class="inside">
                            <form action="options.php" method="post" class="settings-form" data-redirect="<?php echo add_query_arg( ['page' => 'options_coursestorm_api'], admin_url( 'options-general.php' ) ); ?>">
                                <?php
                                settings_fields( 'coursestorm' );
                                do_settings_sections( 'coursestorm' );
                                submit_button();
                                ?>
                            </form>
						</div>
						<!-- .inside -->

					</div>
					<!-- .postbox -->

				</div>
				<!-- .meta-box-sortables .ui-sortable -->

			</div>
			<!-- post-body-content -->

			<!-- sidebar -->
			<div id="postbox-container-1" class="postbox-container">

				<div class="meta-box-sortables">

					<div class="postbox">
                        <h2 class=""><span><?php esc_attr_e( 'Sync Status' ); ?></span></h2>

						<div class="inside">
							<p><?php echo $cron_schedule_string; ?></p>
							<?php if( isset( $subdomain ) ) : ?>
							<p>Next sync occurs in <strong><?php echo __($time_to_next_cron); ?></strong></p>
							<?php endif; ?>
							<p><input class="button-primary" id="manually-trigger-cs-sync" type="submit" name="coursestorm-manual-sync" value="<?php esc_attr_e( 'Sync now' ); ?>" data-nonce=<?php echo wp_create_nonce("coursestorm_manual_sync_nonce"); ?><?php if( ! isset( $subdomain ) ) : ?> disabled="disabled"<?php endif; ?> /><span class="load-spinner"><img src="/wp-admin/images/wpspin_light-2x.gif" /></span></p>
						</div>
						<!-- .inside -->

					</div>
					<!-- .postbox -->

					<div class="postbox">
						<h2 class=""><span><?php esc_attr_e( 'Log in to CourseStorm Admin' ); ?></span></h2>
						
						<div class="inside">
							<?php if( isset( $subdomain ) ) : ?>
							<p>To manage your class catalog, log into CourseStorm by clicking the button below.</p>
							<p><a href="https://<?php echo get_option('coursestorm-settings')['subdomain']; ?>.coursestorm.com/#/admin/logIn" target="_blank" class="button-primary" id="coursestorm-manage-site">Log In <span class="dashicons dashicons-external"></span></a></p>
							<?php else : ?>
							<p>If you do not have a CourseStorm catalog yet, <a href="<?php echo COURSESTORM_SIGNUP_URL; ?>" target="_blank">you can start a free trial today!</a>
							<?php endif; ?>
						</div>
						<!-- .inside -->

					</div>
					<!-- .postbox -->

					<?php if( isset( $subdomain ) ) : ?>
						<div class="postbox">
							<h2 class=""><span><?php esc_attr_e( 'CourseStorm Settings' ); ?></span></h2>

							<div class="inside">
								<p>If you have changed your CourseStorm settings and are not seeing the changes reflected on your website, you can sync your setting manually.</p>
								<p><input class="button-primary" id="manually-trigger-cs-settings-sync" type="submit" name="coursestorm-manual-settings-sync" value="<?php esc_attr_e( 'Sync CourseStorm settings' ); ?>" data-nonce=<?php echo wp_create_nonce("coursestorm_manual_settings_sync_nonce"); ?><?php if( ! isset( $subdomain ) ) : ?> disabled="disabled"<?php endif; ?> data-redirect="<?php echo add_query_arg( ['page' => 'options_coursestorm_api'], admin_url( 'options-general.php' ) ); ?>" /><span class="load-spinner"><img src="/wp-admin/images/wpspin_light-2x.gif" /></span></p>
							</div>
							<!-- .inside -->

						</div>
						<!-- .postbox -->
					<?php endif; ?>

				</div>
				<!-- .meta-box-sortables -->

			</div>
			<!-- #postbox-container-1 .postbox-container -->

		</div>
		<!-- #post-body .metabox-holder .columns-2 -->

		<br class="clear">
	</div>
	<!-- #poststuff -->

</div> <!-- .wrap -->
