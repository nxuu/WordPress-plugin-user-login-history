<?php

/**
 * Template file to render listing table for admin.
 *
 * @link       https://github.com/faiyazalam
 *
 * @package    User_Login_History
 * @subpackage User_Login_History/admin/partials
 */
?>

<div class="wrap">
    <?php Faulh_Template_Helper::head(); ?>
<div><p><?php echo $this->list_table->table_timezone_edit()?></p></div>
<div class="<?php echo $this->plugin_name; ?>-search-filter">
      <?php require(plugin_dir_path(dirname(__FILE__)) . 'partials/form/filter.php');?>
        <br class="clear">
    </div>
<div><?php do_action('faulh_admin_before_listing_table') ?></div>
   <div class="listingOuter">
        <form method="post">
            <input type="hidden" name="<?php echo $this->plugin_name . '_admin_listing_table' ?>" value="">
            <?php
            $this->list_table->prepare_items();
            $this->list_table->display();
            ?>
        </form>
        <br class="clear">
    </div>
<div><?php do_action('faulh_admin_after_listing_table') ?></div>
</div> 
