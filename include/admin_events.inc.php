<?php

// This file is part of the BatchCustomDerivaties Piwigo plugin

/*
 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


defined('PHPWG_ROOT_PATH') or trigger_error('Hacking attempt!', E_USER_ERROR);


function jpics_get_or_create_no_album() {

  $query = 'SELECT id FROM '.CATEGORIES_TABLE.' WHERE name LIKE "%JPicsNoAlbum%";';
  $archive_ids = query2array($query, null, 'id');

  $id = -1;
  if (count($archive_ids) == 0)
  {
    include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
    $res = create_virtual_category("JPicsNoAlbum <!--hidden-->");
    invalidate_user_cache();
    $id = $res['id'];
  } else {
    $id = $archive_ids[0];
  }
  return 0;
}

class JPics_Loader
{
  public function __construct()
  {
    jpics_get_or_create_no_album();
  }
}

function JPics_Load() {
  global $jpics_obj;
  $jpics_obj = new JPics_Loader();

}
?>