<?php

class repository_demo extends repository {
    public function __construct($repositoryid, $context = SITEID, $options = array()) {
        parent::__construct($repositoryid, $context, $options);
    }
    public function get_listing($path = '', $page = '') {
        $list = array();
        $list['list'] = array();
        // the management interface url
        $list['manage'] = false;
        // dynamically loading
        $list['dynload'] = true;
        // the current path of this list.
        $list['path'] = array(
            array('name'=>'root', 'path'=>''),
            array('name'=>'sub_dir', 'path'=>'/sub_dir')
            );
        // set to true, the login link will be removed
        $list['nologin'] = false;
        // set to true, the search button will be removed
        $list['nosearch'] = false;
        // a file in listing
        $list['list'][] = array('title'=>'file.txt',
            'size'=>'1kb',
            'date'=>'2008.1.12',
            'thumbnail'=>'http://localhost/xx.png',
            'thumbnail_wodth'=>32,
            // plugin-dependent unique path to the file (id, url, path, etc.)
            'source'=>'',
            // the accessible url of the file
            'url'=>''
        );
        // a folder in listing
        $list['list'][] = array('title'=>'foler',
            'size'=>'0',
            'date'=>'2008.1.12',
            'childre'=>array(),
            'thumbnail'=>'http://localhost/xx.png',
        );
        return $list;
    }
    // login 
    public function check_login() {
        global $SESSION;
        if (!empty($SESSION->logged)) {
            return true;
        } else {
            return false;
        }
    }
    // if check_login returns false,
    // this function will be called to print a login form.
    public function print_login() {
        $user_field->label = get_string('username', 'repository_demo').': ';
        $user_field->id    = 'demo_username';
        $user_field->type  = 'text';
        $user_field->name  = 'demousername';
        $user_field->value = $ret->username;
        
        $passwd_field->label = get_string('password', 'repository_demo').': ';
        $passwd_field->id    = 'demo_password';
        $passwd_field->type  = 'password';
        $passwd_field->name  = 'demopassword';

        $form = array();
        $form['login'] = array($user_field, $passwd_field);
        return $form;
    }
    //search
    // if this plugin support global search, if this function return
    // true, search function will be called when global searching working
    public function global_search() {
        return false;
    }
    public function search($text) {
        $search_result = array();
        // search result listing's format is the same as 
        // file listing
        $search_result['list'] = array();
        return $search_result;
    }
    // move file to local moodle
    // the default implementation will download the file by $url using curl,
    // that file will be saved as $file_name.
    /**
    public function get_file($url, $file_name = '') {
    }
    */

    // when logout button on file picker is clicked, this function will be 
    // called.
    public function logout() {
        global $SESSION;
        unset($SESSION->logged);
        return true;
    }
    // can be overloaded to use a customized name
    // for repository instance
    public function get_name() {
        return 'demo plugin';
    }
    // management api

    // this function must be static
    public static function get_instance_option_names() {
        return array('account');
    }

    public function instance_config_form(&$mform) {
        $mform->addElement('text', 'account', get_string('account', 'repository_demo'), array('value'=>'','size' => '40'));
    }

    // this function must be static
    public static function get_type_option_names() {
        return array('api_key');
    }
    public function type_config_form(&$mform) {
        $mform->addElement('text', 'api_key', get_string('api_key', 'repository_demo'), array('value'=>'','size' => '40'));
    }
    // will be called when installing a new plugin in admin panel
    public static function plugin_init() {
        $result = true;
        // do nothing
        return $result;
    }
}
