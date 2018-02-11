<?php

/**
 * Template file to render profile link of the current user.
 *
 * @link       https://github.com/faiyazalam
 *
 * @package    User_Login_History
 * @subpackage User_Login_History/admin/partials
 */
?>
<?php _e('This table is showing time in the timezone', 'faulh') ?> - <strong><?php echo $this->list_table->get_table_timezone() ?></strong>&nbsp;<span><a class="" href="<?php echo get_edit_user_link() ?>"><?php _e('Edit', 'faulh') ?></a></span>
