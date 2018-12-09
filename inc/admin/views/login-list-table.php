<div class="wrap">
    <h1><?php esc_html_e('Login List', $this->plugin_text_domain) ?></h1>
    <hr>
    <div><?php require(plugin_dir_path(dirname(__FILE__)) . 'views/forms/filter.php'); ?></div>
    <hr>
    <div><?php echo $this->list_table->timezone_edit_link() ?></div>
    <hr>
    <div class="faulh_admin_table">
        <form method="post">
            <input type="hidden" name="<?php echo $this->list_table->get_bulk_action_form() ?>" value="">
            <?php $this->list_table->display(); ?>
        </form>
    </div>
</div>