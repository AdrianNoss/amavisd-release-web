<?php 
  include('config.php');

  // fallback for config.php < 2022-04-14 (language file renamed)
  if (isset($langugage) && $langugage == 'it_IT') { $language = 'it'; };

  // language handling
  $lang_accept_browser_array = array();

  if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    // retrieve list from browser in order of priority
    $lang_accept_browser = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    preg_match_all('/(\W|^)([a-z]{2})([^a-z]|$)/six', $lang_accept_browser, $m, PREG_PATTERN_ORDER);
    $lang_accept_browser_array = $m[2];
  };

  // push default language from config to the bottom
  if (isset($langugage)) {
    $lang_accept_browser_array[] = $language;
  };

  // push fallback 'en' to the bottom
  $lang_accept_browser_array[] = 'en';

  // run through languages and check for support
  foreach ($lang_accept_browser_array as $tmp) {
    if (file_exists(getcwd() . '/include/' . 'lang-' . $tmp . '.php')) {
      $language = $tmp;
      include('lang-' . $language . '.php');
      break;
    };
  };

  include('functions.php');

  // fallback for config.php < 2022-04-10 (typo fixed)
  if (isset($recaptchca_api_key   ) && ! isset($recaptcha_api_key   )) { $recaptcha_api_key    = $recaptchca_api_key;    };
  if (isset($recaptchca_secret_key) && ! isset($recaptcha_secret_key)) { $recaptcha_secret_key = $recaptchca_secret_key; };

  function test_id_valid($id) {
    $id_pattern = "/^[0-9a-zA-Z.-]+$/";
    return (preg_match($id_pattern, $id));
  };
// vim: set noai ts=2 sw=2 et:
?>
