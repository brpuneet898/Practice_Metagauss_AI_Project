<?php

namespace BuddyBot\Admin\Requests;

final class Assistants extends \BuddyBot\Admin\Requests\MoRoot
{
    public function requestJs()
    {
        $this->getAssistantsJs();
        $this->deleteAssistantJs();
    }

    private function getAssistantsJs()
    {
        $nonce = wp_create_nonce('get_assistants');
        echo '
        getAssistants();
        function getAssistants() {

            const data = {
                "action": "getAssistants",
                "nonce": "' . $nonce . '"
            };
  
            $.post(ajaxurl, data, function(response) {
                response = JSON.parse(response);
                if (response.success) {
                    $("tbody").html(response.html);
                } else {
                    showAlert(response.message);
                }
            });
        }
        ';
    }

    private function deleteAssistantJs()
    {
        $nonce = wp_create_nonce('delete_assistant');
        echo '
        $(".buddybot-org-assistants-table").on("click", ".buddybot-listbtn-assistant-delete", function(){
            
            let row = $(this).parents("tr");
            let fileId = row.attr("data-buddybot-itemid");

            row.find(".buddybot-list-spinner").removeClass("visually-hidden");

            const data = {
                "action": "deleteOrgFile",
                "file_id": fileId,
                "nonce": "' . $nonce . '"
            };
  
            $.post(ajaxurl, data, function(response) {
                response = JSON.parse(response);

                if (response.success) {
                    getOrgFiles();
                } else {
                    alert("Failed to delete file " + fileId);
                    row.find(".buddybot-list-spinner").addClass("visually-hidden");
                }
            });
        });
        ';
    }
}