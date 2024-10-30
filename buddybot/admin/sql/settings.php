<?php

namespace BuddyBot\Admin\Sql;

class Settings extends \BuddyBot\Admin\Sql\MoRoot
{
    protected function setTable()
    {
        $this->table = $this->config->getDbTable('settings');
    }

    protected function isOptionSet($name)
    {
        global $wpdb;
        return $wpdb->get_var(
            $wpdb->prepare(
                'SELECT EXISTS(SELECT 1 FROM %i WHERE option_name=%s LIMIT 1)',
                $this->table, $name
            )
        );
    }

    public function saveOption($name, $value)
    {
        global $wpdb;

        if ($this->isOptionSet($name)) {
            $wpdb->update(
                $this->table,
                array(
                    'option_value' => maybe_serialize($value),
                    'last_editor' => get_current_user_id(),
                    'edited_on' => current_time('mysql', true)
                ),
                array('option_name' => $name),
                array('%s', '%d', '%s'),
                array('%s')
            );
        } else {
            $wpdb->insert(
                $this->table,
                array(
                    'option_name' => $name,
                    'option_value' => maybe_serialize($value),
                    'last_editor' => get_current_user_id(),
                    'edited_on' => current_time('mysql', true)
                ),
                array('%s', '%s', '%d', '%s')
            );
        }
    }
}