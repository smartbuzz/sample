<?php

class HomeController extends AppController {

    var $name = 'Home';
    var $uses = array('Mother', "Provider");
    var $helpers = array('Html', 'Form', 'Javascript', 'Ajax', 'Session');
    var $components = array('RequestHandler', 'Email', "Session", "Cookie", "Common");

    function beforeFilter() {
        parent::beforeFilter();
        $this->Common->session = $this->Session->read();
    }

    public function beforeRender() {
        
        parent::beforeRender();       
        header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0"); // // HTTP/1.1
        header("Pragma: no-cache");
        header("Expires: Mon, 17 Dec 2007 00:00:00 GMT"); // Date in the pas
    }

    function index() { 
        /* start for poll */
	App::import('model', 'pollquestion');
        $this->pollquestions = new pollquestion();
        //$Pollquestiondata = $this->pollquestions->find("all", array('conditions' => array('id' => '2')));
        //$Pollquestiondata = $this->pollquestions->find("all", array('order'=>'id DESC','limit'=>'0,1'));
        $Pollquestiondata1 = $this->pollquestions->getPollQuestion();
       // pr($Pollquestiondata1);

        $Pollquestiondata = @$Pollquestiondata1[0]['pollquestions'];
		$this->set("pollquestiondata", $Pollquestiondata);
        $pol_question_id = $Pollquestiondata['id'];

        App::import('model', 'pollanswers');
        $this->pollanswers = new pollanswers();
        $pollquestionanswer = $this->pollanswers->find('all', array('conditions' => array('question_id' => $pol_question_id)));
        $this->set("pollquestionanswers", $pollquestionanswer);

        if (isset($_REQUEST['polsubmitted'])) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $already_voted = $this->pollanswers->checkvalidip($ip_address, $pol_question_id);
           
            if ($already_voted < 1) {
                $created = date('Y-m-d h:m:s');
                $insert_poll_ans = "insert into pollresults set question_id='" . $_REQUEST['pol_question_id'] . "',answer_id='" . $_REQUEST['polans'] . "', created='" . $created . "',ip='" . $_SERVER['REMOTE_ADDR'] . "'";
                mysql_query($insert_poll_ans);
                $poll_submitted = '1';
                $this->set('poll_submitted', $poll_submitted);
            } else {
                $poll_submitted = '1';
                $this->set('poll_submitted', $poll_submitted);
                $already_voted = '1';
                $this->set('already_voted', $already_voted);
            }
        }
        if (isset($_SESSION['user_id'])) {
            $this->redirect(SITEURL . "moeders/home");
        }
        
      
        /*App::import('model', 'Sitesearch');
        $this->Search = new Sitesearch();
        $search = $this->Search->find('all', array('fields' => array('Sitesearch.urltosend', 'Sitesearch.valuetoshow')));
        $searchdata = Set::combine($search, '{n}.Sitesearch.urltosend', '{n}.Sitesearch.valuetoshow');
		$this->set("searchData", $searchdata);
		*/
        
        $this->pageTitle = 'Mamazoekt | Home';
        $this->layout = 'default';
    }

    function login($loginFor="mother", $is_ajax=true) { 

        $this->autoRender = false;
        //$this->layout = "ajax";
        $this->autoRender=$this->layout = false;
        $remember_me = false;
        $user_cookie = "";
        $pass_cookie = "";
        
        if (empty($this->data)) {

            if ($is_ajax != false) {
                echo "1";
                exit;
            }
        }

        if (isset($this->data['Mother'])) {
            $email = $this->data['Mother']['username'];
            $password = $this->data['Mother']['password'];
            if ($this->data['Mother']['remember'] == "1") {
                $remember_me = true;
            }
            $i = $this->_motherLogin($email, $password, $remember_me);
            //$user_cookie = $this->data['Mother']['username'];
            //$pass_cookie = $this->data['Mother']['password'];
        } else if (isset($this->data['Provider'])) {
            $email = $this->data['Provider']['username'];
            $password = $this->data['Provider']['password'];
            if ($this->data['Provider']['remember'] == "1") {
                $remember_me = true;
            }
            $i = $this->_providerLogin($email, $password, $remember_me);
        }

        if ($i == false) {
		    echo "2";
            exit;
        } else {
			
			if(isset($_SESSION['pr_userid']))
			{
				if (empty($_SESSION["org_email"]) || empty($_SESSION["website"]) || empty($_SESSION["org_phone"]) || empty($_SESSION["reg_number"]) || empty($_SESSION["bank_acc_num"]) || empty($_SESSION["to"]) || empty($_SESSION["finance_dept_email"])) {
					echo "0"."##profileProvider";
				}
				else
				{
					echo "0"."##dashboardProvider";
				}
			}
			else
			{	
				   echo "0"."##dashboardMother";
				   //echo "0##".$_SESSION['phase_id'];
			}
            exit;
        }
    }

    function _motherLogin($email, $password, $remember_me) {

        $this->autoRender = false;
        $this->autoLayout = false;
        $rec = $this->Mother->find("first", array("conditions" => array("email" => $email, "password" => $password, "activated" => "1", "deleted" => "0")));
        if (!empty($rec)) {
            //destroy any session is exist
            $this->Session->destroy();

            $user_cookie = "";
            $pass_cookie = "";

            $this->Session->write('loggedin', true);
            $this->Session->write('user_id', $rec['Mother']['id']);
            $this->Session->write('first_name', $rec['Mother']['first_name']);
            $this->Session->write('user_name', $rec['Mother']['user_name']);
            $this->Session->write('last_name', $rec['Mother']['last_name']);
            $this->Session->write('postal_code', $rec['Mother']['postal_code']);
            $this->Session->write('city', $rec['Mother']['city']);
            $this->Session->write('date_of_birth', $rec['Mother']['date_of_birth']);
            $this->Session->write('created', $rec['Mother']['created']);
            $this->Session->write('phase_id', $rec['Mother']['phase_id']);
            $this->Session->write('profile_image', $rec['Mother']['photo']);
            
            
            
		if(isset($_SESSION['user_id']))
		{
			$id = $_SESSION['user_id'];
			$query = mysql_query("select * from mothers where id=".$id);
			$motherdata = mysql_fetch_array($query);
			//echo "<pre>"; print_r($motherdata);
			$dueDate = $motherdata["due_date"];
			$currentDate = date("Y-m-d");
			$days = getDaysInBetween($currentDate, $dueDate);
			$weeksCount = $days / 7;
			if (is_float($weeksCount)) {
			$lessWeekDays = $days % 7;
			} else {
			$lessWeekDays = "";
			}

			$weeks = floor($weeksCount);
			if ($weeks < 42 && !empty($lessWeekDays)) {
			$currentWeekPrev = 41 - $weeks;
			$currentWeek = $currentWeekPrev - 1;
			} elseif ($weeks < 42 && empty($lessWeekDays)) {
			$currentWeek = 41 - $weeks;
			} else {
			$currentWeek = 1;
			}

			$_SESSION["currentWeekStart"] = $currentWeek;

			$day = date('d');
			$month = date('m');
			$year = date('Y');
			$weekDates = get_week_range($day, $month, $year);
			$currentStartDate1 = $weekDates["start_date"];
			$currentEndDate1 = $weekDates["end_date"];
			$weekStartDate1 = date('d M', strtotime($currentStartDate1));
			$weekEndDate1 = date('d M', strtotime($currentEndDate1));
			$currentWeek;
			if($_SESSION['phase_id']=='1')
			{
					$_SESSION['cal_url']=$this->webroot."moeders/ovulatiekalender";
			}
			if($_SESSION['phase_id']=='2')
			{
					$calendarUrl = $this->webroot."moeders/zwangerschapskalender/".$currentWeek."/".$weekStartDate1."/".$weekEndDate1;
					$_SESSION['cal_url']=$calendarUrl;
			}
			if($_SESSION['phase_id']=='3')
			{
					$_SESSION['cal_url']=$this->webroot."moeders/kinderkalender";
			}
			 
		}
	
	    //$this->Session->write('rememberMe', $remember_me);
	    $this->Session->write('username', $rec['Mother']['email']);
	    $this->Session->write('password', $rec['Mother']['password']);
            //echo "<pre>"; if($this->Session->check('user_id')) { echo "yes"; } else { echo "no"; } die;

            if (empty($rec['Mother']['photo']))
                $this->Session->write('profile_image', 'no_img.gif');
            else
                $this->Session->write('profile_image', $rec['Mother']['photo']);
            switch ($rec['Mother']['phase_id']) {
                case "1":
                    $this->Session->write('phase_class', 'rt_banner_lft_desclog2');
                    $this->Session->write('phase_class_calender', 'a_log_rtinner_2_imglog2');
                    break;
                case "2":
                    $this->Session->write('phase_class', 'rt_banner_lft_desc');
                    $this->Session->write('phase_class_calender', 'a_log_rtinner_2_img');
                    break;
                case "3":
                    $this->Session->write('phase_class', 'rt_banner_lft_desclog3');
                    $this->Session->write('phase_class_calender', 'a_log_rtinner_2_imglog3');
                    $this->Session->write('phase_name', 'Ik ben moeder');
                    break;
                default :
                    $this->Session->write('phase_class', 'rt_banner_lft_desc');
                    $this->Session->write('phase_class_calender', 'a_log_rtinner_2_img');
                    $this->Session->write('phase_name', 'Ik ben zwanger');
                    break;
            }
            if ($remember_me == true) { 
                 $user_cookie = $rec['Mother']['email'];
                 $pass_cookie = $rec['Mother']['password'];
                 setcookie("username", $user_cookie, time() + 3600*24*30, MAMAZOEKT_PATH);
                 setcookie("password", $pass_cookie, time() + 3600*24*30, MAMAZOEKT_PATH);
            }

            return true;
        } else {
            $this->Session->write("error", true);
            $this->Session->write("opt", "Mother");
            $this->Session->write("msg", "Je e-mailadres of je wachtwoord is niet juist. Weet je je wachtwoord niet meer? Vul je e-mailadres in en klik op de link.");

            return false;
        }
    }

    function _providerLogin($email, $password, $remember_me) { 
        $this->autoRender = false;
        $this->autoLayout = false;
	
        $rec = $this->Provider->find("first", array("conditions" => array("email" => $email, "password" => $password, "activated" => "1")));
        if (!empty($rec)) {
            //destroy any session is exist
            $this->Session->destroy();
            $user_cookie = "";
            $pass_cookie = "";
            $this->Session->write('loggedin', true);
            $this->Session->write('pr_userid', $rec['Provider']['id']);
            $this->Session->write('pr_firstname', $rec['Provider']['first_name']);
            $this->Session->write('pr_lastname', $rec['Provider']['last_name']);
            $this->Session->write('pr_company_name', $rec['Provider']['company_name']);

	    //code if edit profile required entry is not filled it will redirect to edit profile page
            $middleName = $rec["Provider"]["middle_name"];
            $organizationEmail = $rec["Provider"]["organization_email"];
	    $website = $rec["Provider"]["website"];
	    $organizationPhone = $rec["Provider"]["organization_phone"];
	    $registrationNumber = $rec["Provider"]["registration_number"];
	    $bankAccountNumber = $rec["Provider"]["bank_account_number"];
	    $to = $rec["Provider"]["account_city"];
	    $financeDepartmentEmail = $rec["Provider"]["finance_department_email"];

            $this->Session->write('middle_name', $middleName);
            $this->Session->write('org_email', $organizationEmail);
			$this->Session->write('website', $website);
			$this->Session->write('org_phone', $organizationPhone);
			$this->Session->write('reg_number', $registrationNumber);
			$this->Session->write('bank_acc_num', $bankAccountNumber);
			$this->Session->write('to', $to);
			$this->Session->write('finance_dept_email', $financeDepartmentEmail);
            //end code..............................................................................

            if ($remember_me == true) { 

                $user_cookie = $rec['Provider']['email'];
                $pass_cookie = $rec['Provider']['password'];
                setcookie("usernamep", $user_cookie, time() + 3600*24*30, MAMAZOEKT_PATH);
                setcookie("passwordp", $pass_cookie, time() + 3600*24*30, MAMAZOEKT_PATH);
            }

            return true;
        } else {
            return false;
        }
    }

// Generate a random character string
    function rand_str($length = 32, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890') {
        // Length of character list
        $chars_length = (strlen($chars) - 1);

        // Start our string
        $string = $chars{rand(0, $chars_length)};

        // Generate random string
        for ($i = 1; $i < $length; $i = strlen($string)) {
            // Grab a random character from our list
            $r = $chars{rand(0, $chars_length)};

            // Make sure the same two characters don't appear next to each other
            if ($r != $string{$i - 1})
                $string .= $r;
        }

        // Return the string
        return $string;
    }

    function rand_str_number($length = 32, $chars = '1234567890') {
        // Length of character list
        $chars_length = (strlen($chars) - 1);

        // Start our string
        $string = $chars{rand(0, $chars_length)};

        // Generate random string
        for ($i = 1; $i < $length; $i = strlen($string)) {
            // Grab a random character from our list
            $r = $chars{rand(0, $chars_length)};

            // Make sure the same two characters don't appear next to each other
            if ($r != $string{$i - 1})
                $string .= $r;
        }

        // Return the string
        return $string;
    }

    function forgotmessage() {
        $this->layout = 'inner_default';
	//$this->redirect(array('controller'=>'home','action'=>'forgotmessage'));
    }

    function logout() {
	//echo "<pre>"; print_r($_COOKIE); die;
        $this->layout = 'inner_default';
         App::import('model', 'Sitesearch');
        $this->Search = new Sitesearch();
        //$conditions = array("Sitesearch.valuetoshow <> " => null);
        $search = $this->Search->find('all', array('fields' => array('Sitesearch.urltosend', 'Sitesearch.valuetoshow')));
        $searchdata = Set::combine($search, '{n}.Sitesearch.urltosend', '{n}.Sitesearch.valuetoshow');
		//pr($searchdata);//die;
        $this->set("searchData", $searchdata);
        $this->Session->destroy();
        //$this->Cookie->destroy();
        $this->Session->delete('loggedin');
        $this->Session->delete('user_id');
        $this->Session->delete('first_name');
        $this->Session->delete('last_name');

        $this->Session->delete('postal_code');
        $this->Session->delete('city');
        $this->Session->delete('user_id');
        $this->Session->delete('date_of_birth');
        $this->Session->delete('created');
        $this->Session->delete('phase_id');
        $this->Session->delete('phase_class');
        $this->Session->delete('profile_image');
        $this->Session->delete('cal_url');
        //$this->redirect('/');
    }

}
