<?php
/**
 * @file
 * DigiPass template implementation to display a errors.
 *
 * Variables:
 * - $title: the title of the error.
 * - $message: message to the user
 * - $shop_url: NetLicensing shop url.
 */
?>
<?php get_header(); ?>
<div class="wrap_inner__digipass page_digipass__shop">
    <h1><?php echo $title; ?></h1>

    <div class="message__digipass">
        <p><?php echo $message; ?></p>
    </div>
</div>

<?php get_footer(); ?>
