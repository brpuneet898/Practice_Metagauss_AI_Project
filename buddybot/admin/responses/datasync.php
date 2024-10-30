<?php

namespace BuddyBot\Admin\Responses;

class DataSync extends \BuddyBot\Admin\Responses\MoRoot
{
    protected $file_data = '';

    public function checkFileStatus()
    {
        $this->checkNonce('check_file_status');
        
        $file_id = $_POST['file_id'];

        $url = 'https://api.openai.com/v1/files/' . $file_id;
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $this->api_key
            )
        );

        $output = $this->curlOutput($ch);
        $this->checkError($output);
        
        $this->response['success'] = true;
        $this->response['message'] = __('File syncronized!', 'buddybot');

        echo wp_json_encode($this->response);
        wp_die();
    }

    public function isFileWritable()
    {
        $this->checkNonce('is_file_writable');
        $data_type = $_POST['data_type'];
        $file = $this->core_files->getLocalPath($data_type);

        if (is_writable($file)) {
            $this->response['success'] = true;
            $this->response['message'] = '<div>' . __('The file is writable.', 'buddybot') . '</div>';
        } else {
            $this->response['success'] = false;
            $this->response['message'] = __('The file is not writable.', 'buddybot');
        }

        echo wp_json_encode($this->response);
        wp_die();
    }

    public function addDataToFile()
    {
        $this->checkNonce('add_data_to_file');
        $this->checkCapabilities();
        
        $data_type = $_POST['data_type'];

        $method = 'compile' . $data_type;

        if (method_exists($this, $method)) {
            $this->$method();
        } else {
            $this->response['success'] = false;
            $this->response['message'] = '<div>' . __('Data compile method undefined. Operation aborted.', 'buddybot') . '</div>';
            echo json_encode($this->response);
            wp_die();
        }

        $this->writeData($data_type);
        
        $this->response['success'] = true;
        $this->response['message'] = '<div>' . __('Added data to file.', 'buddybot') . '</div>';

        echo json_encode($this->response);
        wp_die();
    }

    private function compilePosts()
    {
        $args = array(
            'post_type' => 'post'
        );
    
        $post_query = new \WP_Query($args);
    
        if($post_query->have_posts()) {
            while($post_query->have_posts()) {
                $post_query->the_post();
                $this->file_data .= strip_tags(get_the_title());
                $this->file_data .= strip_tags(get_the_content());
            }
        }

        wp_reset_postdata();
    }

    function compileComments()
    {
        $args = array(
            'status' => 'approve' // Fetch only approved comments
        );

        $comments = get_comments($args);

        foreach ($comments as $comment) {
            $this->file_data .= strip_tags($comment->comment_content);
        }
    
    }

    private function writeData($data_type)
    {
        $file = fopen($this->core_files->getLocalPath($data_type), "w");
        fwrite($file, str_replace('&nbsp;',' ', $this->file_data));
        fclose($file);
        $this->file_data = '';
    }

    public function transferDataFile()
    {
        $this->checkNonce('transfer_data_file');
        $this->checkCapabilities();

        $data_type = $_POST['data_type'];

        $cfile = curl_file_create(
            realpath($this->core_files->getLocalPath($data_type)),
            'application/octet-stream',
            basename($this->core_files->getRemoteName($data_type))
        );

        $url = 'https://api.openai.com/v1/files';
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $this->api_key
            )
        );

        $data = array(
            'purpose' => 'assistants',
            'file' => $cfile
        );

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $output = $this->curlOutput($ch);
        $this->checkError($output);
        $this->updateRemoteFileOption($data_type, $output);

        wp_die();
    }

    private function updateRemoteFileOption($data_type, $output)
    {
        $update = update_option($this->core_files->getWpOptionName($data_type), $output->id, false);

        if ($update) {
            $this->response['success'] = true;
            $this->response['message'] = '<div>' . __(sprintf('Remote file name updated to %s.', $output->id), 'buddybot') . '</div>';
        } else {
            $this->response['success'] = false;
            $this->response['message'] = '<div>' . __('Unable to update remote file name.', 'buddybot') . '</div>';
        }

        echo json_encode($this->response);
    }

    public function __construct()
    {
        $this->setAll();
        add_action('wp_ajax_checkFileStatus', array($this, 'checkFileStatus'));
        add_action('wp_ajax_isFileWritable', array($this, 'isFileWritable'));
        add_action('wp_ajax_addDataToFile', array($this, 'addDataToFile'));
        add_action('wp_ajax_transferDataFile', array($this, 'transferDataFile'));
    }
}