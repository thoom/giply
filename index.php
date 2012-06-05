<?php
/**
 * index.php
 *
 * This script can be used as an endpoint for a deploy subdomain.
 *
 * @author Zach Peacock <zdp@thoomtech.com>
 * @copyright Copyright (c) 2012, Thoom Technologies LLC
 *
 */

header("Content-Type: text/plain");
require __DIR__ . "/Giply.php";

$action = $project = $hash = null;
list($action, $project, $hash) = explode('/', $_SERVER['REQUEST_URI']);

if ($action != 'pull') {
    header("400 Invalid action", true, 400);
    exit('Missing action');
}

if (!$project) {
    header("400 Missing project", true, 400);
    exit('Missing project to pull');
}

if (!$hash) {
    header("400 Missing hash", true, 400);
    exit('Missing security hash');
}

if (!isset($_POST['payload'])) {
    header("Missing POST payload", true, 400);
    exit("Missing POST payload");
}

$project_dir = "/var/www/$project";
if ($hash != md5($project_dir)) {
    header("400 Invalid hash", true, 400);
    exit('Invalid security hash');
}

if (!is_dir("$project_dir/.git")) {
    header("400 Invalid project name", true, 400);
    exit('Invalid project name');
}

$deploy = new Giply($project_dir);
$deploy->log("Payload: " . $_POST['payload'], Giply::LOG_DEBUG);

//$deploy->post_deploy = function() use ($deploy) {
//    //Any sort of custom logic here!
//};

$deploy->execute();