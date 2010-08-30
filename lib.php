<?php
require_once("cmis_repository_wrapper.php");
class repository_cmis extends repository {
  	private $cmis=null;
    public function __construct($repositoryid, $context = SITEID, $options = array()) {
        global $SESSION, $CFG;
        parent::__construct($repositoryid, $context, $options);
        $this->sessname = 'cmis_session_'.$this->id;
        $options=array();
        $options["username"]=optional_param('cmisusername', '', PARAM_RAW);
        $options["password"]=optional_param('cmispassword', '', PARAM_RAW);
        if (empty($options["username"]) && isset($SESSION->{$this->sessname})) {
        	$options=unserialize($SESSION->{$this->sessname});
        } else {
        	$SESSION->{$this->sessname}=serialize($options);
        }
        $x=serialize(array("SESSNAME" => $this->sessname,"SESSION" => $SESSION->{$this->sessname}));
        $this->cmis = new CMISService($this->options['cmis_url'],$options["username"],$options["password"]);
        if (!$this->cmis->authenticated) {
            $this->logout();
        }
        
        if ($this->cmis->authenticated) {
        	add_to_log(SITEID,"cmis","CONST","","AUTHENTICATED as " . $options['username']);
        }
    }
    // if check_login returns false,
    // this function will be called to print a login form.
    public function print_login() {
        $user_field->label = get_string('username', 'repository_cmis').': ';
        $user_field->id    = 'cmis_username';
        $user_field->type  = 'text';
        $user_field->name  = 'cmisusername';
        //$user_field->value = $ret->username;
        
        $passwd_field->label = get_string('password', 'repository_cmis').': ';
        $passwd_field->id    = 'cmis_password';
        $passwd_field->type  = 'password';
        $passwd_field->name  = 'cmispassword';

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

    // management api

    public function logout() {
        global $SESSION;
        unset($SESSION->{$this->sessname});
        return $this->print_login();
    }

    public function check_login() {
        global $SESSION;
        return !empty($SESSION->{$this->sessname});
    }
 
    public function get_file($objId, $file = '') {
        global $CFG;
	add_to_log(SITEID,"cmis","GET-FILE-0","",$objId);
        //$node = $this->cmis->getObjectById($objId);
	//add_to_log(SITEID,"cmis","GET-FILE-00","","START-NODE-LOOK-AT");
	//add_to_log(SITEID,"cmis","GET-FILE-0a","",serialize($node));
	//add_to_log(SITEID,"cmis","GET-FILE-0b","",$node->properties['cmis:baseTypeId']);
        //if ($node->properties['cmis:baseTypeId'] == "cmis:document") {
	// We really should check to make sure that this is a document -- but something may be broken on getObjectById -- will let this pass for beta
                $path = $this->prepare_file($file);
                $fp = fopen($path, 'w');
                fwrite($fp,$this->cmis->getContentStream($objId));
		add_to_log(SITEID,"cmis","GET-FILE-1","",$path);
                return array("path" => $path,"objid" => $objId);
        //}
	//add_to_log(SITEID,"cmis","GET-FILE-N","","Returning Null");
        //return null;
    }

    public function get_listing($path = '/', $page = '') {
        global $CFG, $SESSION, $OUTPUT;
        $ret = array();
        $ret['dynload'] = true;
        $ret['list'] = array();
        $url = $this->options['cmis_url'];

        $ret['manage'] = false;
        // set to true, the login link will be removed
        $ret['nologin'] = false;
        // set to true, the search button will be removed
        $ret['nosearch'] = false;
        // a file in listing

        $ret['path'] = array(array('name'=>'Root', 'path'=>'/'));
		add_to_log(SITEID,"cmis","LIST-0","",serialize($ret));

        if (!$this->cmis->authenticated) {
        	$this->logout();
        }
        $folder=$this->cmis->getObjectByPath($path);
        $children=$this->cmis->getChildren($folder->id);
		foreach ($children->objectList as $child) {
            if ($child->properties['cmis:baseTypeId'] == "cmis:document") {
                $ret['list'][] = array('title'=>$child->properties["cmis:name"],
                    'path'=>$folder->properties['cmis:path'],
                    'thumbnail' =>$OUTPUT->pix_url(file_extension_icon($child->properties["cmis:name"], 32)),
                    'source'=>$child->id);
            } elseif ($child->properties['cmis:baseTypeId'] == "cmis:folder") {
                 $ret['list'][] = array('title' => $child->properties["cmis:name"],
                    'path'=>$child->properties['cmis:path'],
                    'thumbnail'=>$OUTPUT->pix_url('f/folder-32') . "",
                    'children'=>array());
            } else {
            }
		}		
		return $ret;
    }

    public function search($search_text) {
        global $CFG;
        $ret = array();
        $ret['list'] = array();
        $query="SELECT cmis:name,score() as rel from cmis:document WHERE CONTAINS('" . $search_text . "')";
        $objs=$this->cmis->query($query);
        add_to_log(SITEID,"cmis",'SEARCH','',$query);
		foreach ($objs->objectList as $obj) {
                $ret['list'][] = array('title'=>$obj->properties["cmis:name"],
                    'source'=>$obj->links['enclosure']);
        }
        
        return $ret;
    }

    public static function get_instance_option_names() {
        return array('cmis_url');
    }

    public function instance_config_form(&$mform) {
        $mform->addElement('text', 'cmis_url', get_string('cmis_url', 'repository_cmis'), array('size' => '40'));
        $mform->addElement('static', 'cmis_url_intro', '', get_string('cmisurltext', 'repository_cmis'));
        $mform->addRule('cmis_url', get_string('required'), 'required', null, 'client');
        return false;
    }
    public static function plugin_init() {
            return true;
    }

}
