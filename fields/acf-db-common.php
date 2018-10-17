<?php
abstract class acf_db_common extends acf_field
{
    public function __construct( $settings )
    {
        $this->name = 'db';
        $this->label = __('DB', 'acf-db');
        $this->category = __( 'Relational', 'acf' );
        $this->settings = $settings;
        parent::__construct();
    }
    function echo_json($data)
    {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($data);
    }
    public function esc_html($str, $n2br = '<br>', $ignore_br = false)
    {
        $result = esc_html($str);
        if($ignore_br)
            $result = preg_replace('/&lt;(br[^&]*)&gt;/','<$1>',$result);
        if ($n2br !== false)
            return preg_replace('/(\r\n|\r|\n)/', $n2br, $result);
        return $result;
    }
    public function get_html_by_template($template,$data)
    {
        ob_start();
        extract($data);
        include $template;
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }
}