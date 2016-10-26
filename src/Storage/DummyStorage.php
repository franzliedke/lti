<?php

namespace Franzl\Lti\Storage;

###
###  Class to represent a dummy LTI Data Connector with no data persistence
###

class DummyStorage extends AbstractStorage
{
    ###
    ###  ToolConsumer methods
    ###

    ###
    #    Load the tool consumer from the database
    ###
    public function toolConsumerLoad($consumer)
    {
        $consumer->secret = 'secret';
        $consumer->enabled = true;
        $now = time();
        $consumer->created = $now;
        $consumer->updated = $now;
        return true;
    }

    ###
    #    Save the tool consumer to the database
    ###
    public function toolConsumerSave($consumer)
    {
        $consumer->updated = time();
        return true;
    }

    ###
    #    Delete the tool consumer from the database
    ###
    public function toolConsumerDelete($consumer)
    {
        $consumer->initialise();
        return true;
    }

    ###
    #    Load all tool consumers from the database
    ###
    public function toolConsumerList()
    {
        return [];
    }

    ###
    ###  ResourceLink methods
    ###

    ###
    #    Load the resource link from the database
    ###
    public function resourceLinkLoad($resource_link)
    {
        $now = time();
        $resource_link->created = $now;
        $resource_link->updated = $now;
        return true;
    }

    ###
    #    Save the resource link to the database
    ###
    public function resourceLinkSave($resource_link)
    {
        $resource_link->updated = time();
        return true;
    }

    ###
    #    Delete the resource link from the database
    ###
    public function resourceLinkDelete($resource_link)
    {
        $resource_link->initialise();
        return true;
    }

    ###
    #    Obtain an array of User objects for users with a result sourcedId.  The array may include users from other
    #    resource links which are sharing this resource link.  It may also be optionally indexed by the user ID of a specified scope.
    ###
    public function resourceLinkGetUserResultSourcedIDs($resource_link, $local_only, $id_scope)
    {
        return [];
    }

    ###
    #    Get an array of ResourceLinkShare objects for each resource link which is sharing this resource link
    ###
    public function resourceLinkGetShares($resource_link)
    {
        return [];
    }


    ###
    ###  Franzl\Lti\ConsumerNonce methods
    ###

    ###
    #    Load the consumer nonce from the database
    ###
    public function consumerNonceLoad($nonce)
    {
        return false;  // assume the nonce does not already exist
    }

    ###
    #    Save the consumer nonce in the database
    ###
    public function consumerNonceSave($nonce)
    {
        return true;
    }


    ###
    ###  ResourceLinkShareKey methods
    ###

    ###
    #    Load the resource link share key from the database
    ###
    public function resourceLinkShareKeyLoad($share_key)
    {
        return true;
    }

    ###
    #    Save the resource link share key to the database
    ###
    public function resourceLinkShareKeySave($share_key)
    {
        return true;
    }

    ###
    #    Delete the resource link share key from the database
    ###
    public function resourceLinkShareKeyDelete($share_key)
    {
        return true;
    }


    ###
    ###  User methods
    ###


    ###
    #    Load the user from the database
    ###
    public function userLoad($user)
    {
        $now = time();
        $user->created = $now;
        $user->updated = $now;
        return true;
    }

    ###
    #    Save the user to the database
    ###
    public function userSave($user)
    {
        $user->updated = time();
        return true;
    }

    ###
    #    Delete the user from the database
    ###
    public function userDelete($user)
    {
        $user->initialise();
        return true;
    }
}
