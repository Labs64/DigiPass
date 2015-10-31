<?php
/**
 * @file
 * DigiPass template implementation to display a errors.
 *
 * Variables:
 * - $title: the title of the error.
 * - $message: an error message to the user.
 * - $code: error code.
 * - $error: the system error message.
 */
?>
<?php get_header(); ?>
    <div class="wrap_inner__digipass page_digipass__error">
        <h1><?php echo $title; ?></h1>

        <div class="message__digipass">
            <p><?php echo $message; ?></p>
        </div>
    </div>
<?php get_footer(); ?>