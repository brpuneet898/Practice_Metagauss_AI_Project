<?php
namespace BuddyBot\Frontend\Views;

class MoRoot extends \BuddyBot\Frontend\MoRoot
{
    public function shortcodeHtml($atts, $content = null)
    {
        $html = '<div class="alert alert-warning" role="alert">';
        $html .= __('No HTML found for this view.', 'buddybot');
        $html .= '</div>';
        return $html;
    }
}