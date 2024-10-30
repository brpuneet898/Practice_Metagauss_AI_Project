<?php
/*
Plugin Name: BuddyBot Threads
Description: This extension activates only if BuddyBot is activated.
*/

class BuddyBotThreads_Plugin {

    public function __construct() {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        register_activation_hook(__FILE__, array($this, 'buddybotthreads_plugin_activation'));
        register_deactivation_hook(__FILE__, array($this, 'buddybotthreads_plugin_deactivation'));

        add_action('admin_menu', array($this, 'add_threads_submenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_delete_thread', array($this, 'delete_thread')); 
        add_action('wp_ajax_fetch_thread_info', array($this, 'fetch_thread_info')); 
    }

    public function is_core_plugin_active() {
        return is_plugin_active('buddybot/buddybot.php');
    }

    public function buddybotthreads_plugin_activation() {
        if (!$this->is_core_plugin_active()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(esc_html__('This extension requires BuddyBot to be activated. Please activate BuddyBot first.', 'plugin-text-domain'));
        }
    }

    public function buddybotthreads_plugin_deactivation() {
    }

    public function add_threads_submenu() {
        if ($this->is_core_plugin_active()) {
            add_submenu_page(
                'buddybot-chatbot',
                __('Threads', 'buddybotthreads'),
                __('Threads', 'buddybotthreads'),
                'manage_options',
                'threads_submenu_page',
                array($this, 'threads_menu_page_callback')
            );
        }
    }

    public function threads_menu_page_callback() {
        global $wpdb;
        $table_name_threads = $wpdb->prefix . 'buddybot_threads';
        $table_name_users = $wpdb->prefix . 'users';

        $distinct_users = $wpdb->get_col("SELECT DISTINCT u.user_nicename 
                                    FROM $table_name_threads AS t
                                    JOIN $table_name_users AS u ON t.user_id = u.ID");
        $where_clause = '';
        $date_filter_clause = '';
        if (isset($_POST['filter_submit'])) {
            if (!empty($_POST['user_filter'])) {
                $selected_user = sanitize_text_field($_POST['user_filter']);
                $where_clause .= " AND u.user_nicename = '$selected_user'";
            }
            if (!empty($_POST['from_date'])) {
                $from_date = sanitize_text_field($_POST['from_date']);
                $date_filter_clause .= " AND DATE(t.created) >= '$from_date'";
            }

            if (!empty($_POST['to_date'])) {
                $to_date = sanitize_text_field($_POST['to_date']);
                $date_filter_clause .= " AND DATE(t.created) <= '$to_date'";
            }
        }

        $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $per_page = 10;
        $offset = ($current_page - 1) * $per_page;
        // $threads = $wpdb->get_results($wpdb->prepare("SELECT t.id, t.thread_id, u.user_nicename AS user_name, t.thread_name 
        //                                 FROM $table_name_threads AS t
        //                                 JOIN $table_name_users AS u ON t.user_id = u.ID
        //                                 ORDER BY t.id DESC
        //                                 LIMIT %d, %d", $offset, $per_page));
        // $threads = $wpdb->get_results($wpdb->prepare("SELECT t.id, t.thread_id, u.user_nicename AS user_name, t.thread_name 
        //                             FROM $table_name_threads AS t
        //                             JOIN $table_name_users AS u ON t.user_id = u.ID
        //                             WHERE 1=1 $where_clause
        //                             ORDER BY t.id DESC
        //                             LIMIT %d, %d", $offset, $per_page));

        // $total_threads = $wpdb->get_var("SELECT COUNT(id) FROM $table_name_threads");
        $query = "SELECT t.id, t.thread_id, u.user_nicename AS user_name, t.thread_name 
            FROM $table_name_threads AS t
            JOIN $table_name_users AS u ON t.user_id = u.ID
            WHERE 1=1 $where_clause $date_filter_clause
            ORDER BY t.id DESC
            LIMIT %d, %d";
    
        $threads = $wpdb->get_results($wpdb->prepare($query, $offset, $per_page));
        $total_threads = $wpdb->get_var("SELECT COUNT(id) FROM $table_name_threads");

        ?>
        <div class="wrap">
            <h2><?php echo esc_html__('Threads', 'buddybotthreads'); ?></h2>

            <div class="filter-form">
                <h6><?php echo esc_html__('Filters', 'buddybotthreads'); ?></h6>
                <form method="post">
                    <label for="user_filter"><?php echo esc_html__('User:', 'buddybotthreads'); ?></label>
                    <select name="user_filter" id="user_filter">
                        <option value=""><?php echo esc_html__('All Users', 'buddybotthreads'); ?></option>
                        <?php
                        foreach ($distinct_users as $user) {
                            echo '<option value="' . esc_attr($user) . '">' . esc_html($user) . '</option>';
                        }
                        ?>
                    </select>
                    <label for="from_date"><?php echo esc_html__('Date: From', 'buddybotthreads'); ?></label>
                    <input type="date" name="from_date" id="from_date">
                    <label for="to_date"><?php echo esc_html__('To', 'buddybotthreads'); ?></label>
                    <input type="date" name="to_date" id="to_date">
                    <input type="submit" name="filter_submit" value="Go">
                </form>
            </div>

            <div class="p-5">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Sr. No.', 'buddybotthreads'); ?></th>
                            <th><?php echo esc_html__('ID', 'buddybotthreads'); ?></th>
                            <th><?php echo esc_html__('Thread ID', 'buddybotthreads'); ?></th>
                            <th><?php echo esc_html__('User Name', 'buddybotthreads'); ?></th>
                            <th><?php echo esc_html__('Thread Name', 'buddybotthreads'); ?></th>
                            <th><?php echo esc_html__('', 'buddybotthreads'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($threads) {
                            $sr_no = $offset + 1;
                            foreach ($threads as $thread) {
                                ?>
                                <tr>
                                    <td><?php echo esc_html($sr_no); ?></td>
                                    <td><?php echo esc_html($thread->id); ?></td>
                                    <td class="thread-id"><?php echo esc_html($thread->thread_id); ?></td>
                                    <td><?php echo esc_html($thread->user_name); ?></td>
                                    <td><?php echo strlen($thread->thread_name) > 20 ? esc_html(substr($thread->thread_name, 0, 20) . '...') : esc_html($thread->thread_name); ?></td>
                                    <td>
                                        <button class="button button-light thread-info" style="margin: left 110px" data-thread-id="<?php echo esc_attr($thread->id); ?>">
                                            <i class="fas fa-info-circle"></i> 
                                        </button>
                                        <button class="button button-danger thread-delete" style="margin-left:-5px" data-thread-id="<?php echo esc_attr($thread->id); ?>">
                                            <i class="fas fa-trash-alt"></i> 
                                        </button>
                                    </td>
                                </tr>
                                <?php
                                $sr_no++;
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="6"><?php echo esc_html__('No threads found', 'buddybotthreads'); ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                <?php
                $total_pages = ceil($total_threads / $per_page);
                if ($total_pages > 1) {
                    $page_links = paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo; Previous'),
                        'next_text' => __('Next &raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    if ($page_links) {
                        ?>
                        <div class="tablenav">
                            <div class="tablenav-pages">
                                <span class="pagination-links">
                                    <?php echo $page_links; ?>
                                </span> 
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
        <div id="thread-info-modal" class="modal" style="display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-content" style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 30%;">
                <span class="close" style="position: absolute; right: 15px; top: 0; cursor: pointer;">&times;</span>
                <h3>Thread Information</h3>
                <div id="thread-info-content"></div>
            </div>
        </div>
        <div id="delete-thread-modal" class="modal" style="display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.5);">
            <div class="modal-content" style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 20%;">
                <span class="close" style="position: absolute; right: 15px; top: 0; cursor: pointer;">&times;</span>
                <p><?php echo esc_html__('Are you sure you want to delete this thread?', 'buddybotthreads'); ?></p>
                <div style="text-align: right;">
                    <button id="delete-thread-yes" class="button button-danger"><?php echo esc_html__('Yes', 'buddybotthreads'); ?></button>
                    <button id="delete-thread-no" class="button button-primary"><?php echo esc_html__('No', 'buddybotthreads'); ?></button>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var infoButtons = document.querySelectorAll('.thread-info');
                var infoModal = document.getElementById('thread-info-modal');
                var modalContent = document.getElementById('thread-info-content');
                var closeBtnInfo = infoModal.querySelector('.close');

                infoButtons.forEach(function(button) {
                    button.addEventListener('click', function() {
                        infoModal.style.display = 'block';
                        var threadId = this.closest('tr').querySelector('.thread-id').textContent;
                        // console.log('Thread ID:', threadId);
                        fetchThreadInfo(threadId);
                    });
                });

                closeBtnInfo.addEventListener('click', function() {
                    infoModal.style.display = 'none';
                });
                // function fetchThreadInfo(threadId) {
                //     var data = {
                //         action: 'fetch_thread_info',
                //         thread_id: threadId,
                //         security: '<?php echo wp_create_nonce("fetch_thread_info_nonce"); ?>'
                //     };

                //     jQuery.post(ajaxurl, data)
                //         .done(function(response) {
                //             if (response.success) {
                //                 var thread = response.data;
                //                 modalContent.innerHTML = '<pre>' + JSON.stringify(thread, null, 2) + '</pre>';
                //             } else {
                //                 console.error('Failed to fetch thread information: ' + response.data);
                //                 modalContent.innerHTML = '<p>Error: Failed to fetch thread information</p>';
                //             }
                //         })
                //         .fail(function(xhr, status, error) {
                //             console.error('Failed to fetch thread information: ' + error);
                //             modalContent.innerHTML = '<p>Error: Failed to fetch thread information</p>';
                //         });
                // }
                function fetchThreadInfo(threadId) {
                    var data = {
                        action: 'fetch_thread_info',
                        thread_id: threadId,
                        security: '<?php echo wp_create_nonce("fetch_thread_info_nonce"); ?>'
                    };

                    jQuery.post(ajaxurl, data)
                        .done(function(response) {
                            if (response.success) {
                                var thread = response.data;
                                var infoHtml = '<p><strong>ID:</strong> ' + thread.id + '</p>' +
                                            '<p><strong>Object:</strong> ' + thread.object + '</p>' +
                                            '<p><strong>Created At:</strong> ' + thread.created_at + '</p>' +
                                            '<p><strong>Metadata:</strong></p>' +
                                            '<ul>' +
                                            '<li>User ID: ' + thread.metadata.wp_user_id + '</li>' +
                                            '<li>Source: ' + thread.metadata.wp_source + '</li>' +
                                            '</ul>';
                                modalContent.innerHTML = infoHtml;
                            } else {
                                console.error('Failed to fetch thread information: ' + response.data);
                                modalContent.innerHTML = '<p>Error: Failed to fetch thread information</p>';
                            }
                        })
                        .fail(function(xhr, status, error) {
                            console.error('Failed to fetch thread information: ' + error);
                            modalContent.innerHTML = '<p>Error: Failed to fetch thread information</p>';
                        });
                }


                var deleteButtons = document.querySelectorAll('.thread-delete');
                var modal = document.getElementById('delete-thread-modal');
                var closeBtn = modal.querySelector('.close');
                var deleteThreadYesBtn = modal.querySelector('#delete-thread-yes');
                var deleteThreadNoBtn = modal.querySelector('#delete-thread-no');

                deleteButtons.forEach(function(button) {
                    button.addEventListener('click', function() {
                        modal.style.display = 'block';
                        var threadId = this.getAttribute('data-thread-id');
                        deleteThreadYesBtn.setAttribute('data-thread-id', threadId);
                    });
                });

                closeBtn.addEventListener('click', function() {
                    modal.style.display = 'none';
                });

                deleteThreadNoBtn.addEventListener('click', function() {
                    modal.style.display = 'none';
                });

                deleteThreadYesBtn.addEventListener('click', function() {
                    var threadId = this.getAttribute('data-thread-id');
                    var data = {
                        action: 'delete_thread', 
                        thread_id: threadId, 
                        security: '<?php echo wp_create_nonce("delete_thread_nonce"); ?>' 
                    };

                    jQuery.post(ajaxurl, data)
                        .done(function() {
                            modal.style.display = 'none'; 
                            location.reload(); 
                        })
                        .fail(function(xhr, status, error) {
                            console.error('Failed to delete thread: ' + error);
                            modal.style.display = 'none'; 
                        });
                });
            });
        </script>
        <?php
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
    }

    public function delete_thread() {
        check_ajax_referer('delete_thread_nonce', 'security'); 

        if (!isset($_POST['thread_id'])) {
            wp_send_json_error('No thread ID specified');
        }

        $thread_id = intval($_POST['thread_id']);

        global $wpdb;
        $table_name_threads = $wpdb->prefix . 'buddybot_threads';
        $result = $wpdb->delete($table_name_threads, array('id' => $thread_id));
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Thread deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete thread'));
        }
    }
    public function fetch_thread_info() {
        check_ajax_referer('fetch_thread_info_nonce', 'security'); 

        if (!isset($_POST['thread_id'])) {
            wp_send_json_error('No thread ID specified');
        }
        $thread_id = sanitize_text_field($_POST['thread_id']); 
        $settings = \BuddyBot\Admin\Sql\Settings::getInstance();
        $api_key = $settings->getOption('openai_api_key');

        if (empty($api_key)) {
            wp_send_json_error('OpenAI API key is not set');
        }
        $url = 'https://api.openai.com/v1/threads/' . $thread_id;
        error_log('Fetching thread info from URL: ' . $url);

        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'OpenAI-Beta' => 'assistants=v1' 
            ),
        );
        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            wp_send_json_error("Failed to fetch thread information from OpenAI API: $error_message");
        }
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        error_log('Thread info response body: ' . $body); 
        if ($response_code !== 200) {
            wp_send_json_error('Failed to fetch thread information from OpenAI API');
        }
        $thread_info = json_decode($body);
        if (!$thread_info) {
            wp_send_json_error('Failed to parse thread information from OpenAI API response');
        }
        wp_send_json_success($thread_info);
    }
}

new BuddyBotThreads_Plugin();
?>
