<?php
namespace BuddyBot\Frontend;

final class ShortCodes extends \BuddyBot\Frontend\MoRoot
{
    protected $shortcodes;

    protected function setShortcodes()
    {
        $this->shortcodes = array(
            'buddybot_chat'
        );
    }
    private function addShortCodes()
    {
        foreach ($this->shortcodes as $shortcode) {
            $class = str_replace('_', '', $shortcode);

            $this->enqueuePluginStyle();
            $this->enqueuePluginScript();
            $this->enqueueViewStyle($class);
            $this->enqueueViewScript($class);

            $view_class = 'BuddyBot\Frontend\Views\\' . $class;
            $view = new $view_class();
            add_shortcode($shortcode, array($view, 'shortcodeHtml'));
        }
    }

    private function enqueuePluginStyle()
    {
        wp_enqueue_style(
            'buddybot-bootstrap-style',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'
        );
    }

    private function enqueuePluginScript()
    {
        wp_enqueue_script(
            'buddybot-bootstrap-script',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'
        );
    }

    private function enqueueViewStyle($file)
    {
        $file_path = $this->config->getRootPath() . 'frontend/css/' . $file . '.css';
        if (file_exists($file_path)) {
            $file_url = $this->config->getRootUrl() . 'frontend/css/' . $file . '.css';
            wp_enqueue_style('buddybot-style-' . $file, $file_url);
        }
    }

    private function enqueueViewScript($file)
    {
        $file_path = $this->config->getRootPath() . 'frontend/js/' . $file . '.js';
        if (file_exists($file_path)) {
            $file_url = $this->config->getRootUrl() . 'frontend/js/' . $file . '.js';
            wp_enqueue_style('buddybot-script-' . $file, $file_url);
        }
    }

    public function __construct()
    {
        $this->setAll();
        $this->addShortCodes();
    }
}