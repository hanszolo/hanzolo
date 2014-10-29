<?
spl_autoload_register(function ($name) {
    $filename = realpath(__DIR__) . "/src/classes/" . str_replace("\\", "/", $name) . ".php";
    if (file_exists($filename) {
       require_once($filename);
    }
});
