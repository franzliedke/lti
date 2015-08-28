<?php

namespace Franzl\Lti;

###
###  Class to represent a LTI Data Connector for MySQL
###

###
#    NB This class assumes that a MySQL connection has already been opened to the appropriate schema
###

class DataConnectorMysql extends DataConnector {

  private $dbTableNamePrefix = '';

###
#    Class constructor
###
  function __construct($dbTableNamePrefix = '') {

    $this->dbTableNamePrefix = $dbTableNamePrefix;

  }


###
###  ToolConsumer methods
###

###
#    Load the tool consumer from the database
###
  public function Tool_Consumer_load($consumer) {

    $ok = FALSE;
    $sql = sprintf('SELECT name, secret, lti_version, consumer_name, consumer_version, consumer_guid, css_path, protected, enabled, enable_from, enable_until, last_access, created, updated ' .
                   "FROM {$this->dbTableNamePrefix}" . DataConnector::CONSUMER_TABLE_NAME . ' ' .
                   "WHERE consumer_key = %s",
       DataConnector::quoted($consumer->getKey()));
    $rs_consumer = mysql_query($sql);
    if ($rs_consumer) {
      $row = mysql_fetch_object($rs_consumer);
      if ($row) {
        $consumer->name = $row->name;
        $consumer->secret = $row->secret;
        $consumer->lti_version = $row->lti_version;
        $consumer->consumer_name = $row->consumer_name;
        $consumer->consumer_version = $row->consumer_version;
        $consumer->consumer_guid = $row->consumer_guid;
        $consumer->css_path = $row->css_path;
        $consumer->protected = ($row->protected == 1);
        $consumer->enabled = ($row->enabled == 1);
        $consumer->enable_from = NULL;
        if (!is_null($row->enable_from)) {
          $consumer->enable_from = strtotime($row->enable_from);
        }
        $consumer->enable_until = NULL;
        if (!is_null($row->enable_until)) {
          $consumer->enable_until = strtotime($row->enable_until);
        }
        $consumer->last_access = NULL;
        if (!is_null($row->last_access)) {
          $consumer->last_access = strtotime($row->last_access);
        }
        $consumer->created = strtotime($row->created);
        $consumer->updated = strtotime($row->updated);
        $ok = TRUE;
      }
      mysql_free_result($rs_consumer);
    }

    return $ok;

  }

###
#    Save the tool consumer to the database
###
  public function Tool_Consumer_save($consumer) {

    if ($consumer->protected) {
      $protected = 1;
    } else {
      $protected = 0;
    }
    if ($consumer->enabled) {
      $enabled = 1;
    } else {
      $enabled = 0;
    }
    $time = time();
    $now = date("{$this->date_format} {$this->time_format}", $time);
    $from = NULL;
    if (!is_null($consumer->enable_from)) {
      $from = date("{$this->date_format} {$this->time_format}", $consumer->enable_from);
    }
    $until = NULL;
    if (!is_null($consumer->enable_until)) {
      $until = date("{$this->date_format} {$this->time_format}", $consumer->enable_until);
    }
    $last = NULL;
    if (!is_null($consumer->last_access)) {
      $last = date($this->date_format, $consumer->last_access);
    }
    if (is_null($consumer->created)) {
      $sql = sprintf("INSERT INTO {$this->dbTableNamePrefix}" . DataConnector::CONSUMER_TABLE_NAME . ' (consumer_key, name, ' .
             'secret, lti_version, consumer_name, consumer_version, consumer_guid, css_path, protected, enabled, enable_from, enable_until, last_access, created, updated) ' .
             "VALUES (%s, %s, %s, %s, %s, %s, %s, %s, {$protected}, {$enabled}, %s, %s, %s, '{$now}', '{$now}')",
         DataConnector::quoted($consumer->getKey()), DataConnector::quoted($consumer->name),
         DataConnector::quoted($consumer->secret), DataConnector::quoted($consumer->lti_version),
         DataConnector::quoted($consumer->consumer_name), DataConnector::quoted($consumer->consumer_version), DataConnector::quoted($consumer->consumer_guid),
         DataConnector::quoted($consumer->css_path), DataConnector::quoted($from), DataConnector::quoted($until), DataConnector::quoted($last));
    } else {
      $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . DataConnector::CONSUMER_TABLE_NAME . ' SET ' .
               'name = %s, secret= %s, lti_version = %s, consumer_name = %s, consumer_version = %s, consumer_guid = %s, ' .
               "css_path = %s, protected = {$protected}, enabled = {$enabled}, enable_from = %s, enable_until = %s, last_access = %s, updated = '{$now}' " .
             "WHERE consumer_key = %s",
         DataConnector::quoted($consumer->name),
         DataConnector::quoted($consumer->secret), DataConnector::quoted($consumer->lti_version),
         DataConnector::quoted($consumer->consumer_name), DataConnector::quoted($consumer->consumer_version), DataConnector::quoted($consumer->consumer_guid),
         DataConnector::quoted($consumer->css_path), DataConnector::quoted($from), DataConnector::quoted($until), DataConnector::quoted($last),
         DataConnector::quoted($consumer->getKey()));
    }
    $ok = mysql_query($sql);
    if ($ok) {
      if (is_null($consumer->created)) {
        $consumer->created = $time;
      }
      $consumer->updated = $time;
    }

    return $ok;

  }

###
#    Delete the tool consumer from the database
###
  public function Tool_Consumer_delete($consumer) {

// Delete any nonce values for this consumer
    $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . DataConnector::NONCE_TABLE_NAME . ' WHERE consumer_key = %s',
       DataConnector::quoted($consumer->getKey()));
    mysql_query($sql);

// Delete any outstanding share keys for resource links for this consumer
    $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . DataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' WHERE primary_consumer_key = %s',
       DataConnector::quoted($consumer->getKey()));
    mysql_query($sql);

// Delete any users in resource links for this consumer
    $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . DataConnector::USER_TABLE_NAME . ' WHERE consumer_key = %s',
       DataConnector::quoted($consumer->getKey()));
    mysql_query($sql);

// Update any resource links for which this consumer is acting as a primary resource link
    $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . DataConnector::RESOURCE_LINK_TABLE_NAME . ' ' .
                   'SET primary_consumer_key = NULL, primary_context_id = NULL, share_approved = NULL ' .
                   'WHERE primary_consumer_key = %s',
       DataConnector::quoted($consumer->getKey()));
    $ok = mysql_query($sql);

// Delete any resource links for this consumer
    $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . DataConnector::RESOURCE_LINK_TABLE_NAME . ' WHERE consumer_key = %s',
       DataConnector::quoted($consumer->getKey()));
    mysql_query($sql);

// Delete consumer
    $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . DataConnector::CONSUMER_TABLE_NAME . ' WHERE consumer_key = %s',
       DataConnector::quoted($consumer->getKey()));
    $ok = mysql_query($sql);

    if ($ok) {
      $consumer->initialise();
    }

    return $ok;

  }

###
#    Load all tool consumers from the database
###
  public function Tool_Consumer_list() {

    $consumers = [];

    $sql = 'SELECT consumer_key, name, secret, lti_version, consumer_name, consumer_version, consumer_guid, css_path, protected, enabled, enable_from, enable_until, last_access, created, updated ' .
           "FROM {$this->dbTableNamePrefix}" . DataConnector::CONSUMER_TABLE_NAME . ' ' .
           'ORDER BY name';
    $rs_consumers = mysql_query($sql);
    if ($rs_consumers) {
      while ($row = mysql_fetch_object($rs_consumers)) {
        $consumer = new ToolConsumer($row->consumer_key, $this);
        $consumer->name = $row->name;
        $consumer->secret = $row->secret;
        $consumer->lti_version = $row->lti_version;
        $consumer->consumer_name = $row->consumer_name;
        $consumer->consumer_version = $row->consumer_version;
        $consumer->consumer_guid = $row->consumer_guid;
        $consumer->css_path = $row->css_path;
        $consumer->protected = ($row->protected == 1);
        $consumer->enabled = ($row->enabled == 1);
        $consumer->enable_from = NULL;
        if (!is_null($row->enable_from)) {
          $consumer->enable_from = strtotime($row->enable_from);
        }
        $consumer->enable_until = NULL;
        if (!is_null($row->enable_until)) {
          $consumer->enable_until = strtotime($row->enable_until);
        }
        $consumer->last_access = NULL;
        if (!is_null($row->last_access)) {
          $consumer->last_access = strtotime($row->last_access);
        }
        $consumer->created = strtotime($row->created);
        $consumer->updated = strtotime($row->updated);
        $consumers[] = $consumer;
      }
      mysql_free_result($rs_consumers);
    }

    return $consumers;

  }

###
###  ResourceLink methods
###

###
#    Load the resource link from the database
###
  public function Resource_Link_load($resource_link) {

    $ok = FALSE;
    $sql = sprintf('SELECT c.* ' .
                   "FROM {$this->dbTableNamePrefix}" . DataConnector::RESOURCE_LINK_TABLE_NAME . ' AS c ' .
                   'WHERE (consumer_key = %s) AND (context_id = %s)',
       DataConnector::quoted($resource_link->getKey()), DataConnector::quoted($resource_link->getId()));
    $rs_context = mysql_query($sql);
    if ($rs_context) {
      $row = mysql_fetch_object($rs_context);
      if ($row) {
        $resource_link->lti_context_id = $row->lti_context_id;
        $resource_link->lti_resource_id = $row->lti_resource_id;
        $resource_link->title = $row->title;
        $resource_link->settings = unserialize($row->settings);
        if (!is_array($resource_link->settings)) {
          $resource_link->settings = [];
        }
        $resource_link->primary_consumer_key = $row->primary_consumer_key;
        $resource_link->primary_resource_link_id = $row->primary_context_id;
        $resource_link->share_approved = (is_null($row->share_approved)) ? NULL : ($row->share_approved == 1);
        $resource_link->created = strtotime($row->created);
        $resource_link->updated = strtotime($row->updated);
        $ok = TRUE;
      }
    }

    return $ok;

  }

###
#    Save the resource link to the database
###
  public function Resource_Link_save($resource_link) {

    if (is_null($resource_link->share_approved)) {
      $approved = 'NULL';
    } else if ($resource_link->share_approved) {
      $approved = 1;
    } else {
      $approved = 0;
    }
    $time = time();
    $now = date("{$this->date_format} {$this->time_format}", $time);
    $settingsValue = serialize($resource_link->settings);
    $key = $resource_link->getKey();
    $id = $resource_link->getId();
    $previous_id = $resource_link->getId(TRUE);
    if (is_null($resource_link->created)) {
      $sql = sprintf("INSERT INTO {$this->dbTableNamePrefix}" . DataConnector::RESOURCE_LINK_TABLE_NAME . ' (consumer_key, context_id, ' .
                     'lti_context_id, lti_resource_id, title, settings, primary_consumer_key, primary_context_id, share_approved, created, updated) ' .
                     "VALUES (%s, %s, %s, %s, %s, '{$settingsValue}', %s, %s, {$approved}, '{$now}', '{$now}')",
         DataConnector::quoted($key), DataConnector::quoted($id),
         DataConnector::quoted($resource_link->lti_context_id), DataConnector::quoted($resource_link->lti_resource_id),
         DataConnector::quoted($resource_link->title),
         DataConnector::quoted($resource_link->primary_consumer_key), DataConnector::quoted($resource_link->primary_resource_link_id));
    } else if ($id == $previous_id) {
      $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . DataConnector::RESOURCE_LINK_TABLE_NAME . ' SET ' .
                     "lti_context_id = %s, lti_resource_id = %s, title = %s, settings = '{$settingsValue}', ".
                     "primary_consumer_key = %s, primary_context_id = %s, share_approved = {$approved}, updated = '{$now}' " .
                     'WHERE (consumer_key = %s) AND (context_id = %s)',
         DataConnector::quoted($resource_link->lti_context_id), DataConnector::quoted($resource_link->lti_resource_id),
         DataConnector::quoted($resource_link->title),
         DataConnector::quoted($resource_link->primary_consumer_key), DataConnector::quoted($resource_link->primary_resource_link_id),
         DataConnector::quoted($key), DataConnector::quoted($id));
    } else {
      $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . DataConnector::RESOURCE_LINK_TABLE_NAME . ' SET ' .
                     "context_id = %s, lti_context_id = %s, lti_resource_id = %s, title = %s, settings = '{$settingsValue}', ".
                     "primary_consumer_key = %s, primary_context_id = %s, share_approved = {$approved}, updated = '{$now}' " .
                     'WHERE (consumer_key = %s) AND (context_id = %s)',
         DataConnector::quoted($id),
         DataConnector::quoted($resource_link->lti_context_id), DataConnector::quoted($resource_link->lti_resource_id),
         DataConnector::quoted($resource_link->title),
         DataConnector::quoted($resource_link->primary_consumer_key), DataConnector::quoted($resource_link->primary_resource_link_id),
         DataConnector::quoted($key), DataConnector::quoted($previous_id));
    }
    $ok = mysql_query($sql);
    if ($ok) {
      if (is_null($resource_link->created)) {
        $resource_link->created = $time;
      }
      $resource_link->updated = $time;
    }

    return $ok;

  }

###
#    Delete the resource link from the database
###
  public function Resource_Link_delete($resource_link) {

// Delete any outstanding share keys for resource links for this consumer
    $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . DataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
                   'WHERE (primary_consumer_key = %s) AND (primary_context_id = %s)',
       DataConnector::quoted($resource_link->getKey()), DataConnector::quoted($resource_link->getId()));
    $ok = mysql_query($sql);

// Delete users
    if ($ok) {
      $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . DataConnector::USER_TABLE_NAME . ' ' .
                     'WHERE (consumer_key = %s) AND (context_id = %s)',
         DataConnector::quoted($resource_link->getKey()), DataConnector::quoted($resource_link->getId()));
      $ok = mysql_query($sql);
    }

// Update any resource links for which this is the primary resource link
    if ($ok) {
      $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . DataConnector::RESOURCE_LINK_TABLE_NAME . ' ' .
                     'SET primary_consumer_key = NULL, primary_context_id = NULL ' .
                     'WHERE (primary_consumer_key = %s) AND (primary_context_id = %s)',
         DataConnector::quoted($resource_link->getKey()), DataConnector::quoted($resource_link->getId()));
      $ok = mysql_query($sql);
    }

// Delete resource link
    if ($ok) {
      $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . DataConnector::RESOURCE_LINK_TABLE_NAME . ' ' .
                     'WHERE (consumer_key = %s) AND (context_id = %s)',
         DataConnector::quoted($resource_link->getKey()), DataConnector::quoted($resource_link->getId()));
      $ok = mysql_query($sql);
    }

    if ($ok) {
      $resource_link->initialise();
    }

    return $ok;

  }

###
#    Obtain an array of User objects for users with a result sourcedId.  The array may include users from other
#    resource links which are sharing this resource link.  It may also be optionally indexed by the user ID of a specified scope.
###
  public function Resource_Link_getUserResultSourcedIDs($resource_link, $local_only, $id_scope) {

    $users = [];

    if ($local_only) {
      $sql = sprintf('SELECT u.consumer_key, u.context_id, u.user_id, u.lti_result_sourcedid ' .
                     "FROM {$this->dbTableNamePrefix}" . DataConnector::USER_TABLE_NAME . ' AS u '  .
                     "INNER JOIN {$this->dbTableNamePrefix}" . DataConnector::RESOURCE_LINK_TABLE_NAME . ' AS c '  .
                     'ON u.consumer_key = c.consumer_key AND u.context_id = c.context_id ' .
                     "WHERE (c.consumer_key = %s) AND (c.context_id = %s) AND (c.primary_consumer_key IS NULL) AND (c.primary_context_id IS NULL)",
         DataConnector::quoted($resource_link->getKey()), DataConnector::quoted($resource_link->getId()));
    } else {
      $sql = sprintf('SELECT u.consumer_key, u.context_id, u.user_id, u.lti_result_sourcedid ' .
                     "FROM {$this->dbTableNamePrefix}" . DataConnector::USER_TABLE_NAME . ' AS u '  .
                     "INNER JOIN {$this->dbTableNamePrefix}" . DataConnector::RESOURCE_LINK_TABLE_NAME . ' AS c '  .
                     'ON u.consumer_key = c.consumer_key AND u.context_id = c.context_id ' .
                     'WHERE ((c.consumer_key = %s) AND (c.context_id = %s) AND (c.primary_consumer_key IS NULL) AND (c.primary_context_id IS NULL)) OR ' .
                     '((c.primary_consumer_key = %s) AND (c.primary_context_id = %s) AND (share_approved = 1))',
         DataConnector::quoted($resource_link->getKey()), DataConnector::quoted($resource_link->getId()),
         DataConnector::quoted($resource_link->getKey()), DataConnector::quoted($resource_link->getId()));
    }
    $rs_user = mysql_query($sql);
    if ($rs_user) {
      while ($row = mysql_fetch_object($rs_user)) {
        $user = new User($resource_link, $row->user_id);
        $user->consumer_key = $row->consumer_key;
        $user->context_id = $row->context_id;
        $user->lti_result_sourcedid = $row->lti_result_sourcedid;
        if (is_null($id_scope)) {
          $users[] = $user;
        } else {
          $users[$user->getId($id_scope)] = $user;
        }
      }
    }

    return $users;

  }

###
#    Get an array of ResourceLinkShare objects for each resource link which is sharing this resource link.
###
  public function Resource_Link_getShares($resource_link) {

    $shares = [];

    $sql = sprintf('SELECT consumer_key, context_id, title, share_approved ' .
                   "FROM {$this->dbTableNamePrefix}" . DataConnector::RESOURCE_LINK_TABLE_NAME . ' ' .
                   'WHERE (primary_consumer_key = %s) AND (primary_context_id = %s) ' .
                   'ORDER BY consumer_key',
       DataConnector::quoted($resource_link->getKey()), DataConnector::quoted($resource_link->getId()));
    $rs_share = mysql_query($sql);
    if ($rs_share) {
      while ($row = mysql_fetch_object($rs_share)) {
        $share = new ResourceLinkShare();
        $share->consumer_key = $row->consumer_key;
        $share->resource_link_id = $row->context_id;
        $share->title = $row->title;
        $share->approved = ($row->share_approved == 1);
        $shares[] = $share;
      }
    }

    return $shares;

  }


###
###  Franzl\Lti\ConsumerNonce methods
###

###
#    Load the consumer nonce from the database
###
  public function Consumer_Nonce_load($nonce) {

    $ok = TRUE;

#
### Delete any expired nonce values
#
    $now = date("{$this->date_format} {$this->time_format}", time());
    $sql = "DELETE FROM {$this->dbTableNamePrefix}" . DataConnector::NONCE_TABLE_NAME . " WHERE expires <= '{$now}'";
    mysql_query($sql);

#
### load the nonce
#
    $sql = sprintf("SELECT value AS T FROM {$this->dbTableNamePrefix}" . DataConnector::NONCE_TABLE_NAME . ' WHERE (consumer_key = %s) AND (value = %s)',
       DataConnector::quoted($nonce->getKey()), DataConnector::quoted($nonce->getValue()));
    $rs_nonce = mysql_query($sql);
    if ($rs_nonce) {
      $row = mysql_fetch_object($rs_nonce);
      if ($row === FALSE) {
        $ok = FALSE;
      }
    }

    return $ok;

  }

###
#    Save the consumer nonce in the database
###
  public function Consumer_Nonce_save($nonce) {

    $expires = date("{$this->date_format} {$this->time_format}", $nonce->expires);
    $sql = sprintf("INSERT INTO {$this->dbTableNamePrefix}" . DataConnector::NONCE_TABLE_NAME . " (consumer_key, value, expires) VALUES (%s, %s, '{$expires}')",
       DataConnector::quoted($nonce->getKey()), DataConnector::quoted($nonce->getValue()));
    $ok = mysql_query($sql);

    return $ok;

  }


###
###  ResourceLinkShareKey methods
###

###
#    Load the resource link share key from the database
###
  public function Resource_Link_Share_Key_load($share_key) {

    $ok = FALSE;

// Clear expired share keys
    $now = date("{$this->date_format} {$this->time_format}", time());
    $sql = "DELETE FROM {$this->dbTableNamePrefix}" . DataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . " WHERE expires <= '{$now}'";
    mysql_query($sql);

// Load share key
    $id = mysql_real_escape_string($share_key->getId());
    $sql = 'SELECT * ' .
           "FROM {$this->dbTableNamePrefix}" . DataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
           "WHERE share_key_id = '{$id}'";
    $rs_share_key = mysql_query($sql);
    if ($rs_share_key) {
      $row = mysql_fetch_object($rs_share_key);
      if ($row) {
        $share_key->primary_consumer_key = $row->primary_consumer_key;
        $share_key->primary_resource_link_id = $row->primary_context_id;
        $share_key->auto_approve = ($row->auto_approve == 1);
        $share_key->expires = strtotime($row->expires);
        $ok = TRUE;
      }
    }

    return $ok;

  }

###
#    Save the resource link share key to the database
###
  public function Resource_Link_Share_Key_save($share_key) {

    if ($share_key->auto_approve) {
      $approve = 1;
    } else {
      $approve = 0;
    }
    $expires = date("{$this->date_format} {$this->time_format}", $share_key->expires);
    $sql = sprintf("INSERT INTO {$this->dbTableNamePrefix}" . DataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
                   '(share_key_id, primary_consumer_key, primary_context_id, auto_approve, expires) ' .
                   "VALUES (%s, %s, %s, {$approve}, '{$expires}')",
       DataConnector::quoted($share_key->getId()), DataConnector::quoted($share_key->primary_consumer_key),
       DataConnector::quoted($share_key->primary_resource_link_id));

    return mysql_query($sql);

  }

###
#    Delete the resource link share key from the database
###
  public function Resource_Link_Share_Key_delete($share_key) {

    $sql = "DELETE FROM {$this->dbTableNamePrefix}" . DataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . " WHERE share_key_id = '{$share_key->getId()}'";

    $ok = mysql_query($sql);

    if ($ok) {
      $share_key->initialise();
    }

    return $ok;

  }


###
###  User methods
###


###
#    Load the user from the database
###
  public function User_load($user) {

    $ok = FALSE;
    $sql = sprintf('SELECT u.* ' .
                   "FROM {$this->dbTableNamePrefix}" . DataConnector::USER_TABLE_NAME . ' AS u ' .
                   'WHERE (consumer_key = %s) AND (context_id = %s) AND (user_id = %s)',
       DataConnector::quoted($user->getResourceLink()->getKey()), DataConnector::quoted($user->getResourceLink()->getId()),
       DataConnector::quoted($user->getId(ToolProvider::ID_SCOPE_ID_ONLY)));
    $rs_user = mysql_query($sql);
    if ($rs_user) {
      $row = mysql_fetch_object($rs_user);
      if ($row) {
        $user->lti_result_sourcedid = $row->lti_result_sourcedid;
        $user->created = strtotime($row->created);
        $user->updated = strtotime($row->updated);
        $ok = TRUE;
      }
    }

    return $ok;

  }

###
#    Save the user to the database
###
  public function User_save($user) {

    $time = time();
    $now = date("{$this->date_format} {$this->time_format}", $time);
    if (is_null($user->created)) {
      $sql = sprintf("INSERT INTO {$this->dbTableNamePrefix}" . DataConnector::USER_TABLE_NAME . ' (consumer_key, context_id, ' .
                     'user_id, lti_result_sourcedid, created, updated) ' .
                     "VALUES (%s, %s, %s, %s, '{$now}', '{$now}')",
         DataConnector::quoted($user->getResourceLink()->getKey()), DataConnector::quoted($user->getResourceLink()->getId()),
         DataConnector::quoted($user->getId(ToolProvider::ID_SCOPE_ID_ONLY)), DataConnector::quoted($user->lti_result_sourcedid));
    } else {
      $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . DataConnector::USER_TABLE_NAME . ' ' .
                     "SET lti_result_sourcedid = %s, updated = '{$now}' " .
                     'WHERE (consumer_key = %s) AND (context_id = %s) AND (user_id = %s)',
         DataConnector::quoted($user->lti_result_sourcedid),
         DataConnector::quoted($user->getResourceLink()->getKey()), DataConnector::quoted($user->getResourceLink()->getId()),
         DataConnector::quoted($user->getId(ToolProvider::ID_SCOPE_ID_ONLY)));
    }
    $ok = mysql_query($sql);
    if ($ok) {
      if (is_null($user->created)) {
        $user->created = $time;
      }
      $user->updated = $time;
    }

    return $ok;

  }

###
#    Delete the user from the database
###
  public function User_delete($user) {

    $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . DataConnector::USER_TABLE_NAME . ' ' .
                   'WHERE (consumer_key = %s) AND (context_id = %s) AND (user_id = %s)',
       DataConnector::quoted($user->getResourceLink()->getKey()), DataConnector::quoted($user->getResourceLink()->getId()),
       DataConnector::quoted($user->getId(ToolProvider::ID_SCOPE_ID_ONLY)));
    $ok = mysql_query($sql);

    if ($ok) {
      $user->initialise();
    }

    return $ok;

  }

}
