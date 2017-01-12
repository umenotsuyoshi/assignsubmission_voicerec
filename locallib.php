<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains the definition for the library class for file submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package assignsubmission_voicerec
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/eventslib.php');
defined('MOODLE_INTERNAL') || die();

/**
 * File areas for file submission assignment
 */
define('ASSIGN_SUBMISSION_VOICEREC_MAX_SUMMARY_FILES', 5);
define('ASSIGN_FILEAREA_SUBMISSION_VOICEREC', 'submission_voicerec');


/**
 * Library class for file submission plugin extending submission plugin base class
 *
 * @package   assignsubmission_voicerec
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_voicerec extends assign_submission_plugin {

    /**
     * Get the name of the file submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('voicerec', 'assignsubmission_voicerec');
    }


    /**
     * Load the submission object for a particular user, optionally creating it if required
     * I don't want to have to do this, but it's private on the assign() class, so can't be used!
     * 
     * upload.phpから呼び出される。
     * 
     * @param int $userid The id of the user whose submission we want or 0 in which case USER->id is used
     * @param bool $create optional Defaults to false. If set to true a new submission object will be created in the database
     * @return stdClass The submission
     */
    public function get_user_submission_record($userid, $create) {
        global $DB, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }
        // if the userid is not null then use userid
        $submission = $DB->get_record('assign_submission', array('assignment'=>$this->assignment->get_instance()->id, 'userid'=>$userid));

        if ($submission) {
            return $submission;
        }
        if ($create) {
            $submission = new stdClass();
            $submission->assignment   = $this->assignment->get_instance()->id;
            $submission->userid       = $userid;
            $submission->timecreated = time();
            $submission->timemodified = $submission->timecreated;

            if ($this->assignment->get_instance()->submissiondrafts) {
                $submission->status = ASSIGN_SUBMISSION_STATUS_DRAFT;
            } else {
                $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
            }
            $sid = $DB->insert_record('assign_submission', $submission);
            $submission->id = $sid;
            return $submission;
        }
        return false;
    }

    /**
     * Get file submission information from the database
     *
     * @param int $submissionid
     * @return mixed
     */
    private function get_file_submission($submissionid) {
        global $DB;
        return $DB->get_record('assignsubmission_voicerec', array('submission'=>$submissionid));
    }


    /**
     * Get the default setting for file submission plugin
     * 
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;
		// サイトでのプラグインのデフォルト設定
        $sitemaxfiles = get_config('assignsubmission_voicerec', 'sitemaxfiles');// ドロップダウンリスト上の最大値
        $defaultfilenum = $this->get_config('maxfilesubmissions');
        $defaultfilenum = ( 0 < $defaultfilenum && $defaultfilenum <= $sitemaxfiles)?$defaultfilenum:1;
        // サイト管理者の設定値以上の録音時間指定は不可
        $sitemaxrecodingduration = get_config('assignsubmission_voicerec', 'maxduration');
        $maxsubmissionduration = $this->get_config('maxsubmissionduration');
        if(!$maxsubmissionduration){
        	$maxsubmissionduration = $sitemaxrecodingduration; 
        }
        $maxsubmissionduration = $sitemaxrecodingduration > $maxsubmissionduration? $maxsubmissionduration:$sitemaxrecodingduration;
		$errordurationmsg = get_string('maxdurationerror', 'assignsubmission_voicerec',$sitemaxrecodingduration);
        $defaultname = $this->get_config('defaultname');
        
        // 録音可能回数の最大値設定
        $settings = array();
        $options = array();
        for ($i = 1; $i <= $sitemaxfiles; $i++) {
            $options[$i] = $i;
        }

        $name = get_string('maxfilessubmission', 'assignsubmission_voicerec');
        $mform->addElement('select', 'assignsubmission_voicerec_maxfiles', $name, $options);
        $mform->addHelpButton('assignsubmission_voicerec_maxfiles', 'maxfilessubmission', 'assignsubmission_voicerec');
        $mform->setDefault('assignsubmission_voicerec_maxfiles', $defaultfilenum);
        $mform->disabledIf('assignsubmission_voicerec_maxfiles', 'assignsubmission_voicerec_enabled', 'notchecked');

        // 録音制限時間
        $name = get_string('maxduration', 'assignsubmission_voicerec');
        $mform->setType('assignsubmission_voicerec_maxduration', PARAM_INT);
        $mform->addElement('text', 'assignsubmission_voicerec_maxduration', $name, array('size' => '6'));
        $mform->addRule('assignsubmission_voicerec_maxduration', $errordurationmsg, 'nonzero', null, 'client');  
        $mform->addRule('assignsubmission_voicerec_maxduration', $errordurationmsg, 'required', null, 'client');
        //$mform->addRule('assignsubmission_voicerec_maxduration', $errordurationmsg, 'numeric', null, 'client');
        $checkdurationcallback = function($val){
        	$sitemaxduration = get_config('assignsubmission_voicerec', 'maxduration');
        	if(0 < $val && $val <= $sitemaxduration){
        		return true;
        	}
        	return false;
        };
        $mform->addRule('assignsubmission_voicerec_maxduration', $errordurationmsg, 'callback', $checkdurationcallback, 'server');
        $mform->addHelpButton('assignsubmission_voicerec_maxduration', 'maxduration', 'assignsubmission_voicerec');
        $mform->setDefault('assignsubmission_voicerec_maxduration', $maxsubmissionduration);
        $mform->disabledIf('assignsubmission_voicerec_maxduration', 'assignsubmission_voicerec_enabled','notchecked');
        
        // 音声ファイルの名前：None (blank),username_assignment_course_date,
        $filenameoptions = array( 
        		0 => get_string("nodefaultname", "assignsubmission_voicerec"), 
        		1 => get_string("lastfirstname", "assignsubmission_voicerec"), 
        		2 => get_string("firstlastname", "assignsubmission_voicerec"));
        $mform->addElement('select', 'assignsubmission_voicerec_defaultname', get_string("defaultname", "assignsubmission_voicerec"), $filenameoptions);
        $mform->addHelpButton('assignsubmission_voicerec_defaultname', 'defaultname', 'assignsubmission_voicerec');
        $mform->setDefault('assignsubmission_voicerec_defaultname', $defaultname);
        $mform->disabledIf('assignsubmission_voicerec_defaultname', 'assignsubmission_voicerec_enabled', 'notchecked');
        
    }

    /**
     * Save the settings for file submission plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $this->set_config('maxfilesubmissions', $data->assignsubmission_voicerec_maxfiles);
        $this->set_config('maxsubmissionduration', $data->assignsubmission_voicerec_maxduration);
        $this->set_config('defaultname', $data->assignsubmission_voicerec_defaultname);
        
        return true;
    }
    /**
     * Produces a list of links to the files uploaded by a user
     *
     * @param $userid int optional id of the user. If 0 then $USER->id is used.
     * @param $return boolean optional defaults to false. If true the list is returned rather than printed
     * @return string optional
     */
    public function print_user_files($submissionid, $allowdelete=true) {
    	global $CFG, $OUTPUT, $DB;
    
    	$strdelete = get_string('delete');
    	$output =  '<link rel="stylesheet" type="text/css" href="submission/voicerec/style.css" />';
    	$fs = get_file_storage();
    	$files = $fs->get_area_files($this->assignment->get_context()->id, 'assignsubmission_voicerec', ASSIGN_FILEAREA_SUBMISSION_VOICEREC, $submissionid, "id", false);
    	if (!empty($files)) {
    		require_once($CFG->dirroot . '/mod/assign/locallib.php');
    		if ($CFG->enableportfolios) {
    			require_once($CFG->libdir.'/portfoliolib.php');
    			$button = new portfolio_add_button();
    		}
    		if (!empty($CFG->enableplagiarism)) {
    			require_once($CFG->libdir . '/plagiarismlib.php');
    		}
    		foreach ($files as $file) {
    			$filename = $file->get_filename();
    			$filepath = $file->get_filepath();
    			$mimetype = $file->get_mimetype();
    			$path = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$this->assignment->get_context()->id.'/assignsubmission_voicerec/submission_voicerec/'.$submissionid.'/'.$filename);
    			//$output .= '<span style="white-space:nowrap;"><img src="'.$OUTPUT->pix_url(file_mimetype_icon($mimetype)).'" class="icon" alt="'.$mimetype.'" />';
    			$output .= '<span style="white-space:nowrap;">';
    			// Dummy link for media filters
    			$options = array(
    					'context'=>$this->assignment->get_context(),
    					'trusted'=>true,
    					'noclean'=>true
    			);
    			$filtered = format_text('<a href="'.$path.'" style="display:none;"> </a> ', $format = FORMAT_HTML, $options);
    			$filtered = preg_replace('~<a.+?</a>~','',$filtered);
    			// Add a real link after the dummy one, so that we get a proper download link no matter what
    			$output .= $filtered . '</span><a href="'.$path.'" >'.s($filename).'</a>';
    			if($allowdelete) {
    				$delurl  = "$CFG->wwwroot/mod/assign/submission/voicerec/delete.php?id={$this->assignment->get_course_module()->id}&amp;sid={$submissionid}&amp;path=$filepath&amp;file=$filename";//&amp;userid={$submission->userid} &amp;mode=$mode&amp;offset=$offset
    
    				$output .= '<a href="'.$delurl.'">&nbsp;'
    						.'<img title="'.$strdelete.'" src="'.$OUTPUT->pix_url('/t/delete').'" class="iconsmall" alt="" /></a> ';
    			}
    			if ($CFG->enableportfolios && has_capability('mod/assign:exportownsubmission', $this->assignment->get_context())) {
    				$button->set_callback_options('assign_portfolio_caller', array('cmid' => $this->assignment->get_course_module()->id, 'sid'=>$submissionid, 'area'=>ASSIGN_FILEAREA_SUBMISSION_VOICEREC), '/mod/assign/portfolio_callback.php');
    				$button->set_format_by_file($file);
    				$output .= $button->to_html(PORTFOLIO_ADD_ICON_LINK);
    			}
    			if (!empty($CFG->enableplagiarism)) {
    				// Wouldn't it be nice if the assignment's get_submission method wasn't private?
    				$submission = $DB->get_record('assign_submission', array('assignment'=>$this->assignment->get_instance()->id, 'id'=>$submissionid), '*', MUST_EXIST);
    				$output .= plagiarism_get_links(array('userid'=>$submission->userid, 'file'=>$file, 'cmid'=>$this->assignment->get_course_module()->id, 'course'=>$this->assignment->get_course(), 'assignment'=>$this->assignment));
    			}
    			$output .= '<br />';
    		}
    		if ($CFG->enableportfolios && count($files) > 1  && has_capability('mod/assign:exportownsubmission', $this->assignment->get_context())) {
    			$button->set_callback_options('assign_portfolio_caller', array('cmid' => $this->assignment->get_course_module()->id, 'sid'=>$submissionid, 'area'=>ASSIGN_FILEAREA_SUBMISSION_VOICEREC), '/mod/assign/portfolio_callback.php');
    			$output .= '<br />'  . $button->to_html(PORTFOLIO_ADD_TEXT_LINK);
    		}
    	}
    
    	//$output = '<div class="files" style="float:left;margin-left:25px;">'.$output.'</div><br clear="all" />';
    	$output = '<div class="files" style="float:left;margin-left:25px;">'.$output.'</div><br clear="all" />';
    	return $output;
    }
    

    /**
     * Add elements to submission form
     *
     * @param mixed $submission stdClass|null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
		global $PAGE;
    	$elements = array();
    	$submissionid = $submission ? $submission->id : 0;
    	$maxduration = $this->get_config('maxsubmissionduration');
    	$mform->addElement('html', $this->print_user_files($submissionid));
    	$maxfiles = $this->get_config('maxfilesubmissions');
    	$count = $this->count_files($submissionid, ASSIGN_FILEAREA_SUBMISSION_VOICEREC);
    	if($count >= $maxfiles) {
    		return;
    	}
    	if (!isset($data->voicerec)) {
    		$data->voicerec = '';
    	}
    	$usemodernbrowser = get_string('usemodernbrowser', 'assignsubmission_voicerec');
    	$checkmikvolume = get_string('checkmikvolume', 'assignsubmission_voicerec');
    	$startpermitbrowserrec = get_string('startpermitbrowserrec', 'assignsubmission_voicerec');
    	$inputrectitle = get_string('inputrectitle', 'assignsubmission_voicerec');
    	$uploadmanualy = get_string('uploadmanualy', 'assignsubmission_voicerec');
    	$submissionlabel = get_string('submissionlabel', 'assignsubmission_voicerec');
    	$changebrowser =  get_string('changebrowser', 'assignsubmission_voicerec');
    	$remainingtime =  get_string('remainingtime', 'assignsubmission_voicerec');
    	$remainingtimeunit =  get_string('remainingtimeunit', 'assignsubmission_voicerec');
    	$checkrecording = get_string('checkrecording','assignsubmission_voicerec');
    	$reclaber= get_string('reclabel','assignsubmission_voicerec');
    	$stoplaber= get_string('stoplabel','assignsubmission_voicerec');
    	$sesskey = sesskey();
    	
    	
    	$PAGE->requires->js_init_call('M.mod_assignsubmission_voicerec.init',array($maxduration));
    	$PAGE->requires->strings_for_js(
    			array('changeserver','changebrowser','inputrectitle','timeoutmessage'),
    			'assignsubmission_voicerec');// Javascriptで使用する言語パック準備
    	   
    			//
    			 
    	$html = <<<EOD
    	<script type="text/javascript" src="submission/voicerec/module.js"></script>
    	<ol>
    	<li>$usemodernbrowser</li>
    	<li>$checkmikvolume</li>
    	<li>$startpermitbrowserrec</li>
    	</ol>
    	<div id="vrec_ctrl_btns">
    		<canvas id="rec_level_meter" width="10" height="29"></canvas>
    		<input type="button" id="voicerec_rec" value="$reclaber"/>
		    <input type="button" id="voicerec_stop" value="$stoplaber"  disabled='disabled'/>
    		<input type="button" id="voicerec_check" value="$checkrecording" disabled='disabled'/>
    		<audio src="" id="voicerec_recording_audio" controls><p>$usemodernbrowser</p></audio>
    		<input type="button" id="voicerec_upload" value="$submissionlabel" disabled='disabled'/>
    	</div>
    	<div>
	        <div id="rectimer_block"><span>$remainingtime</span><span id="rectime_timer">{$maxduration}</span><span>$remainingtimeunit</span></div>
    	</div>
    	<input type="hidden" name="sesskey" value="$sesskey" />
EOD;
    	
    	
    	$mform->addElement('html', $html);
    	return true;
    }

    /**
     * Count the number of files
     *
     * @param int $submissionid
     * @param string $area
     * @return int
     */
    private function count_files($submissionid, $area) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id,
                                     'assignsubmission_voicerec',
                                     $area,
                                     $submissionid,
                                     'id',
                                     false);

        return count($files);
    }

    /**
     * Save the uploaded recording and trigger plagiarism plugin, if enabled, to scan the uploaded files via events trigger
     *
     *　upload.phpから使用される。
     *
     * @global stdClass $USER
     * @global moodle_database $DB
     * @param stdClass $submission
     * @param stdClass $file
     * @return bool
     */
    public function add_recording(stdClass $submission) {
    	
    	global $CFG, $USER, $DB;
    	$fs = get_file_storage();
    	$filesubmission = $this->get_file_submission($submission->id);
    	// Process uploaded file
    	if (!array_key_exists('assignment_file', $_FILES)) {
    		return false;
    	}
    	$filedetails = $_FILES['assignment_file'];
    	$filename = $filedetails['name'];
    	$filesrc = $filedetails['tmp_name'];
    	if (!is_uploaded_file($filesrc)) {
    		return false;
    	}
    	$mimetype = mime_content_type($filesrc); // mime_content_type()がwebmの場合にvideo/webmを返却する。
    	$ext = '.' . substr(StrRChr($mimetype,"/"),1);
    	$ext = ".ogg"; // mime_content_type()バグの回避策。拡張子をwebmにするとビデオとして認識されるので強制的にoggに設定する。 
    	$defaultname = $this->get_config('defaultname');
		/*
 		* 1 => get_string("lastfirstname", "assignsubmission_voicerec"), 
        * 2 => get_string("firstlastname", "assignsubmission_voicerec"));
 		*/
    	if($defaultname) {
    		$filename=($defaultname==1)? $USER->lastname." ".$USER->firstname :$USER->firstname ." ".$USER->lastname;
    		$filename=clean_filename($filename);
    		$filename.='_'.date('Ymd_His');
    		$filename=str_replace(' ', '_', $filename). $ext;
    	}else{
    		$filename = $filename . $ext;
    	}
    	$n=1;
    	while($fs->file_exists($this->assignment->get_context()->id, 'assignsubmission_voicerec', ASSIGN_FILEAREA_SUBMISSION_VOICEREC, $submission->id, '/', $filename)) {
    		if(preg_match('/(.*)(\d+)(\.\w+)$/',$filename,$m)){
    			$filename = "$m[1]". (1+ intval($m[2])). "$m[3]";
    		}else{
    			if($basenaem = strstr($filename,$ext,true)){
    				$filename=$basenaem;
    			}
    			$filename=$filename.$n++. $ext; // カウントアップはされない。
    		}
    	}
    	// Create file
    	$fileinfo = array(
    			'contextid' => $this->assignment->get_context()->id,
    			'component' => 'assignsubmission_voicerec',
    			'filearea' => ASSIGN_FILEAREA_SUBMISSION_VOICEREC,
    			'itemid' => $submission->id,
    			'filepath' => '/',
    			'filename' => $filename
    	);
    	if ($newfile = $fs->create_file_from_pathname($fileinfo, $filesrc)) {
    		$files = $fs->get_area_files($this->assignment->get_context()->id, 'assignsubmission_voicerec', ASSIGN_FILEAREA_SUBMISSION_VOICEREC, $submission->id, "id", false);
    		$count = $this->count_files($submission->id, ASSIGN_FILEAREA_SUBMISSION_VOICEREC);
    		// send files to event system
    		// Let Moodle know that an assessable file was uploaded (eg for plagiarism detection)
    		$eventdata = new stdClass();
    		$eventdata->modulename = 'assign';
    		$eventdata->cmid = $this->assignment->get_course_module()->id;
    		$eventdata->itemid = $submission->id;
    		$eventdata->courseid = $this->assignment->get_course()->id;
    		$eventdata->userid = $USER->id;
    		if ($count > 1) {
    			$eventdata->files = $files;
    		}
    		$eventdata->file = $files;
    		events_trigger('assessable_file_uploaded', $eventdata);
    
    
    		if ($filesubmission) {
    			$filesubmission->numfiles = $this->count_files($submission->id, ASSIGN_FILEAREA_SUBMISSION_VOICEREC);
    			return $DB->update_record('assignsubmission_voicerec', $filesubmission);
    		} else {
    			$filesubmission = new stdClass();
    			$filesubmission->numfiles = $this->count_files($submission->id, ASSIGN_FILEAREA_SUBMISSION_VOICEREC);
    			$filesubmission->submission = $submission->id;
    			$filesubmission->assignment = $this->assignment->get_instance()->id;
    			return $DB->insert_record('assignsubmission_voicerec', $filesubmission) > 0;
    		}
    	}
    }
    
    /**
     * Produce a list of files suitable for export that represent this feedback or submission
     *
     * @param stdClass $submission The submission
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission, stdClass $user=null) {
        $result = array();
        $fs = get_file_storage();

        $files = $fs->get_area_files($this->assignment->get_context()->id, 'assignsubmission_voicerec', ASSIGN_FILEAREA_SUBMISSION_VOICEREC, $submission->id, "timemodified", false);

        foreach ($files as $file) {
            $result[$file->get_filename()] = $file;
        }
        return $result;
    }

    /**
     * Display the list of files  in the submission status table
     *
     * @param stdClass $submission
     * @return string
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
        $count = $this->count_files($submission->id, ASSIGN_FILEAREA_SUBMISSION_VOICEREC);

        // show we show a link to view all files for this plugin?
        $showviewlink = $count > ASSIGN_SUBMISSION_VOICEREC_MAX_SUMMARY_FILES;
        if ($count <= ASSIGN_SUBMISSION_VOICEREC_MAX_SUMMARY_FILES) {
            return $this->print_user_files($submission->id, false);
        } else {
            return get_string('countfiles', 'assignsubmission_voicerec', $count);
        }
    }

    /**
     * No full submission view - the summary contains the list of files and that is the whole submission
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        return $this->assignment->render_area_files('assignsubmission_voicerec', ASSIGN_FILEAREA_SUBMISSION_VOICEREC, $submission->id);
    }



    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     *
     * @param string $type
     * @param int $version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        if ($type == 'voicerec') {
            return true;
        }
        return false;
    }


    /**
     * Upgrade the settings from the old assignment
     * to the new plugin based one
     *
     * @param context $oldcontext - the old assignment context
     * @param stdClass $oldassignment - the old assignment data record
     * @param string log record log events here
     * @return bool Was it a success? (false will trigger rollback)
     */
    public function upgrade_settings(context $oldcontext,stdClass $oldassignment, & $log) {
        // Old assignment plugin ran out of vars so couldn't do max files, just default to module max
        
        $this->set_config('maxfilesubmissions', 1);
        $sitemaxrecodingduration = get_config('assignsubmission_voicerec', 'maxduration');
        $this->set_config('maxsubmissionduration', $sitemaxrecodingduration);
        $this->set_config('defaultname', $oldassignment->var2);
        
        return true;
    }

    /**
     * Upgrade the submission from the old assignment to the new one
     *
     * @global moodle_database $DB
     * @param context $oldcontext The context of the old assignment
     * @param stdClass $oldassignment The data record for the old oldassignment
     * @param stdClass $oldsubmission The data record for the old submission
     * @param stdClass $submission The data record for the new submission
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext, stdClass $oldassignment, stdClass $oldsubmission, stdClass $submission, & $log) {
        global $DB;

        $filesubmission = new stdClass();

        $filesubmission->numfiles = $oldsubmission->numfiles;
        $filesubmission->submission = $submission->id;
        $filesubmission->assignment = $this->assignment->get_instance()->id;

        if (!$DB->insert_record('assignsubmission_voicerec', $filesubmission) > 0) {
            $log .= get_string('couldnotconvertsubmission', 'mod_assign', $submission->userid);
            return false;
        }

        // now copy the area files
        $this->assignment->copy_area_files_for_upgrade($oldcontext->id,
                                                        'mod_assignment',
                                                        'submission',
                                                        $oldsubmission->id,
                                                        // New file area
                                                        $this->assignment->get_context()->id,
                                                        'assignsubmission_voicerec',
                                                        ASSIGN_FILEAREA_SUBMISSION_VOICEREC,
                                                        $submission->id);

        return true;
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @global moodle_database $DB
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // will throw exception on failure
        $DB->delete_records('assignsubmission_voicerec', array('assignment'=>$this->assignment->get_instance()->id));

        return true;
    }

    /**
     * Formatting for log info
     *
     * @param stdClass $submission The submission
     *
     * @return string
     */
    public function format_for_log(stdClass $submission) {
        // format the info for each submission plugin add_to_log
        $filecount = $this->count_files($submission->id, ASSIGN_FILEAREA_SUBMISSION_VOICEREC);
        $fileloginfo = '';
        $fileloginfo .= ' the number of file(s) : ' . $filecount . " file(s).<br>";

        return $fileloginfo;
    }

    /**
     * Return true if there are no submission files
     */
    public function is_empty(stdClass $submission) {
        return $this->count_files($submission->id, ASSIGN_FILEAREA_SUBMISSION_VOICEREC) == 0;
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(ASSIGN_FILEAREA_SUBMISSION_VOICEREC=>$this->get_name());
    }

}
