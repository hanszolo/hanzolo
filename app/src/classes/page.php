<?

class page {
    public $styles;
    public $scripts;
    public $template;

    public function __construct() {
        $this->styles = array();
        $this->scripts = array();
        $this->template = "default.php";
    }
}
