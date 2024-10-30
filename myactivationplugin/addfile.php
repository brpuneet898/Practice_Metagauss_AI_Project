<?php
function addfile_menu_page_callback() {
    ?>
    <div class="wrap">
        <h2><?php echo esc_html__('Add File', 'my-activation-plugin'); ?></h2>
        <div class="p-5">
            <?php
            wp_enqueue_media();
            ?>

            <div class="p-4 border border bg-light rounded-3 w-50">
                <div id="buddybot-file-output" class="small mb-3"></div>
                <input type="hidden" id="buddybot-file-selected" class="form-control mb-2">
                <button class="btn btn-outline-dark btn-sm me-1" type="button" id="buddybot-file-select-btn">
                    <?php esc_html_e('Select File', 'buddybot'); ?>
                </button>
                <button class="btn btn-dark btn-sm ms-1" type="button" id="buddybot-file-upload-btn">
                    <?php esc_html_e('Upload File', 'buddybot'); ?>
                </button>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $("#buddybot-file-select-btn").click(function(e) {
            e.preventDefault();

            let file_frame;

            if (file_frame) {
                file_frame.open();
                return;
            }

            file_frame = wp.media({
                title: "<?php esc_attr_e('Select a File to Upload', 'buddybot'); ?>",
                button: {
                    text: "<?php esc_attr_e('Use This File', 'buddybot'); ?>",
                },
                multiple: false
            });

            file_frame.open();

            file_frame.on("select", function() {
                let selection = file_frame.state().get("selection").first();
                $("#buddybot-file-selected").val(selection.id);
                $("#buddybot-file-output").html("<?php esc_html_e('You selected file ID', 'buddybot'); ?>" + selection.id);
            });
        });

        $("#buddybot-file-upload-btn").click(addFile);

        function addFile() {
            let fileId = $("#buddybot-file-selected").val();
            let nonce = '<?php echo wp_create_nonce("add_file"); ?>';

            const data = {
                "action": "addFile",
                "file_id": fileId,
                "nonce": nonce
            };

            $.post(ajaxurl, data, function(response) {
                alert(response);
                response = JSON.parse(response);

                if (response.success) {
                    $("#buddybot-file-output").html(response.html);
                }
            });
        }
    });
    </script>

    <?php
}


function addFile() {
    $nonce_status = wp_verify_nonce($_POST['nonce'], 'add_file');

    if ($nonce_status === false) {
        wp_die();
    }

    $file_id = $_POST['file_id'];
    
    $settings = \BuddyBot\Admin\Sql\Settings::getInstance();
    $api_key = $settings->getOption('openai_api_key');

    if (empty($api_key)) {
        echo '<div class="error">OpenAI API key is not set. Please set the API key in the plugin settings.</div>';
    }

    $url = 'https://api.openai.com/v1/files';

    $file_path = get_attached_file($file_id);
    $file_name = basename($file_path);

    $args = array(
        'method' => 'POST',
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Disposition' => 'attachment; filename="' . $file_name . '"',
        ),
        'body' => file_get_contents($file_path),
    );

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        $success = false;
    } else {
        $output = json_decode(wp_remote_retrieve_body($response));
        $success = isset($output->id);
        $html = $success ? printFileOutput($output) : '';
    }

    echo json_encode(array('success' => $success, 'html' => $html));
    wp_die();
}

    // function addFile()
    // {
    //     $nonce_status = wp_verify_nonce($_POST['nonce'], 'add_file');

    //     if ($nonce_status === false) {
    //         wp_die();
    //     }

    //     $file_id = $_POST['file_id'];

    //     $cfile = curl_file_create(
    //         wp_get_attachment_url($file_id),
    //         get_post_mime_type($file_id),
    //         get_the_title($file_id)
    //     );

    //     $url = 'https://api.openai.com/v1/files';

    //     $settings = \BuddyBot\Admin\Sql\Settings::getInstance();
    //     $api_key = $settings->getOption('openai_api_key');

    //     if (empty($api_key)) {
    //         echo '<div class="error">OpenAI API key is not set. Please set the API key in the plugin settings.</div>';
    //     }
    //     $ch = curl_init($url);
        
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    //         'Authorization: Bearer ' . $api_key
    //         )
    //     );

    //     $data = array(
    //         'purpose' => 'assistants',
    //         'file' => $cfile
    //     );

    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    //     $output = curl_exec($ch);

    //     if ($output != false) {
    //         $response['success'] = true;
    //         $output = json_decode($output);
    //         $response['html'] = $this->printFileOutput($output);
    //     } else {
    //         $response['success'] = false;
    //     }

    //     echo json_encode($response);
    //     curl_close($ch);

    //     wp_die();
    // }

function printFileOutput($output) {
    $html = '<span>';
    $html .= __(sprintf('Your file has been uploaded successfully with id <b>%s</b>', $output->id), 'buddybot');
    $html .= '</span>';
    return $html;
}

add_action('wp_ajax_addFile', 'addFile');

?>
