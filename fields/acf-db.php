<?php

class acf_db extends acf_db_common
{
    public function __construct($settings)
    {
        parent::__construct($settings);
        add_action( 'wp_ajax_get_posts', array( $this, 'get_posts' ) );
    }
    public function render_field_settings( $field )
	{
        acf_render_field_setting( $field, array(
            'label'			=> __('DB Name','acf-db'),
            'instructions'	=> '',
            'type'			=> 'text',
            'name'			=> 'dbname',
        ));
        acf_render_field_setting( $field, array(
            'label'			=> __('Table Name','acf-db'),
            'instructions'	=> '',
            'type'			=> 'text',
            'name'			=> 'tablename',
        ));
        acf_render_field_setting( $field, array(
            'label'			=> __('Column Name For Editor','acf-db'),
            'instructions'	=> '',
            'type'			=> 'text',
            'name'			=> 'columnname4editor',
        ));
        acf_render_field_setting( $field, array(
            'label'			=> __('Column Name For Save','acf-db'),
            'instructions'	=> '',
            'type'			=> 'text',
            'name'			=> 'columnnames4save',
        ));
        acf_render_field_setting( $field, array(
            'label'			=> __('WHERE','acf-db'),
            'instructions'	=> '',
            'type'			=> 'text',
            'name'			=> 'where',
        ));
	}
	public function get_posts($field,$page = 1,$ppp = 20)
    {
        global $wpdb;
        $is_api = false;
        if(!empty($_POST['action']) && !empty($_POST['data'])){
            $is_api = true;
            $field = $_POST['data'];
            foreach ($field as $i => $_tmp){
                $field[$i] = urldecode($_tmp);
            }
            unset($field['count']);
            $page = $field['page'];
        }
        $sql = $this->__get_sql_by_field($field,$page,$ppp);
        $result = $wpdb->get_results($sql);
        if($is_api){
            $result = array(
                'html' => $this->get_html_by_template($this->settings['path'] . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'parts-item-list.php',array('field'=>$field,'posts'=>$result)),
            );
            if(!empty($_POST['page_info'])){
//                $result['sql'] = $sql;
                $result['count'] = $this->get_posts_count($field);
                $result['last_page'] = $this->get_last_page_no_by($result['count']);
            }
            echo $this->echo_json($result);
            exit;
        }
        return $result;
    }
    public function get_posts_by_value($field)
    {
        global $wpdb;
        $sql = $this->__get_sql_by_field($field);
        $result = $wpdb->get_results($sql);
        return $result;
    }
    public function get_posts_count($field)
    {
        global $wpdb;
        $field['count'] = true;
        $sql = $this->__get_sql_by_field($field);
        $posts = $wpdb->get_results($sql);
        return empty($posts) ? 0 : $posts[0]->count;
    }
    public function __get_sql_by_field($field,$page = 1,$ppp = 20)
    {
        $is_count = !empty($field['count']);
        $column = $field['columnnames4save'];
        $ucolumn = $field['columnname4editor'];
        $sql = array();
        $sql[] = "SELECT";
        if($is_count){
            $sql[] = "COUNT({$column}) AS count";
        }else{
            $sql[] = $ucolumn;
            $sql[] = "," . preg_replace('/,/',',',$column);
        }
        $sql[] = "FROM {$field['dbname']}.{$field['tablename']}";
        $where = array();
        if(!empty($field['words'])){
            $tmp = preg_split('/[\s,]+/',$field['words']);
            foreach ($tmp as $i => $_tmp)
                $tmp[$i] = esc_sql($tmp[$i]);
            $where[] = "({$ucolumn} LIKE '%" . implode("%') OR ({$ucolumn} LIKE '%",$tmp) . "%')";
        }
        if(!empty($field['value'])) {
            $where[] = "{$column} IN ({$field['value']})";
        }
        if(!empty($field['where'])){
            $where[] = $field['where'];
        }
        if(!empty($where)){
            $sql[] = "WHERE " . implode(' AND ',$where);
        }
        if(!empty($field['value'])){
            $sql[] = "ORDER BY FIELD({$column},{$field['value']})";
        }else {
            if (!$is_count) {
                $sql[] = "ORDER BY {$column}";
                $sql[] = "LIMIT " . $this->get_sql_limit_value_by_page($page, $ppp);
            }
        }
        return implode(' ',$sql);
    }
    /**
     * ページ番号からLIMIT句に設定する値を返す
     * @param $page
     * @param int $count_per_page
     * @param int $first_count
     * @return string
     */
    public function get_sql_limit_value_by_page($page, $count_per_page = 20, $first_count = 0)
    {
        if($count_per_page < 1)
            return '';
        if (is_array($page)) {
            $count_per_page = $page['count_per_page'];
            if (isset($page['first_count']))
                $first_count = $page['first_count'];
            $page = $page['page'];
        }
        if ($first_count < 1)
            $first_count = $count_per_page;
        if ($page < 2)
            return "0,$first_count";
        return ($first_count + (($page - 2) * $count_per_page)) . ",$count_per_page";
    }
    /**
     * 件数から最後のページ番号を返す
     * @param $count_of_all
     * @param int $count_per_page
     * @param int $first_count
     * @return int
     */
    public function get_last_page_no_by($count_of_all, $count_per_page = 20, $first_count = 0)
    {
        if (is_array($count_of_all)) {
            $count_per_page = $count_of_all['count_per_page'];
            if (isset($count_of_all['first_count']))
                $first_count = $count_of_all['first_count'];
            $count_of_all = $count_of_all['count_of_all'];
        }
        if($count_of_all < 1)
            return 1;
        if ($first_count < 1)
            $first_count = $count_per_page;
        if ($count_of_all <= $first_count)
            return 1;
        return 1 + ceil(($count_of_all - $first_count) / $count_per_page);
    }
    function render_field( $field ) {
	    $selected_posts = !empty($field['value']) ? $this->get_posts_by_value($field) : array();
	    $value = !empty($field['value']) ? $field['value'] : '';
	    unset($field['value']);
	    $posts_count = $this->get_posts_count($field);
	    $last_page_no = $this->get_last_page_no_by($posts_count);
	    $posts = $this->get_posts($field);
	    //columnnames4save
        $atts = array(
            'id'				=> $field['id'],
            'class'				=> "acf-relationship acf-db {$field['class']}",
            'data-s'			=> '',
            'data-post_type'	=> '',
            'data-taxonomy'		=> ''
        );
        $db = array(
            'count' => $posts_count,
            'last_page' => $last_page_no,
            'page' => 1,
            'dbname' => urlencode($field['dbname']),
            'tablename' => urlencode($field['tablename']),
            'columnname4editor' => urlencode($field['columnname4editor']),
            'columnnames4save' => urlencode($field['columnnames4save']),
            'where' => urlencode($field['where']),
        );
	    ?>
        <div <?php acf_esc_attr_e($atts); ?> data-db='<?php echo json_encode($db);?>'>
            <?php acf_hidden_input( array('name' => $field['name'], 'value' => $value) ); ?>
            <div class="filters">
                <div class="filter -search">
                    <span><?php acf_text_input( array('placeholder' => __("Search...",'acf'), 'data-filter' => 's' , 'name' => 's') );?></span>
                </div>
            </div>
            <div class="selection">
                <div class="choices">
                    <ul class="acf-bl list ui-sortable">
                        <?php echo $this->get_html_by_template($this->settings['path'] . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'parts-item-list.php',array('field'=>$field,'posts'=>$posts));?>
                    </ul>
                </div>
                <div class="values">
                    <ul class="acf-bl list">
                        <?php echo $this->get_html_by_template($this->settings['path'] . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'parts-item-list.php',array('field'=>$field,'posts'=>$selected_posts));?>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
	public function input_admin_enqueue_scripts()
	{
        wp_register_style( 'acf-db-css', $this->settings['url'] . 'assets/css/acf-db.css', array(), $this->settings['version'] );
		wp_register_script( 'acf-db-js', $this->settings['url'] . 'assets/js/acf-db.js', array( 'jquery', 'jquery-numeric' ), $this->settings['version'] );

		wp_enqueue_style('acf-db-css');
		wp_enqueue_script('acf-db-js');
        wp_localize_script('acf-db-js', 'acfdb_ajax', array( 'url' => admin_url('admin-ajax.php')) );
	}
}