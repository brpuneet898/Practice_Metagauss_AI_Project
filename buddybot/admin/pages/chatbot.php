<div class="p-5">

<?php
$mo_chatbot_page = new \BuddyBot\Admin\Html\Views\ChatBot();
$mo_chatbot_page->getHtml();

$mo_chatbot_requests = new \BuddyBot\Admin\Requests\ChatBot();
add_action('admin_footer', array($mo_chatbot_requests, 'requestsJs'));
?>

</div>