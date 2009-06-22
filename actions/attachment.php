<?php
/**
 * Laconica, the distributed open-source microblogging tool
 *
 * Show notice attachments
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
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
 *
 * @category  Personal
 * @package   Laconica
 * @author    Evan Prodromou <evan@controlyourself.ca>
 * @copyright 2008-2009 Control Yourself, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://laconi.ca/
 */

if (!defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/attachmentlist.php';

/**
 * Show notice attachments
 *
 * @category Personal
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://laconi.ca/
 */

class AttachmentAction extends Action
{
    /**
     * Attachment object to show
     */

    var $attachment = null;

    /**
     * Load attributes based on database arguments
     *
     * Loads all the DB stuff
     *
     * @param array $args $_REQUEST array
     *
     * @return success flag
     */

    function prepare($args)
    {
        parent::prepare($args);

        if ($id = $this->trimmed('attachment')) {
            $this->attachment = File::staticGet($id);
        }

        if (empty($this->attachment)) {
            $this->clientError(_('No such attachment.'), 404);
            return false;
        }
        return true;
    }

    /**
     * Is this action read-only?
     *
     * @return boolean true
     */

    function isReadOnly($args)
    {
        return true;
    }

    /**
     * Title of the page
     *
     * @return string title of the page
     */
    function title()
    {
        $a = new Attachment($this->attachment);
        return $a->title();
    }

    /**
     * Handle input
     *
     * Only handles get, so just show the page.
     *
     * @param array $args $_REQUEST data (unused)
     *
     * @return void
     */

    function handle($args)
    {
        parent::handle($args);
        $this->showPage();
    }

    /**
     * Don't show local navigation
     *
     * @return void
     */

    function showLocalNavBlock()
    {
    }

    /**
     * Fill the content area of the page
     *
     * Shows a single notice list item.
     *
     * @return void
     */

    function showContent()
    {
        $ali = new Attachment($this->attachment, $this);
        $cnt = $ali->show();
    }

    /**
     * Don't show page notice
     *
     * @return void
     */

    function showPageNoticeBlock()
    {
    }

    /**
     * Show aside: this attachments appears in what notices
     *
     * @return void
     */
    function showSections() {
        $ns = new AttachmentNoticeSection($this);
        $ns->show();
        $atcs = new AttachmentTagCloudSection($this);
        $atcs->show();
    }
}
