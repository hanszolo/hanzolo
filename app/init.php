<?
spl_autoload_register(function ($name) {
    $filename = "/var/lib/hanzolo/app/src/classes/" . str_replace("\\", "/", $name) . ".php";
    if (file_exists($filename)) {
       require_once($filename);
    } else {
        die("$name not found"); #do something less dumb
    }
});
