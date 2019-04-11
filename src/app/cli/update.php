<?php

// Include required files
include 'blacklist.php';

// Define constants
define('DS', DIRECTORY_SEPARATOR);
define('ROOTWEBDIR', dirname(__FILE__) . DS);
define('APPDIR', realpath(ROOTWEBDIR . '..' . DS) . DS);

// Get config file
$config = file_get_contents(APPDIR . 'assets' . DS . 'json' . DS . 'config.json');
$config = (object) json_decode($config);
$config->public_link = str_replace('https://www.dropbox.com/', 'https://dl.dropboxusercontent.com/', str_replace('?dl=0', '', $config->public_link));

// Get webhook events
$webhooks = @file_get_contents(APPDIR . 'assets' . DS . 'json' . DS . 'events.json');
$webhooks = json_decode($webhooks);

foreach ($webhooks as $key => $value) {
    $webhooks[$value->command][] = $value;
    unset($webhooks[$key]);
}

// Get package json
$package = file_get_contents(APPDIR . 'package.json');
$package = (object) json_decode($package);

// Get remote package json
$remote_package = file_get_contents('https://raw.githubusercontent.com/abdyfranco/macos-ifttt-control/master/src/app/package.json');
$remote_package = (object) json_decode($remote_package);

// Check if there is an update available
if (version_compare($remote_package->version, $package->version, '>')) {
    echo 'Updating from ' . $package->version . ' to ' . $remote_package->version . ". \n";

    // Get remote update map
    $update_map = file_get_contents('https://raw.githubusercontent.com/abdyfranco/macos-ifttt-control/master/src/update_map.json');
    $update_map = (object) json_decode($update_map);

    foreach ($update_map as $remote_file => $local_file) {
        // Get real path for local file
        $local_file = APPDIR . trim(trim($local_file, '.'), '/');

        // Get remote file content
        $remote_content = file_get_contents($remote_file);

        if (is_writable($local_file) || !file_exists($local_file)) {
            // Delete old file
            if (file_exists($local_file)) {
                unlink($local_file);
            }

            // Save new file
            file_put_contents($local_file, $remote_content);

            echo 'Updated: ' . $remote_file . ' => ' . $local_file . "\n";
        } else {
            echo 'Update failed at: ' . $local_file . "\n";
            exit;
        }
    }

    // Update package-lock.json version
    $package_lock = file_get_contents(APPDIR . 'package-lock.json');
    $package_lock = (object) json_decode($package_lock);

    $package_lock->version = $remote_package->version;
    $package_lock = json_encode($package_lock);

    file_put_contents(APPDIR . 'package-lock.json', $package_lock);
} else {
    echo 'No updates available.' . "\n";
}