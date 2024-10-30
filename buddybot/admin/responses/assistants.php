<?php

namespace BuddyBot\Admin\Responses;

class Assistants extends \BuddyBot\Admin\Responses\MoRoot
{
    
    public function deleteAssistant()
    {

    }

    public function getAssistants()
    {
        $this->checkNonce('get_assistants');

        $url = 'https://api.openai.com/v1/assistants?limit=50';

        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'OpenAI-Beta: assistants=v1',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key
            )
        );

        $output = $this->curlOutput($ch);
        $this->checkError($output);

        if ($output->object === 'list') {
            $this->response['success'] = true;
            $this->assistantsTableHtml($output);
        } else {
            $this->response['success'] = false;
            $this->response['message'] = __('Unable to fetch assistants list.', 'buddybot');
        }

        echo wp_json_encode($this->response);
        wp_die();
    }

    private function assistantsTableHtml($output)
    {
        if (!is_array($output->data)) {
            return;
        }

        $html = '';

        foreach ($output->data as $index => $assistant) {
            $html .= '<tr class="small" data-buddybot-itemid="' . esc_attr($assistant->id) . '">';
            $html .= '<th scope="row">' . absint($index) + 1 . '</th>';
            $html .= '<td>' . esc_html($assistant->name) . '</td>';
            $html .= '<td>' . esc_html($assistant->description) . '</td>';
            $html .= '<td>' . esc_html($assistant->model) . '</td>';
            $html .= '<td><code>' . esc_html($assistant->id) . '</code></td>';
            $html .= '<td>' . $this->assistantBtns($assistant->id) . '</td>';
            $html .= '</tr>';
        }

        $this->response['html'] = $html;
    }

    protected function assistantBtns($assistant_id)
    {   
        $assistant_url = get_admin_url() . 'admin.php?page=buddybot-assistant&assistant_id=' . $assistant_id;
        $html = '<div class="btn-group btn-group-sm me-2" role="group" aria-label="Basic example">';
        $html .= '<a href="' . esc_url($assistant_url) . '" type="button" class="buddybot-listbtn-assistant-edit btn btn-outline-dark">' . $this->moIcon('edit') . '</a>';
        $html .= '<button type="button" class="buddybot-listbtn-assistant-delete btn btn-outline-dark">' . $this->moIcon('delete') . '</button>';
        $html .= '</div>';

        $html .= $this->listSpinner();
        
        return $html;
    }

    public function __construct()
    {
        $this->setAll();
        add_action('wp_ajax_deleteAssistant', array($this, 'deleteAssistant'));
        add_action('wp_ajax_getAssistants', array($this, 'getAssistants'));
    }
}