<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Statements extends Parent_controller {
	public function __construct()
	{
        $this->initialize();
        parent::__construct();
        $this->load->model('Tincan_model');
    }
    
    public static $endpoint;
    public static $auth;
    
    function initialize(){
        //At the End of the endpoints please add /statements
        self::$endpoint = "Your-Tin-can-Api Endpoint";
        $user = "Your-Scorm-Email";
        $pass = "Your-Scorm-Password";
        $cred = $user.':'.$pass;
        self::$auth = base64_encode($cred);   
        return false;
    }
    
    public function index()
    {
        $request_body = file_get_contents('php://input');
        $request_body = urldecode($request_body);
        $malformed_jsons = explode('"',$request_body);
        print_r($malformed_jsons);
        $record=array();
        foreach($malformed_jsons as $key=>$value){
            if(strstr($value,'course_id')){
                $record['courseid'] = $value;
            }else if($value == 'verb'){
                $record['verb'] = $malformed_jsons[$key+2];
            }else if($value == 'en-US'){
                $record['en-US'] = $malformed_jsons[$key+2];
            }else if($value == 'object'){
                $record['object'] = $malformed_jsons[$key+4];
            }else if($value == 'success'){
                $record['result'] = $malformed_jsons[$key+1];
            }else if($value == 'score'){
                $record['score'] = $malformed_jsons[$key+3];
            }else if($value == 'max'){
                $record['max'] = $malformed_jsons[$key+1];
            }else if($value == 'raw'){
                $record['raw'] = $malformed_jsons[$key+1];
            }else if($value == 'response'){
                $record['answer'] = $malformed_jsons[$key+2];
            }else if($value == 'und'){
                $record['slideName'] = $malformed_jsons[$key+2];
            }else if($value == 'completion'){
                $record['completion'] = $malformed_jsons[$key+1];
            }else if($value == 'min'){
                $record['minimum'] = $malformed_jsons[$key+1];
            }else if($value == 'correctResponsesPattern'){
                $record['correctResponsesPattern'] = $malformed_jsons[$key+2];
                
            }
        }
        //print_r($record); -- for Debugging
        $statements = $this->build_statements($record,$malformed_jsons);
        //print_r($statements); -- for Debugging
        
        
        $this->open_connection($statements, "Your-Tin-can-Api Endpoint/statements"); 
    }
    
    function make_request($record){
        $statements = $this->build_statements($record);
//        print_r()
//        return $this->open_connection($statements, "https://cloud.scorm.com/tc/1NJOMPAQAK/sandbox/statements/");
    }
    
    function open_connection($data, $url){ 
        $this->initialize();
        $streamopt = array(
            'ssl' => array(
                'verify-peer' => false,
            ),
            'http' => array(
                'method' => 'POST',
                'ignore_errors' => true,
                'header' =>  array(
                    'Authorization: '.sprintf('Basic %s',self::$auth ),
                    'Content-Type: application/json',
                    'Accept: application/json, */*; q=0.01',
                ),
                'content' => json_encode($data),
            ),
        );

        $context = stream_context_create($streamopt);

        $stream = fopen($url, 'rb', false, $context);

        $ret = stream_get_contents($stream);

        $meta = stream_get_meta_data($stream);
        if ($ret) {
            $ret = json_decode($ret);
        }
        print_r(array($ret, $meta));
    }
    
    function build_statements($record,$malformed_jsons){
        
        $tincan_statement = array();
        $type = $record['verb'];
        if(!empty($record['slideName']))
        {
            $course_id = $record['slideName'];
        }else
        {
            $course_id = $record['courseid'];   
        }
        
        $recorded_time = 'T';
        //VERB LIST : “experienced”, “attended”, “attempted”, “completed”, “passed”, “failed”, “answered”, “interacted”, “imported”, “created”, “shared”, and “voided”
        $instructor = $this->session->userdata('userName');
        $actor=array(
                            'name' =>  array($instructor),
                            'mbox'  => array('mailto:wajahatanwar56@gmail.com'),
                            'objectType' => 'Person',
                        );
        if($type == 'answered'|| $record['en-US'] == 'answered')
        {
            if($record['en-US'] == 'answered')
            {
                $type == 'answered';
            }
            
            $score1 = str_replace(":","",$record['score']);
            $score2 = str_replace("}","",$score1);
            $score3 = str_replace(",","",$score2);
             if(empty($score3))
             {
                 $score3 = 0;
             }
            
            $result1 = str_replace(":","",$record['result']);
            $result2 = str_replace(",","",$result1);
            
            $tincan_statement=array(
                array(
                    'actor' => $actor,
                    'verb' => 'answered',
                    'object' => array(
                        'objectType' => 'Activity',
                        'id' => $course_id,
                        'definition' => array(
                            'name' => array('en-US'=>$course_id),
                            'description' => array('en-US'=> $course_id),
                        ),
                    ),
                    'context' => array(
                        'instructor' => array(
                            'name' => array("wajahta"),
                            'mbox' => array('mailto:wajahatanwar56@gmail.com'),
                            'objectType'=>'Person'
                        ),
                        'contextActivities' =>array(
                            'parent' => array(
                                'id' => $course_id,
                                ),
                            ),

                    ),
                    'result' => array(
                        'score'=> array(
                            'raw'=> $score3,
                        ),
                        'success' => $result2,
                    ),
                )
            );
            $course_name = $record['correctResponsesPattern'];
            $lesson_id = $this->session->userdata('lessonID');
            $course_id_num = $this->session->userdata('courseID');
            $user_id_num = $this->session->userdata('userID');
            $quiz_id = $this->session->userdata('quizID');
            $result = $this->Tincan_model->add_answer_detail($lesson_id,$course_id_num,$user_id_num,$course_name,$score3,$result2,$type);
            $compile_report = $this->show_report($user_id_num,$course_id_num,$quiz_id);
        }
        else if($type == 'experienced')
        {
            if($type == 'display')
            {
                $type ="interacted";
            }
            $tincan_statement=array(
                array(
                    'actor' => $actor,
                    'verb' => $type,
                    'object' => array(
                        'objectType' => 'Activity',
                        'id' => $course_id,
                        'definition' => array(
                            'name' => array('en-US'=>$course_id),
                            'description' => array('en-US'=> $course_id),
                        ),
                    ),
                    'context' => array(
                        'instructor' => array(
                            'name' => array("wajahta"),
                            'mbox' => array('mailto:wajahatanwar56@gmail.com'),
                            'objectType'=>'Person'
                        ),
                        'contextActivities' =>array(
                            'parent' => array(
                                'id' => $course_id,
                                ),
                            ),

                    ),
                )
            );
        }
        else if($type == 'attempted')
        {
             $tincan_statement=array(
                array(
                    'actor' => $actor,
                    'verb' => $type,
                    'object' => array(
                        'objectType' => 'Activity',
                        'id' => $course_id,
                        'definition' => array(
                            'name' => array('en-US'=>$course_id),
                            'description' => array('en-US'=> $course_id),
                        ),
                    ),
                    'context' => array(
                        'instructor' => array(
                            'name' => array("wajahta"),
                            'mbox' => array('mailto:wajahatanwar56@gmail.com'),
                            'objectType'=>'Person'
                        ),
                        'contextActivities' =>array(
                            'parent' => array(
                                'id' => $course_id,
                                ),
                            ),

                    ),
                )
            );
        }
        else if($type == 'passed' || $type == 'failed')
        {
            $flag_minimum = false;
             foreach($malformed_jsons as $key=>$value){
               if($value == 'raw'){
                $record['score'] = $malformed_jsons[$key+1];
               }else if($value == 'min')
               {
                   $record['minimum'] = $malformed_jsons[$key+1];
                   $flag_minimum = true;
               }
             }
            if($flag_minimum)
            {
                preg_match_all('/\d+/', $record['minimum'], $mini);
                $mini = $mini['0']['0'];
            }else
            {
               $mini = 0; 
            }
            preg_match_all('/\d+/', $record['score'], $array);
            preg_match_all('/\d+/', $record['max'], $maxi);
            $score3 = $array['0']['0'];
            $max2 = $maxi['0']['0'];
            
            
            if(!empty($score3) && $score3 > 1)
            {
                $tincan_statement=array(
                    array(
                        'actor' => $actor,
                        'verb' => $type,
                        'object' => array(
                            'objectType' => 'Activity',
                            'id' => $course_id,
                            'definition' => array(
                                'name' => array('en-US'=>$course_id),
                                'description' => array('en-US'=> $course_id),
                            ),
                        ),
                        'context' => array(
                            'instructor' => array(
                                'name' => array($instructor),
                                'mbox' => array('mailto:wajahatanwar56@gmail.com'),
                                'objectType'=>'Person'
                            ),
                            'contextActivities' =>array(
                                'parent' => array(
                                    'id' => $course_id,
                                    ),
                                ),

                        ),
                        'result' => array(
                            'score'=> array(
                                'raw'=> $score3,
                                'min'=> 0,
                                'max' => $max2
                            ),
                            'completion' => 'true',
                            'success' => 'true',
                        ),
                    )
                );
                
                $user_id = $this->session->userdata('userID');
                $course_id = $this->session->userdata('courseID');
                $gained_score = $score3;
                $total_score = $max2;
                
                $result = $this->Tincan_model->add_info($user_id,$course_id,$gained_score,$total_score,$mini,$type);
                
            }
        }  
        else
        {
            if(isset($record['completion']))
            {
                $completed = $record['completion'];
            }
            if(empty($completed) || $completed == false)
            {
                $type = 'experienced';   
            }else 
            {
               $type == 'compeleted';
            }
            $tincan_statement=array(
                array(
                    'actor' => $actor,
                    'verb' => $type,
                    'object' => array(
                        'objectType' => 'Activity',
                        'id' => $course_id,
                        'definition' => array(
                            'name' => array('en-US'=>$course_id),
                            'description' => array('en-US'=> $course_id),
                        ),
                    ),
                    'context' => array(
                        'instructor' => array(
                            'name' => array("wajahta"),
                            'mbox' => array('mailto:wajahatanwar56@gmail.com'),
                            'objectType'=>'Person'
                        ),
                        'contextActivities' =>array(
                            'parent' => array(
                                'id' => $course_id,
                                ),
                            ),

                    ),
                )
            );
        }
        return $tincan_statement;
    }
}