<?php

function jpics_install()
{
  global $conf, $prefixeTable;
    
  // column images.download_counter
  $result = pwg_query('SHOW COLUMNS FROM `'.$prefixeTable.'images` LIKE "is_archived";');
  if (!pwg_db_num_rows($result))
  {     
    pwg_query('ALTER TABLE `'.$prefixeTable .'images` ADD `is_archived` BOOL DEFAULT 0;');
  }
}
?>