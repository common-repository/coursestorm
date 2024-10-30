<div class="coursestorm-course-payment-plan">
    <strong>Payment plan available:</strong>
    $<?php echo number_format($properties->deposit, 2); ?> deposit plus <?php echo $properties->number_of_payments; ?> payment<?php echo ($properties->number_of_payments > 1) ? 's' : null; ?> of $<?php echo number_format($properties->individual_payment_amount, 2); ?><?php if ($properties->number_of_payments > 1) : ?>, paid <?php echo $properties->payment_interval_text; ?><?php endif; ?>
</div>