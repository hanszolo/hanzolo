<?
class header {
    const JQUERY = "<script src=\"//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js\"></script>";

    public static function default_headers() {
        return implode("\n", array(self::JQUERY));
    }

    public static function load_module($main) {
        return sprintf("<script data-main=\"/scripts/$main\" src=\"/scripts/require.js\"></script>", $main);
    }
}