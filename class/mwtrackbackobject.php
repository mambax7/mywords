<?php
/**
 * MyWords for XOOPS
 *
 * Copyright © 2017 Eduardo Cortés http://www.eduardocortes.mx
 * -------------------------------------------------------------
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 * -------------------------------------------------------------
 * @copyright    Eduardo Cortés (http://www.eduardocortes.mx)
 * @license      GNU GPL 2
 * @package      mywords
 * @author       Eduardo Cortés (AKA bitcero)    <i.bitcero@gmail.com>
 * @url          http://www.eduardocortes.mx
 */
class mwtrackbackobject extends RMObject
{
    public function __construct($id = null)
    {
        $this->db = XoopsDatabaseFactory::getDatabaseConnection();
        $this->_dbtable = $this->db->prefix('mod_mywords_trackbacks');
        $this->setNew();
        $this->initVarsFromTable();
        if (null === $id && $id <= 0) {
            return;
        }

        if (is_numeric($id)) {
            if ($this->loadValues($id)) {
                $this->unsetNew();
            }

            return;
        }
    }

    public function id()
    {
        return $this->getVar('id_t');
    }

    public function save()
    {
        return $this->saveToTable();
    }

    public function update()
    {
        return $this->updateTable();
    }

    public function delete()
    {
        return $this->deleteFromTable();
    }
}
