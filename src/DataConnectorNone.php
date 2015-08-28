<?php

namespace Franzl\Lti;

###
###  Class to represent a dummy LTI Data Connector with no data persistence
###

class DataConnectorNone extends DataConnector {

###
###  ToolConsumer methods
###

###
#    Load the tool consumer from the database
###
  public function Tool_Consumer_load($consumer) {

    $consumer->secret = 'secret';
    $consumer->enabled = TRUE;
    $now = time();
    $consumer->created = $now;
    $consumer->updated = $now;
    return TRUE;

  }

###
#    Save the tool consumer to the database
###
  public function Tool_Consumer_save($consumer) {

    $consumer->updated = time();
    return TRUE;

  }

###
#    Delete the tool consumer from the database
###
  public function Tool_Consumer_delete($consumer) {

    $consumer->initialise();
    return TRUE;

  }

###
#    Load all tool consumers from the database
###
  public function Tool_Consumer_list() {

    return [];

  }

###
###  ResourceLink methods
###

###
#    Load the resource link from the database
###
  public function Resource_Link_load($resource_link) {

    $now = time();
    $resource_link->created = $now;
    $resource_link->updated = $now;
    return TRUE;

  }

###
#    Save the resource link to the database
###
  public function Resource_Link_save($resource_link) {

    $resource_link->updated = time();
    return TRUE;

  }

###
#    Delete the resource link from the database
###
  public function Resource_Link_delete($resource_link) {

    $resource_link->initialise();
    return TRUE;

  }

###
#    Obtain an array of User objects for users with a result sourcedId.  The array may include users from other
#    resource links which are sharing this resource link.  It may also be optionally indexed by the user ID of a specified scope.
###
  public function Resource_Link_getUserResultSourcedIDs($resource_link, $local_only, $id_scope) {

    return [];

  }

###
#    Get an array of ResourceLinkShare objects for each resource link which is sharing this resource link
###
  public function Resource_Link_getShares($resource_link) {

    return [];

  }


###
###  Franzl\Lti\ConsumerNonce methods
###

###
#    Load the consumer nonce from the database
###
  public function Consumer_Nonce_load($nonce) {

    return FALSE;  // assume the nonce does not already exist

  }

###
#    Save the consumer nonce in the database
###
  public function Consumer_Nonce_save($nonce) {

    return TRUE;

  }


###
###  ResourceLinkShareKey methods
###

###
#    Load the resource link share key from the database
###
  public function Resource_Link_Share_Key_load($share_key) {

    return TRUE;

  }

###
#    Save the resource link share key to the database
###
  public function Resource_Link_Share_Key_save($share_key) {

    return TRUE;

  }

###
#    Delete the resource link share key from the database
###
  public function Resource_Link_Share_Key_delete($share_key) {

    return TRUE;

  }


###
###  User methods
###


###
#    Load the user from the database
###
  public function User_load($user) {

    $now = time();
    $user->created = $now;
    $user->updated = $now;
    return TRUE;

  }

###
#    Save the user to the database
###
  public function User_save($user) {

    $user->updated = time();
    return TRUE;

  }

###
#    Delete the user from the database
###
  public function User_delete($user) {

    $user->initialise();
    return TRUE;

  }

}
