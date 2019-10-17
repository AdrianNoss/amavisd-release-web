<?php
  
  /* 
    lang() Function.
    writes a localized string taken from lang file
  */
  function lang($keyString, $data='', $return=false) {
    
    if (!is_array($data)) $data=array($data);
    
    $stringRet = lang[$keyString];
    
    $elmCounter=1;
    foreach ($data as $dataElement) {
      str_replace("{" . $elmCounter . "}", $dataElement, $stringRet;  
    }
    
    if ($return==false)
      echo $stringRet;
    else
      return $stringRet;
      
    return true;
  
  }

?>
