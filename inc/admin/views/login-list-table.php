<div class="wrap">
    <?php \User_Login_History\Inc\Common\Helpers\Template::head(esc_html__('Login List', 'faulh')); ?>

    <hr>
    <div><?php require(plugin_dir_path(dirname(__FILE__)) . 'views/forms/filter.php'); ?></div>
    <hr>
    <div><?php echo $this->Login_List_Table->timezone_edit_link() ?></div>
    <hr>
    <div class="faulh_admin_table">
        <form method="post">
            <input type="hidden" name="<?php echo $this->Login_List_Table->get_bulk_action_form() ?>" value="">
            <?php $this->Login_List_Table->display(); ?>
        </form>
    </div>
</div>