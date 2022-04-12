<?php 
  include('config.php');
  include('lang-' . $language . '.php');
  include('functions.php');

  // fallback for config.php < 2022-04-10 (typo fixed)
  if (isset($recaptchca_api_key   ) && ! isset($recaptcha_api_key   )) { $recaptcha_api_key    = $recaptchca_api_key;    };
  if (isset($recaptchca_secret_key) && ! isset($recaptcha_secret_key)) { $recaptcha_secret_key = $recaptchca_secret_key; };

  function test_id_valid($id) {
    $id_pattern = "/^[0-9a-zA-Z.-]+$/";
    return (preg_match($id_pattern, $id));
  };
?>
