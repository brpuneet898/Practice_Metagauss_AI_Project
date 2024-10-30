<div class="p-5">

<?php
$mo_assistants_page = new \BuddyBot\Admin\Html\Views\Assistants();
$mo_assistants_page->getHtml();

$mo_assistants_requests = new \BuddyBot\Admin\Requests\Assistants();
add_action('admin_footer', array($mo_assistants_requests, 'requestsJs'));
?>

</div>