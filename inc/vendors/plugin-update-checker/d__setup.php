<?php

// require 'plugin-update-checker.php';
// global $d_plugin_dirs;

// $config = [
//     'git' => 'https://github.com/Duffion/d-plugin-fulcrum',
//     'target_branch' => 'production'
// ];


// $update_checker = Puc_v4_Factory::buildUpdateChecker(
//     $config['git'],
//     __FILE__,
//     'fulcrum'
// );

// //Set the branch that contains the stable release.
// $update_checker->setBranch($config['target_branch']);

//Optional: If you're using a private repository, specify the access token like this:
// $update_checker->setAuthentication($config['auth_token']);

// $d__plugin_info = $update_checker->getVcsApi()->enableReleaseAssets();