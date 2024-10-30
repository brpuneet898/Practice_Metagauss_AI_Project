<div class="p-5">

<?php
$mo_settings_page = new \BuddyBot\Admin\Html\Views\Settings();
$mo_settings_page->getHtml();
add_action('admin_footer', array($mo_settings_page, 'pageJs'));

$mo_settings_requests = new \BuddyBot\Admin\Requests\Settings();
add_action('admin_footer', array($mo_settings_requests, 'requestsJs'));
?>

</div>