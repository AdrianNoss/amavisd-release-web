<?php
  
  /* 
    lang() Function.
    writes a localized string taken from lang file
  */
  function lang($keyString, $data='', $return=false) {
    global $lang;    
    if (!is_array($data)) $data=array($data);
    
    $stringRet = $lang[$keyString];
    
    $elmCounter=1;
    foreach ($data as $dataElement) {
      $stringRet = str_replace("{" . $elmCounter . "}", $dataElement, $stringRet);
      $elmCounter++;
    }
    
    if ($stringRet=='') $stringRet=$keyString;

    if ($return==false)
      echo $stringRet;
    else
      return $stringRet;
      
    return true;
  
  }

?>
