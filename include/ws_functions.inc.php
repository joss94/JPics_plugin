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

class JPics_WS
{
  /**
   * JPics_WS constructor
   *   - add service methods
   *
   * @param PwgServer &$service
   */
  public function __construct(&$service)
  {
    global $conf, $user;

    include_once(PHPWG_ROOT_PATH.'include/ws_functions.inc.php');
    $ws_functions_root = PHPWG_ROOT_PATH.'include/ws_functions/';

    $f_params = array(
      'f_min_rate' => array('default'=>null,
                            'type'=>WS_TYPE_FLOAT),
      'f_max_rate' => array('default'=>null,
                            'type'=>WS_TYPE_FLOAT),
      'f_min_hit' =>  array('default'=>null,
                            'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      'f_max_hit' =>  array('default'=>null,
                            'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      'f_min_ratio' => array('default'=>null,
                             'type'=>WS_TYPE_FLOAT|WS_TYPE_POSITIVE),
      'f_max_ratio' => array('default'=>null,
                             'type'=>WS_TYPE_FLOAT|WS_TYPE_POSITIVE),
      'f_max_level' => array('default'=>null,
                             'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      'f_min_date_available' => array('default'=>null),
      'f_max_date_available' => array('default'=>null),
      'f_min_date_created' =>   array('default'=>null),
      'f_max_date_created' =>   array('default'=>null),
      );

    $service->addMethod(
      'jpics.images.archive',
      array($this, 'jpics_archive_images'),
      array(
        'image_id' => array('flags'=>WS_PARAM_FORCE_ARRAY, 'type'=>WS_TYPE_ID),
        'archive' => array('default'=>false, 'type'=>WS_TYPE_BOOL),
      ),
      'Archives images',
      null,
      array()
    );

    $service->addMethod(
      'jpics.images.addToCategory',
      array($this, 'jpics_add_images_to_cat'),
      array(
        'image_id' => array('flags'=>WS_PARAM_FORCE_ARRAY, 'type'=>WS_TYPE_ID),
        'cat_id' => array('type'=>WS_TYPE_ID),
      ),
      'Adds images to a category',
      null,
      array()
    );

    $service->addMethod(
      'jpics.images.moveToCategory',
      array($this, 'jpics_move_images_to_cat'),
      array(
        'image_id' => array('flags'=>WS_PARAM_FORCE_ARRAY, 'type'=>WS_TYPE_ID),
        'cat_id' => array('type'=>WS_TYPE_ID),
      ),
      'Moves images to a category',
      null,
      array()
    );

    $service->addMethod(
      'jpics.categories.getImages',
      array($this, 'jpics_categories_getImages'),
      array_merge(array(
        'cat_id' =>     array('default'=>null, 'flags'=>WS_PARAM_FORCE_ARRAY, 'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
        'recursive' =>  array('default'=>false, 'type'=>WS_TYPE_BOOL),
        'per_page' =>   array('default'=>100, 'maxValue'=>$conf['ws_max_images_per_page'], 'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
        'page' =>       array('default'=>0, 'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
        'order' =>      array('default'=>null, 'info'=>'id, file, name, hit, rating_score, date_creation, date_available, random'),
        ), $f_params),
      'Returns elements for the corresponding categories.
<br><b>cat_id</b> can be empty if <b>recursive</b> is true.
<br><b>order</b> comma separated fields for sorting',
      $ws_functions_root . 'pwg.categories.php'
    );
  }

  /**
   * API method
   * Archives images
   * @param int[] $params
   */
  public function jpics_archive_images($params, &$service)
  {
    include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');

    $archivePics = '0';
    if($params['archive']) {
      $archivePics = '1';
    }

    $query = 'UPDATE '.IMAGES_TABLE.' SET is_archived='.$archivePics.' WHERE id IN ('.implode(',',$params['image_id']).');';

    $result = pwg_query($query, 'id');

    include_once(PHPWG_ROOT_PATH.'include/functions_user.inc.php');
    global $user;

    invalidate_user_cache();
  }

  /**
   * API method
   * Adds images to a category
   * @param int[] $params
   */
  public function jpics_add_images_to_cat($params, &$service)
  {
    include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
    include_once(PHPWG_ROOT_PATH.'include/ws_functions/pwg.images.php');
    foreach($params['image_id'] as $img_id) {
      ws_add_image_category_relations($img_id, $params['cat_id'], false);
    }
  }

  /**
   * API method
   * Moves images to a category
   * @param int[] $params
   */
  public function jpics_move_images_to_cat($params, &$service)
  {
    include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
    include_once(PHPWG_ROOT_PATH.'include/ws_functions/pwg.images.php');
    foreach($params['image_id'] as $img_id) {
      ws_add_image_category_relations($img_id, $params['cat_id'], true);
    }
  }

  /**
   * API method
   * Returns images per category
   * @param mixed[] $params
   *    @option int[] cat_id (optional)
   *    @option bool recursive
   *    @option int per_page
   *    @option int page
   *    @option string order (optional)
   */
  function jpics_categories_getImages($params, &$service)
  {
    global $user, $conf;

    $images = array();

    //------------------------------------------------- get the related categories
    $where_clauses = array();
    foreach ($params['cat_id'] as $cat_id)
    {
      if ($params['recursive'])
      {
        $where_clauses[] = 'uppercats '.DB_REGEX_OPERATOR.' \'(^|,)'.$cat_id.'(,|$)\'';
      }
      else
      {
        $where_clauses[] = 'id='.$cat_id;
      }
    }
    if (!empty($where_clauses))
    {
      $where_clauses = array('('. implode("\n    OR ", $where_clauses) . ')');
    }
    $where_clauses[] = get_sql_condition_FandF(
      array('forbidden_categories' => 'id'),
      null, true
      );

    $query = '
  SELECT id, name, permalink, image_order
    FROM '. CATEGORIES_TABLE .'
    WHERE '. implode("\n    AND ", $where_clauses) .'
  ;';
    $result = pwg_query($query);

    $cats = array();
    while ($row = pwg_db_fetch_assoc($result))
    {
      $row['id'] = (int)$row['id'];
      $cats[ $row['id'] ] = $row;
    }

    //-------------------------------------------------------- get the images
    if (!empty($cats))
    {
      $where_clauses = ws_std_image_sql_filter($params, 'i.');
      $where_clauses[] = 'category_id IN ('. implode(',', array_keys($cats)) .')';
      $where_clauses[] = get_sql_condition_FandF(
        array('visible_images' => 'i.id'),
        null, true
        );

      $order_by = ws_std_image_sql_order($params, 'i.');
      if ( empty($order_by)
            and count($params['cat_id'])==1
            and isset($cats[ $params['cat_id'][0] ]['image_order'])
          )
      {
        $order_by = $cats[ $params['cat_id'][0] ]['image_order'];
      }
      $order_by = empty($order_by) ? $conf['order_by'] : 'ORDER BY '.$order_by;

      $query = '
  SELECT SQL_CALC_FOUND_ROWS i.*, GROUP_CONCAT(category_id) AS cat_ids
    FROM '. IMAGES_TABLE .' i
      INNER JOIN '. IMAGE_CATEGORY_TABLE .' ON i.id=image_id
    WHERE '. implode("\n    AND ", $where_clauses) .'
    GROUP BY i.id
    '. $order_by .'
    LIMIT '. $params['per_page'] .'
    OFFSET '. ($params['per_page']*$params['page']) .'
  ;';
      $result = pwg_query($query);

      while ($row = pwg_db_fetch_assoc($result))
      {
        $image = array();
        foreach (array('id', 'width', 'height', 'hit') as $k)
        {
          if (isset($row[$k]))
          {
            $image[$k] = (int)$row[$k];
          }
        }
        foreach (array('file', 'name', 'comment', 'date_creation', 'date_available', 'is_archived') as $k)
        {
          $image[$k] = $row[$k];
        }
        $image = array_merge($image, ws_std_get_urls($row));

        $image_cats = array();
        foreach (explode(',', $row['cat_ids']) as $cat_id)
        {
          $url = make_index_url(
            array(
              'category' => $cats[$cat_id],
              )
            );
          $page_url = make_picture_url(
            array(
              'category' => $cats[$cat_id],
              'image_id' => $row['id'],
              'image_file' => $row['file'],
              )
            );
          $image_cats[] = array(
            'id' => (int)$cat_id,
            'url' => $url,
            'page_url' => $page_url,
            );
        }

        $image['categories'] = new PwgNamedArray(
          $image_cats,
          'category',
          array('id', 'url', 'page_url')
          );
        $images[] = $image;
      }
    }

    list($total_images) = pwg_db_fetch_row(pwg_query('SELECT FOUND_ROWS()'));

    return array(
      'paging' => new PwgNamedStruct(
        array(
          'page' => $params['page'],
          'per_page' => $params['per_page'],
          'count' => count($images),
          'total_count' => $total_images
          )
        ),
      'images' => new PwgNamedArray(
        $images, 'image',
        ws_std_get_image_xml_attributes()
        )
      );
  }

}



function JPics_Load_WS($arr) {
  $service = &$arr[0];

  global $JPics_WS;
  $JPics_WS = new JPics_WS($service);
}