<?php

define('ABS_PATH', '../');
require_once ABS_PATH.'classes/rest_server.php';
require_once ABS_PATH.'classes/utils.php';
require_once ABS_PATH.'classes/validate.php';
require_once ABS_PATH.'classes/question_answers.php';
require_once ABS_PATH.'classes/logger.php';

class discussionsModel extends indexModel
{
    private $state;
    
    function __construct($state)
    {
        $this->state = $state;
        
        $this->state->process_auth_key();
        
        if ($this->state->method == 'get' && $this->state->params[2] == 'recent')
        {
            $this->recent_questions();
        }
        
        // api/discussions/question/
        elseif ($this->state->method == 'get' && $this->state->params[2] == 'question')
        {
            $question_id = isset($this->state->params[3]) ? $this->state->params[3] : null;
            $action = isset($this->state->params[4]) ? $this->state->params[4] : null;

            // api/discussions/question/1/answers/
            if ($action == 'answers')
            {
                $this->get_answers($question_id);
            }
        }
        
        // api/discussions/question/ask/
        elseif ($this->state->method == 'post' && $this->state->params[2] == 'question'
                                               && $this->state->params[3] == 'ask')
        {
            $action = isset($this->state->params[3]) ? $this->state->params[3] : null;

            // api/discussions/question/1/answers/
            if ($action == 'ask')
            {
                $this->ask_question();
            }
        }
        
        // api/discussions/question/<question_id>/add_answer/
        elseif ($this->state->method == 'post' && $this->state->params[2] == 'question'
                                               && $this->state->params[4] == 'add_answer')
        {
            $this->add_answer();
        }
        
        // api/discussions/mine/questions/
        elseif ($this->state->method == 'get' && $this->state->params[2] == 'mine'
                                              && $this->state->params[3] == 'questions')
        {
            $this->my_questions();
        }
        
        // api/discussions/mine/answers/
        elseif ($this->state->method == 'get' && $this->state->params[2] == 'mine'
                                              && $this->state->params[3] == 'answers')
        {
            $this->my_answers();
        }
        
        elseif ($this->state->method == 'get' && !empty ($this->state->request_vars['search']))
        {
            $this->search_discussions();
        }
    }
    
    private function recent_questions()
    {
        $limit = isset($this->state->request_vars['count']) ? $this->state->request_vars['count'] : null;
        $discussion = new QuestionAnswer;
        $recent_qns = $discussion->getAllQuestions($limit, false);

        if ($recent_qns === false)
        {
            RestServer::send_response(500, array('error' => 'Server is sick. Please try again later.'));
        }

        RestServer::send_response('200', array('message' => $recent_qns));
    }
    
    private function get_answers($question_id)
    {
        if (!is_numeric($question_id))
        {
            RestServer::send_response(400, array('error' => 'Invalid question identifier.'));
        }
        
        $limit = 10;
        
        $discussion = new QuestionAnswer;
        $answers = $discussion->getAnswers($question_id, $limit);
        $answers = $answers ? $answers : array();
        
        RestServer::send_response(200, array('message' => $answers));
    }
    
    private function ask_question()
    {
        if (empty($this->state->request_vars['question']))
        {
            RestServer::send_response(400, array('error' => 'Please provide the question.'));
        }
        
        $discussion = new QuestionAnswer;
        
        $qn_id = $discussion->askQuestion($this->state->user_id, $this->state->user_type, $this->state->request_vars['question']);

        if ($qn_id)
        {
            $response_array = array('message' => 'The question was submitted.', 'question_id' => $qn_id);
            RestServer::send_response(201, $response_array);
        }
        else
        {
            RestServer::send_response(500, array('error' => 'Unable to ask the question at this time. Please try again later.'));
        }
    }
    
    private function add_answer()
    {
        $question_id = isset($this->state->params[3]) ? $this->state->params[3] : null;
        $answer = !empty($this->state->request_vars['answer']) ? $this->state->request_vars['answer'] : null;

        if (!is_numeric($question_id))
        {
            RestServer::send_response(400, array('error' => 'Invalid question identifier.'));
        }

        if (!$answer)
        {
            RestServer::send_response(400, array('error' => 'Please provide an answer.'));
        }
        
        $discussion = new QuestionAnswer;
        $answered = $discussion->addAnswer($this->state->user_id, $this->state->user_type, $question_id, $answer);
        
        if ($answered)
        {
            RestServer::send_response(201, array('message' => 'Answer was submitted.'));
        }
        else
        {
            RestServer::send_response(500, array('error' => 'We could\'t save your answer. Please try again.'));
        }
    }
    
    private function my_questions()
    {
        $discussion = new QuestionAnswer;
        $limit = is_numeric($this->state->request_vars['limit']) ? $this->state->request_vars['limit'] : WEBSERVICE_DISCUSSIONS_SEARCH_RESULTS_DEFAULT_COUNT;
        $my_questions = $discussion->getMyQuestions($this->state->business_id, $this->state->user_type, $limit);
        $my_questions = $my_questions ? $my_questions : array();
        
        RestServer::send_response(200, array('message' => $my_questions));
    }
    
    private function my_answers()
    {
        $discussion = new QuestionAnswer;
        $limit = is_numeric($this->state->request_vars['limit']) ? $this->state->request_vars['limit'] : WEBSERVICE_DISCUSSIONS_SEARCH_RESULTS_DEFAULT_COUNT;
        $my_answers = $discussion->getMyAnswers($this->state->business_id, $this->state->user_type, $limit);
        $my_answers = $my_answers ? $my_answers : array();
        
        RestServer::send_response(200, array('message' => $my_answers));
    }


    private function search_discussions()
    {
        $search_terms = $this->state->request_vars['search'];
        
        if (empty($search_terms))
        {
            RestServer::send_response(400, 'Please provide search keyword.');
        }
        
        $limit = isset($this->state->request_vars['limit']) ? $this->state->request_vars['limit'] : WEBSERVICE_DISCUSSIONS_SEARCH_RESULTS_DEFAULT_COUNT;
        $limit = is_numeric($limit) ? $limit : null;
        
        $discussion = new QuestionAnswer;
        $results = $discussion->searchQuestion($search_terms, $limit, false);
        $results = $results ? $results : array();
        
        RestServer::send_response(200, array('message' => $results));
    }
}