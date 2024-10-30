<div class="welcome wrap">
    <figure><img src="<?php echo plugins_url( '/../../admin/assets/cs-logo.svg', __FILE__ ); ?>" alt="CourseStorm" /></figure>
    <h2>Welcome to the official CourseStorm WordPress plugin!</h2>

    <p>
    CourseStorm provides impossibly simple online class registration, payment processing, and enrollment management. <a href="<?php echo COURSESTORM_SIGNUP_URL; ?>" target="_blank">Learn more about CourseStorm ></a>
    </p>

    <div class="buttons">
        <a href="javascript:void(0)" class="button show-settings primary">Use existing account</a>
        <a href="<?php echo COURSESTORM_SIGNUP_URL; ?>" class="button secondary" target="_blank">Create account</a>
    </div>

    <form class="settings-form" action="options.php" method="post" data-redirect="<?php echo add_query_arg( ['page' => 'options_coursestorm_api'], admin_url( 'options-general.php' ) ); ?>">
        <h2>Please enter your CourseStorm subdomain below</h2>
        <?php
        settings_fields( 'coursestorm' );
        do_settings_sections( 'coursestorm' );
        submit_button();
        ?>
    </form>
</div>