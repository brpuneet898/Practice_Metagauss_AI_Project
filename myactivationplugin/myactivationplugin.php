<?php
/*
Plugin Name: BuddyBot Files
Description: This extension activates only if BuddyBot is activated.
*/

function enqueue_my_scripts() {
    wp_enqueue_script('jquery');
    wp_localize_script('your-script-handle', 'my_plugin_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'delete_nonce' => wp_create_nonce('delete_org_file'),
        'info_nonce' => wp_create_nonce('get_file_info'),
        'files_nonce' => wp_create_nonce('get_org_files'),
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_my_scripts');


class My_Activation_Plugin {

    public function __construct() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        register_activation_hook(__FILE__, array($this, 'my_activation_plugin_activation'));
        register_deactivation_hook(__FILE__, array($this, 'my_activation_plugin_deactivation'));

        add_action('admin_menu', array($this, 'add_myfiles_submenu'));
        add_action('wp_ajax_delete_org_file', array($this, 'deleteOrgFile'));
        add_action('wp_ajax_get_org_files', array($this, 'getOrgFiles'));
        add_action('wp_ajax_get_file_info', array($this, 'get_file_info'));

        add_shortcode('buddybot_files', array($this, 'buddybot_files_shortcode'));
    }

    public function is_core_plugin_active() {
        return is_plugin_active('buddybot/buddybot.php');
    }

    public function my_activation_plugin_activation() {
        if (!$this->is_core_plugin_active()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(esc_html__('This extension requires BuddyBot to be activated. Please activate BuddyBot first.', 'plugin-text-domain'));
            // add_action('admin_notices', array($this, 'my_activation_error_notice'));
        }
    }

    public function my_activation_plugin_deactivation() {
    }

    public function my_activation_error_notice() {
        ?>
        <div class="error">
            <p><?php esc_html_e('This extension requires BuddyBot to be activated. Please activate BuddyBot first.', 'plugin-text-domain'); ?></p>
        </div>
        <?php
    }

    public function add_myfiles_submenu() {
        if ($this->is_core_plugin_active()) {
            add_submenu_page(
                'buddybot-chatbot', 
                __('BuddyBot Files', 'metgauss-openai'), 
                __('BuddyBot Files', 'metgauss-openai'),
                'manage_options', 
                'myfiles_submenu_page', 
                array($this, 'myfiles_menu_page_callback')
            );
            include_once(plugin_dir_path(__FILE__) . 'addfile.php');
            add_submenu_page(
                'buddybot-chatbot', 
                __('Add File', 'metgauss-openai'), 
                __('Add File', 'metgauss-openai'),
                'manage_options', 
                'addfile_submenu_page', 
                'addfile_menu_page_callback'
            );
        }
    }

    public function myfiles_menu_page_callback() {
        ?>
        <div class="wrap">
            <!-- <h1><?php echo esc_html__('MyFiles', 'my-activation-plugin'); ?></h1> -->
            <div class="p-5">
                <?php
                $this->myfiles_page_html();
                ?>
            </div>
        </div>
        <?php
    }

    public function myfiles_page_html() {
        $heading = __('Files', 'my-activation-plugin');
        echo '<h2>' . esc_html($heading) . '</h2>';
        $addfile_page = admin_url('admin.php?page=addfile_submenu_page');
        echo '<div class="mb-3">';
        echo '<a class="btn btn-dark btn-sm" role="button" href="' . esc_url($addfile_page) . '">';
        echo esc_html(__('Add File', 'my-activation-plugin'));
        echo '</a>';
        echo '</div>';
        echo '<div id="fileInfoModal" class="modal fade" tabindex="-1" aria-labelledby="fileInfoModalLabel" aria-hidden="true">';
        echo '<div class="modal-dialog">';
        echo '<div class="modal-content">';
        echo '<div class="modal-header">';
        echo '<h5 class="modal-title" id="fileInfoModalLabel">File Information</h5>';
        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
        echo '</div>';
        echo '<div class="modal-body" id="fileInfoContent">';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<table class="mo-org-files-table table table-sm">';
        echo '<thead>';
        echo '<tr>';
        echo '<th scope="col">' . esc_html(__('No.', 'my-activation-plugin')) . '</th>';
        echo '<th scope="col"></th>';
        echo '<th scope="col">' . esc_html(__('File Name', 'my-activation-plugin')) . '</th>';
        echo '<th scope="col">' . esc_html(__('Purpose', 'my-activation-plugin')) . '</th>';
        echo '<th scope="col">' . esc_html(__('Size', 'my-activation-plugin')) . '</th>';
        echo '<th scope="col">' . esc_html(__('ID', 'my-activation-plugin')) . '</th>';
        echo '<th scope="col"></th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        echo '<tr>';
        echo '<td colspan="6" class="p-5">';
        echo '<div class="spinner-border text-dark d-flex justify-content-center mx-auto" role="status">';
        echo '<span class="visually-hidden">Loading...</span>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';
        // wp_enqueue_script( 'files_script', plugin_dir_url( __FILE__ ).'script.js', array('jquery'));
        ?>
        <script>
            // jQuery(document).ready(function ($) {
            //     $(".mo-org-files-table").on("click", ".mo-listbtn-file-delete", function () {
            //         let row = $(this).parents("tr");
            //         let fileId = row.attr("data-mo-itemid");
            //         row.find(".mo-list-spinner").removeClass("visually-hidden");

            //         const data = {
            //         action: "delete_org_file",
            //         file_id: fileId,
            //         nonce: "<?php //echo wp_create_nonce('delete_org_file'); ?>",
            //         };

            //         $.post(ajaxurl, data, function (response) {
            //         response = JSON.parse(response);
            //         if (response.success) {
            //             getOrgFiles();
            //         } else {
            //             alert(
            //             "<?php //echo esc_html__('Failed to delete file', 'plugin-text-domain'); ?>" +
            //                 fileId
            //             );
            //             row.find(".mo-list-spinner").addClass("visually-hidden");
            //         }
            //         });
            //     });

            //     // Code added for the file information JS.
            //     $(document).on("click", ".mo-listbtn-file-info", function () {
            //         // var fileId = $(this).data("file-id");
            //         var fileId = $(this).closest("tr").attr("data-mo-itemid");
            //         $.ajax({
            //         type: "POST",
            //         url: ajaxurl,
            //         data: {
            //             action: "get_file_info",
            //             file_id: fileId,
            //             nonce: "<?php //echo wp_create_nonce('get_file_info'); ?>",
            //         },
            //         success: function (response) {
            //             $("#fileInfoContent").html(response);
            //             $("#fileInfoModal").modal("show");
            //         },
            //         });
            //     });

            //     function getOrgFiles() {
            //         const data = {
            //         action: "get_org_files",
            //         nonce: "<?php //echo wp_create_nonce('get_org_files'); ?>",
            //         };

            //         $.post(ajaxurl, data, function (response) {
            //         $("tbody").html(response);
            //         });
            //     }

            //     getOrgFiles();
            //     });
            document.addEventListener("DOMContentLoaded", function () {
                var table = document.querySelector(".mo-org-files-table");

                if (table) {
                    table.addEventListener("click", function (event) {
                        if (event.target.classList.contains("mo-listbtn-file-delete")) {
                            var row = event.target.closest("tr");
                            var fileId = row.getAttribute("data-mo-itemid");
                            var spinner = row.querySelector(".mo-list-spinner");

                            spinner.classList.remove("visually-hidden");

                            var data = new FormData();
                            data.append("action", "delete_org_file");
                            data.append("file_id", fileId);
                            data.append("nonce", '<?php echo wp_create_nonce("delete_org_file"); ?>');

                            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                                method: "POST",
                                body: data
                            })
                            .then(function (response) {
                                return response.json();
                            })
                            .then(function (response) {
                                if (response.success) {
                                    getOrgFiles();
                                } else {
                                    alert("Failed to delete file " + fileId);
                                    spinner.classList.add("visually-hidden");
                                }
                            })
                            .catch(function (error) {
                                console.error("Error:", error);
                                spinner.classList.add("visually-hidden");
                            });
                        }
                    });

                    table.addEventListener("click", function (event) {
                        if (event.target.classList.contains("mo-listbtn-file-info")) {
                            var fileId = event.target.closest("tr").getAttribute("data-mo-itemid");

                            var data = new FormData();
                            data.append("action", "get_file_info");
                            data.append("file_id", fileId);
                            data.append("nonce", '<?php echo wp_create_nonce("get_file_info"); ?>');

                            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                                method: "POST",
                                body: data
                            })
                            .then(function (response) {
                                return response.text();
                            })
                            .then(function (response) {
                                document.getElementById("fileInfoContent").innerHTML = response;
                                document.getElementById("fileInfoModal").classList.add("show");
                            })
                            .catch(function (error) {
                                console.error("Error:", error);
                            });
                        }
                    });

                    function getOrgFiles() {
                        var data = new FormData();
                        data.append("action", "get_org_files");
                        data.append("nonce", '<?php echo wp_create_nonce("get_org_files"); ?>');

                        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                            method: "POST",
                            body: data
                        })
                        .then(function (response) {
                            return response.text();
                        })
                        .then(function (response) {
                            document.querySelector("tbody").innerHTML = response;
                        })
                        .catch(function (error) {
                            console.error("Error:", error);
                        });
                    }

                    getOrgFiles();
                }
            });
        </script>        
        <?php
    }

    public function deleteOrgFile() {
        $nonce_status = wp_verify_nonce($_POST['nonce'], 'delete_org_file');

        if ($nonce_status === false) {
            wp_die();
        }

        $file_id = $_POST['file_id'];
        // $api_key = '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';

        $settings = \BuddyBot\Admin\Sql\Settings::getInstance();
        $api_key = $settings->getOption('openai_api_key');

        if (empty($api_key)) {
            echo '<div class="error">OpenAI API key is not set. Please set the API key in the plugin settings.</div>';
        }

        $url = 'https://api.openai.com/v1/files/' . $file_id;

        $args = array(
            'method' => 'DELETE',
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
        );

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $success = false;
        } else {
            $success = true;
        }

        echo json_encode(array('success' => $success));
        wp_die();
    }

    public function getOrgFiles() {
        $nonce_status = wp_verify_nonce($_POST['nonce'], 'get_org_files');

        if ($nonce_status === false) {
            wp_die();
        }

        // $api_key = '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'; // Your OpenAI API Key

        $settings = \BuddyBot\Admin\Sql\Settings::getInstance();
        $api_key = $settings->getOption('openai_api_key');

        if (empty($api_key)) {
            echo '<div class="error">OpenAI API key is not set. Please set the API key in the plugin settings.</div>';
        }

        $url = 'https://api.openai.com/v1/files';

        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
        );

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            echo esc_html_e('Error fetching files.', 'plugin-text-domain');
            wp_die();
        }

        $body = wp_remote_retrieve_body($response);
        $files_data = json_decode($body);

        if ($files_data && isset($files_data->data)) {
            $files = $files_data->data;
            $html = '';

            foreach ($files as $index => $file) {
                $html .= '<tr class="small" data-mo-itemid="' . esc_attr($file->id) . '">';
                $html .= '<th scope="row">' . absint($index) + 1 . '</th>';
                $html .= '<td>' . $this->fileIcon($file->filename) . '</td>';
                $html .= '<td>' . esc_html($file->filename) . '</td>';
                $html .= '<td>' . esc_html($file->purpose) . '</td>';
                $html .= '<td>' . esc_html($this->formatSizeUnits($file->bytes)) . '</td>';
                $html .= '<td><code>' . esc_html($file->id) . '</code></td>';
                // $html .= '<td>' . $this->listBtns('file') . '</td>';
                $html .= '<td>' . $this->listBtns('file', $file) . '</td>';
                $html .= '</tr>';
            }

            echo wp_kses_post($html);
        } else {
            echo esc_html_e('No files found.', 'plugin-text-domain');
        }

        wp_die();
    }

    public function formatSizeUnits($bytes) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    public function fileIcon($filename) {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $icon_dir = plugins_url('myactivationplugin') . '/fileicons/';
        $icon_file = $icon_dir . $ext . '.png';
        $icon_url = $icon_dir . 'file.png';

        if (file_exists($icon_file)) {
            $icon_url = $icon_dir . $ext . '.png';
        }

        return '<img width="16" src="' . esc_url($icon_url) . '">';
    }


    public function listBtns($item_type) {
        $info_btn_class = 'mo-listbtn-' . $item_type . '-info';
        $delete_btn_class = 'mo-listbtn-' . $item_type . '-delete';
        
        $html = '<div class="btn-group btn-group-sm me-2" role="group" aria-label="Basic example">';
        // $html .= '<button type="button" class="' . esc_attr($info_btn_class) . ' btn btn-outline-dark">' . $this->moIcon('info') . '</button>';
        $html .= '<button type="button" class="' . esc_attr($info_btn_class) . ' btn btn-outline-dark mo-listbtn-file-info" data-file-id="' . esc_attr($file->id) . '">' . $this->moIcon('info') . '</button>';
        $html .= '<button type="button" class="' . esc_attr($delete_btn_class) . ' btn btn-outline-dark">' . $this->moIcon('delete') . '</button>';
        $html .= '</div>';

        $html .= $this->listSpinner();
        
        return $html;
    }


    public function moIcon($icon) {
        $html = '<span class="material-symbols-outlined" style="font-size:20px;vertical-align:sub;">';
        $html .= esc_html($icon);
        $html .= '</span>';
        return $html;
    }

    public function listSpinner() {
        $html .= '<div class="mo-list-spinner spinner-border spinner-border-sm visually-hidden" role="status">';
        $html .= '<span class="visually-hidden">Loading...</span></div>';
        return $html;
    }

    // public function get_file_info() {
    //     check_ajax_referer('get_file_info', 'nonce');

    //     $file_id = $_POST['file_id'];
    //     $api_key = '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';
    //     $url = 'https://api.openai.com/v1/files/' . $file_id;

    //     $args = array(
    //         'headers' => array(
    //             'Authorization' => 'Bearer ' . $api_key,
    //         ),
    //     );

    //     $response = wp_remote_request($url, $args);

    //     if (is_wp_error($response)) {
    //         echo esc_html_e('Error fetching file information.', 'plugin-text-domain');
    //         wp_die();
    //     }

    //     $body = wp_remote_retrieve_body($response);
    //     $file_info = json_decode($body);

    //     if (!isset($file_info->id)) {
    //         echo esc_html_e('File information not found.', 'plugin-text-domain');
    //         wp_die();
    //     }

    //     echo '<p><strong>File Name:</strong> ' . esc_html($file_info->filename) . '</p>';
    //     echo '<p><strong>Purpose:</strong> ' . esc_html($file_info->purpose) . '</p>';
    //     echo '<p><strong>Size:</strong> ' . esc_html($this->formatSizeUnits($file_info->bytes)) . '</p>';
    //     echo '<p><strong>ID:</strong> ' . esc_html($file_info->id) . '</p>';
    //     echo '<p><strong>Created At:</strong> ' . esc_html(date('Y-m-d H:i:s', $file_info->created_at)) . '</p>';
    //     echo '<p><strong>Status:</strong> ' . esc_html($file_info->status) . '</p>';
    //     echo '<p><strong>Status Details:</strong> ' . esc_html($file_info->status_details) . '</p>';


    //     // $file_extension = pathinfo($file_info->filename, PATHINFO_EXTENSION);
    //     // $image_extensions = array('jpg', 'jpeg', 'png', 'gif');

    //     // if (in_array(strtolower($file_extension), $image_extensions)) {
    //     //     $content_url = 'https://api.openai.com/v1/files/' . $file_id . '/content';
    //     //     $content_response = wp_remote_get($content_url, array('headers' => array('Authorization' => 'Bearer ' . $api_key)));
    //     //     $content_body = wp_remote_retrieve_body($content_response);

    //     //     if (!is_wp_error($content_response) && $content_body) {
    //     //         $base64_image = base64_encode($content_body);
    //     //         echo '<p><strong>Thumbnail:</strong></p>';
    //     //         echo '<img src="data:image/png;base64,' . base64_encode($content_body) . '" width="96" />';
    //     //     } else {
    //     //         echo esc_html_e('Error fetching thumbnail.', 'plugin-text-domain');
    //     //     }
    //     // }

    //     // $file_extension = pathinfo($file_info->filename, PATHINFO_EXTENSION);
    //     // $image_extensions = array('jpg', 'jpeg', 'png', 'gif');

    //     // if (in_array(strtolower($file_extension), $image_extensions)) {
    //     //     $content_url = 'https://api.openai.com/v1/files/' . $file_id . '/content';
    //     //     $content_response = wp_remote_get($content_url, array('headers' => array('Authorization' => 'Bearer ' . $api_key)));
    //     //     $content_body = wp_remote_retrieve_body($content_response);

    //     //     if (!is_wp_error($content_response) && $content_body) {
    //     //         echo '<p><strong>File Content:</strong></p>';
    //     //         echo '<pre>' . esc_html($content_body) . '</pre>';
    //     //     } else {
    //     //         echo esc_html_e('Error fetching file content.', 'plugin-text-domain');
    //     //     }
    //     // }
    //     wp_die();
    // }


    public function get_file_info() {
        check_ajax_referer('get_file_info', 'nonce');
        $file_id = $_POST['file_id'];
        // $api_key = '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';

        $settings = \BuddyBot\Admin\Sql\Settings::getInstance();
        $api_key = $settings->getOption('openai_api_key');

        if (empty($api_key)) {
            echo '<div class="error">OpenAI API key is not set. Please set the API key in the plugin settings.</div>';
        }

        $url = 'https://api.openai.com/v1/files/' . $file_id;
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
        );
        $response = wp_remote_request($url, $args);

        // Till here code remain same, from here there are two options - one is JSON format and other is the formatted way.

        // This if else loop provides a formatted way of viewing the file information. 
        if (is_wp_error($response)) {
            echo esc_html_e('Error fetching file information.', 'plugin-text-domain');
        } else {
            $body = wp_remote_retrieve_body($response);
            $file_info = json_decode($body);
            if (isset($file_info->id)) {
                echo '<p><strong>File Name:</strong> ' . esc_html($file_info->filename) . '</p>';
                echo '<p><strong>Purpose:</strong> ' . esc_html($file_info->purpose) . '</p>';
                echo '<p><strong>Size:</strong> ' . esc_html($this->formatSizeUnits($file_info->bytes)) . '</p>';
                echo '<p><strong>ID:</strong> ' . esc_html($file_info->id) . '</p>';
                echo '<p><strong>Created At:</strong> ' . esc_html(date('Y-m-d H:i:s', $file_info->created_at)) . '</p>';
                echo '<p><strong>Status:</strong> ' . esc_html($file_info->status) . '</p>';
                echo '<p><strong>Status Details:</strong> ' . esc_html($file_info->status_details) . '</p>';
            } else {
                echo esc_html_e('File information not found.', 'plugin-text-domain');
            }
        }
        // This if else loop provides a JSON format of viewing the the file information. 
        // if (is_wp_error($response)) {
        //     echo esc_html_e('Error fetching file information.', 'plugin-text-domain');
        // } else {
        //     $body = wp_remote_retrieve_body($response);
        //     $file_info = json_decode($body);
        //     echo '<pre>';
        //     print_r($file_info);
        //     echo '</pre>';
        // }
        wp_die();
    }

    public function buddybot_files_shortcode($atts) {
        ob_start(); 
        $this->myfiles_page_html();
        $output = ob_get_clean();
        return $output;
    }

}

new My_Activation_Plugin();
?>
