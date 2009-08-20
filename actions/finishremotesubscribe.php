<?php
/**
 * Handler for remote subscription finish callback
 *
 * PHP version 5
 *
 * @category Action
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @author   Robin Millette <millette@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://laconi.ca/
 *
 * Laconica - a distributed open-source microblogging tool
 * Copyright (C) 2008, 2009, Control Yourself, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 **/

if (!defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/extlib/libomb/service_consumer.php';
require_once INSTALLDIR.'/lib/omb.php';

/**
 * Handler for remote subscription finish callback
 *
 * When a remote user subscribes a local user, a redirect to this action is
 * issued after the remote user authorized his service to subscribe.
 *
 * @category Action
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @author   Robin Millette <millette@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://laconi.ca/
 */
class FinishremotesubscribeAction extends Action
{

    /**
     * Class handler.
     *
     * @param array $args query arguments
     *
     * @return nothing
     *
     **/
    function handle($args)
    {
        parent::handle($args);

        /* Restore session data. RemotesubscribeAction should have stored
           this entry. */
        $service  = unserialize($_SESSION['oauth_authorization_request']);

        if (!$service) {
            $this->clientError(_('Not expecting this response!'));
            return;
        }

        common_debug('stored request: '. print_r($service, true), __FILE__);

        /* Create user objects for both users. Do it early for request
           validation. */
        $listenee = $service->getListeneeURI();
        $user = User::staticGet('uri', $listenee);

        if (!$user) {
            $this->clientError(_('User being listened to doesn\'t exist.'));
            return;
        }

        $other = User::staticGet('uri', $service->getListenerURI());

        if ($other) {
            $this->clientError(_('You can use the local subscription!'));
            return;
        }

        /* Perform the handling itself via libomb. */
        try {
            $service->finishAuthorization($listenee);
        } catch (OAuthException $e) {
            if ($e->getMessage() == 'The authorized token does not equal the ' .
                                    'submitted token.') {
                $this->clientError(_('Not authorized.'));
                return;
            } else {
                $this->clientError(_('Couldn\'t convert request token to ' .
                                     'access token.'));
                return;
            }
        } catch (OMB_RemoteServiceException $e) {
            $this->clientError(_('Unknown version of OMB protocol.'));
            return;
        } catch (Exception $e) {
            common_debug('Got exception ' . print_r($e, true), __FILE__);
            $this->clientError($e->getMessage());
            return;
        }

        /* The service URLs are not accessible from datastore, so setting them
           after insertion of the profile. */
        $remote = Remote_profile::staticGet('uri', $service->getListenerURI());

        $orig_remote = clone($remote);

        $remote->postnoticeurl    =
                            $service->getServiceURI(OMB_ENDPOINT_POSTNOTICE);
        $remote->updateprofileurl =
                            $service->getServiceURI(OMB_ENDPOINT_UPDATEPROFILE);

        if (!$remote->update($orig_remote)) {
                $this->serverError(_('Error updating remote profile'));
                return;
        }

        /* Clear the session data. */
        unset($_SESSION['oauth_authorization_request']);

        /* If we show subscriptions in reverse chronological order, the new one
           should show up close to the top of the page. */
        common_redirect(common_local_url('subscribers', array('nickname' =>
                                                             $user->nickname)),
                        303);
    }
}
