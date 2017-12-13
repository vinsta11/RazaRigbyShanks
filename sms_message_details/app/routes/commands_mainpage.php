<?php
/**
 * Created by PhpStorm.
 * User: Shanks
 * Date: 01/12/2017
 * Time: 11:25
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->post(
    '/commands',
    function(Request $request, Response $response) use ($app) {

        /** @var  validated_username username that has undergone sanitation and validation */
        $this->validated_username = null;
        /** @var  validated_password password that has undergone sanitation and validation */
        $this->validated_password = null;
        /** @var  validated_fname password that has undergone sanitation and validation */
        $this->validated_fname = null;
        /** @var  validated_lname lname that has undergone sanitation and validation */
        $this->validated_lname = null;
        /** @var  validated_status validated that has undergone sanitation and validation */
        $this->validated_status = null;
        /** @var  arr_tainted_params the parsed values from the previous form, unclean and needing sanitation */
        $this->arr_tainted_params = $request->getParsedBody();


        /** @var  validator_obj the object used to sanitise and validate string throughout the app */
        $this->validator_obj = $this->get('sanitised_validator');
        /** @var  profile_obj an object used to model the user profile's information */
        $this->profile_obj = $this->get('profile_model');
        /** @var  session_wrapper used to read and write from session files */
        $this->session_wrapper = $this->get('session_wrapper');
        /** @var  session_obj used to format the necessary information to save to session files */
        $this->session_obj = $this->get('session_model');

        /** @var  base64_wrapper used to encode/decode information for plaintext read/write */
        $this->base64_wrapper = $this->get('base64_wrapper');
        /** @var  bcrypt_wrapper used to hash user passwords using the bcrypt algorithm */
        $this->bcrypt_wrapper = $this->get('bcrypt_wrapper');
        /** @var  openssl_wrapper used to encrypt/decrypt sensitive user information */
        $this->openssl_wrapper = $this->get('openssl_wrapper');

        /** the session object has to have multiple wrappers added to it
         *  these include the encoding, hashing, and encryption classes
         *  at the moment encryption requires the session_id, so this is also added
         *  lastly, the labels in the session file are also encrypted to obfuscate the information
         */
        $this->session_obj->set_wrapper_session_file($this->session_wrapper);
        $this->session_obj->set_base64_wrapper($this->base64_wrapper);
        $this->session_obj->set_bcrypt_wrapper($this->bcrypt_wrapper);
        $this->session_obj->set_openssl_wrapper($this->openssl_wrapper);
        $this->session_obj->set_sid(session_id());
        $this->session_obj->generate_labels();

        $this->page_text = $this->validator_obj->sanitise_string('The following information is incorrect: ');

        /**
         * At this point, the app checks what the last operation was based on parsedtext
         * there are four main options: login, signup, (empty), and other
         * the first three are legitimate, whereas the last is not
         * the first two are straight-forward, the third just means the user came from another page
         * the last means a malicious user is trying to perform something unwanted, this leads to a logout scenario
         */
        if (isset($this->arr_tainted_params['source'])) {
            $tainted_source = $this->arr_tainted_params['source'];
            $this->validated_source = $this->validator_obj->sanitise_string($tainted_source);
            unset($this->arr_tainted_params['source']);

            //  What follows is the source if one has just logged in
            if(stristr($this->validated_source, 'login')){

                /**
                 * Since the user has logged in, the app checks if there is username and password information
                 * If there is, this information is sanitised and saved for later use
                 */
                if (isset($this->arr_tainted_params['username']) && isset($this->arr_tainted_params['password']))                {
                    $tainted_username = $this->arr_tainted_params['username'];
                    $this->validated_username = $this->validator_obj->validate_username($tainted_username);

                    $tainted_password = $this->arr_tainted_params['password'];
                    $this->validated_password = $this->validator_obj->validate_password($tainted_password);
                }

                //  This will be removed, as it is only here until we implement the mysql database
                //  Todo: remove this backdoor

                if($this->validated_username == 'root' && $this->validated_password == 'toor'){
                    $this->validated_fname = $this->validator_obj->sanitise_string('Rooty');
                    $this->validated_lname = $this->validator_obj->sanitise_string('Toot');
                    $this->validated_status = true;
                }
                elseif($this->validated_username == null && $this->validated_password == null){
                    $this->session_obj->retrieve_secure_data();

                    $this->validated_username = $this->validator_obj->sanitise_string($this->session_obj->perform_detail_retrieval('username'));
                    $this->validated_password = $this->validator_obj->sanitise_string($this->session_obj->perform_detail_retrieval('password'));
                    $this->validated_fname = $this->validator_obj->sanitise_string($this->session_obj->perform_detail_retrieval('fname'));
                    $this->validated_lname = $this->validator_obj->sanitise_string($this->session_obj->perform_detail_retrieval('lname'));
                    $this->validated_status = $this->validator_obj->sanitise_string($this->session_obj->perform_detail_retrieval('validated'));
                }

                //  this is the end of what needs to be removed


                /** If the user information has been successfully authenticated, then return the main page of the app */
                if( $this->validated_status){


                    $this->profile_obj->set_parameters($this->validated_username, $this->validated_password, $this->validated_fname, $this->validated_lname);

                    $this->page_text = $this->validator_obj->sanitise_string('Please select the desired command.');

                    $this->session_obj->set_session_profile($this->profile_obj);//
                    $this->session_obj->set_sid($this->validator_obj->sanitise_string(session_id()));
                    $this->session_obj->store_secure_data();

                    $this->validated_fname = $this->validator_obj->sanitise_string($this->profile_obj->perform_detail_retrieval('fname'));
                    $this->validated_lname = $this->validator_obj->sanitise_string($this->profile_obj->perform_detail_retrieval('lname'));

                    return $this->view->render($response,
                        'commands_page.html.twig',
                        [
                            'css_path' => CSS_PATH,
                            'landing_page' => LANDING_PAGE,
                            'method_get' => 'get',
                            'method_post' => 'post',
                            'action_saved' => './commands/saved',
                            'action_download' => './commands/download',
                            'action_display' => './commands/display',
                            'action_filter' => './commands/filter',
                            'action_send' => './commands/send',
                            'action_change' => './commands/change',
                            'action_logout' => './commands/logout',
                            'initial_input_box_value' => null,
                            'page_title' => APP_NAME,
                            'page_heading_1' => APP_NAME,
                            'greeting_text' => 'Welcome Back ' . $this->validated_fname . ' ' . $this->validated_lname,
                            'page_text' => $this->page_text,
                        ]);
                }

                /** Otherwise, let the user know which part of the information was incorrect, and return to the login_page */
                else{

                    if( $this->validated_username != "root" ){
                        $this->page_text .= 'username ';
                    }

                    if( stristr($this->validated_password, "Unacceptable Password")){
                        $this->page_text .= 'password';
                    }


                    return $this->view->render($response,
                        'alert_layout.html.twig',
                        [
                            'css_path' => CSS_PATH,
                            'landing_page' => LANDING_PAGE,
                            'method' => 'get',
                            'action' => './',
                            'initial_input_box_value' => null,
                            'page_title' => APP_NAME,
                            'page_heading_1' => APP_NAME,
                            'page_heading_2' => $this->validator_obj->sanitise_string('Invalid Information'),
                            'page_text' => $this->validator_obj->sanitise_string($this->page_text),
                        ]);
                }


            }
            /** This is the start of the sign up procedure */
            elseif(stristr($this->validated_source, 'signup')){

                //** the parsed data is checked for the relevant information, sanitised, and saved for later use */
                if (isset($this->arr_tainted_params['username']) && isset($this->arr_tainted_params['password']) &&
                    isset($this->arr_tainted_params['fname']) && isset($this->arr_tainted_params['lname']))
                {
                    $tainted_username = $this->arr_tainted_params['username'];
                    $this->validated_username = $this->validator_obj->validate_username($tainted_username);

                    $tainted_password = $this->arr_tainted_params['password'];
                    $this->validated_password = $this->validator_obj->validate_password($tainted_password);

                    $tainted_fname = $this->arr_tainted_params['fname'];
                    $this->validated_fname = $this->validator_obj->sanitise_string($tainted_fname);

                    $tainted_lname = $this->arr_tainted_params['lname'];
                    $this->validated_lname = $this->validator_obj->sanitise_string($tainted_lname);
                }

                /** If the password returns as unacceptable, let the user know why specifically it is wrong, and return to login_page */
                if(stristr($this->validated_password, 'Unacceptable Password')){
                    return $this->view->render($response,
                        'alert_layout.html.twig',
                        [
                            'css_path' => CSS_PATH,
                            'landing_page' => LANDING_PAGE,
                            'method' => 'get',
                            'action' => './',
                            'initial_input_box_value' => null,
                            'page_title' => APP_NAME,
                            'page_heading_1' => APP_NAME,
                            'page_heading_2' => $this->validator_obj->sanitise_string('Invalid Information'),
                            'page_text' => $this->validated_password,
                        ]);
                }
                else{

                    $this->page_text = $this->validator_obj->sanitise_string('Please select the desired command.');
                    $this->profile_obj->set_parameters($this->validated_username, $this->validated_password, $this->validated_fname, $this->validated_lname);

                    $this->session_obj->set_session_profile($this->profile_obj);
                    $this->session_obj->store_secure_data();

                    /** display the main page of the app if everything is in order */

                    return $this->view->render($response,
                        'commands_page.html.twig',
                        [
                            'css_path' => CSS_PATH,
                            'landing_page' => LANDING_PAGE,
                            'method_get' => 'get',
                            'method_post' => 'post',
                            'action_saved' => '../commands/saved',
                            'action_download' => '../commands/download',
                            'action_display' => '../commands/display',
                            'action_filter' => '../commands/filter',
                            'action_send' => '../commands/send',
                            'action_change' => '../commands/change',
                            'action_logout' => '../commands/logout',
                            'initial_input_box_value' => null,
                            'page_title' => APP_NAME,
                            'page_heading_1' => APP_NAME,
                            'greeting_text' => $this->validator_obj->sanitise_string('Welcome For the First Time ' . $this->validated_fname . ' ' . $this->validated_lname),
                            'page_text' => $this->page_text,
                        ]);
                }

            }
            /** Passive-aggressively let the user know that they did something unexpected, send them back to login_page */
            else{
                $this->page_text = $this->validator_obj->sanitise_string('You have found an error, you must re-enter your information.');

                return $this->view->render($response,
                    'alert_layout.html.twig',
                    [
                        'css_path' => CSS_PATH,
                        'landing_page' => LANDING_PAGE,
                        'method' => 'get',
                        'action' => './',
                        'initial_input_box_value' => null,
                        'page_title' => APP_NAME,
                        'page_heading_1' => APP_NAME,
                        'page_heading_2' => $this->validator_obj->sanitise_string('Unknown Source'),
                        'page_text' => $this->page_text,
                    ]);
            }
        }
        /** The user has come back from one of the commands */
        else{

            $this->session_obj->set_wrapper_session_file($this->session_wrapper);
            $this->session_obj->retrieve_secure_data();

            $this->validated_username = $this->validator_obj->sanitise_string($this->session_obj->perform_detail_retrieval('username'));
            $this->validated_password = $this->validator_obj->sanitise_string($this->session_obj->perform_detail_retrieval('password'));
            $this->validated_fname = $this->validator_obj->sanitise_string($this->session_obj->perform_detail_retrieval('fname'));
            $this->validated_lname = $this->validator_obj->sanitise_string($this->session_obj->perform_detail_retrieval('lname'));
            $this->validated_status = $this->validator_obj->sanitise_string($this->session_obj->perform_detail_retrieval('validated'));

            if( $this->validated_status){

                $this->profile_obj->set_parameters($this->validated_username, $this->validated_password, $this->validated_fname, $this->validated_lname);

                $this->page_text = $this->validator_obj->sanitise_string('Please select the desired command.');

                $this->session_obj->set_session_profile($this->profile_obj);
                $this->session_obj->set_sid($this->validator_obj->sanitise_string(session_id()));
                $this->session_obj->set_wrapper_session_file($this->session_wrapper);
                $this->session_obj->store_secure_data();

                return $this->view->render($response,
                    'commands_page.html.twig',
                    [
                        'css_path' => CSS_PATH,
                        'landing_page' => LANDING_PAGE,
                        'method_get' => 'get',
                        'method_post' => 'post',
                        'action_saved' => './commands/saved',
                        'action_download' => './commands/download',
                        'action_display' => './commands/display',
                        'action_filter' => './commands/filter',
                        'action_send' => './commands/send',
                        'action_change' => './commands/change',
                        'action_logout' => './commands/logout',
                        'initial_input_box_value' => null,
                        'page_title' => APP_NAME,
                        'page_heading_1' => APP_NAME,
                        'greeting_text' => $this->validator_obj->sanitise_string('Welcome Back ' . $this->validated_fname . ' ' . $this->validated_lname),
                        'page_text' => $this->page_text,
                    ]);
            }

            else{

                if( $this->validated_username != "root" ){
                    $this->page_text .= 'username ';
                }

                if( stristr($this->validated_password, 'Unacceptable Password')){
                    $this->page_text .= 'password';
                }


                return $this->view->render($response,
                    'alert_layout.html.twig',
                    [
                        'css_path' => CSS_PATH,
                        'landing_page' => LANDING_PAGE,
                        'method' => 'get',
                        'action' => '../',
                        'initial_input_box_value' => null,
                        'page_title' => APP_NAME,
                        'page_heading_1' => APP_NAME,
                        'page_heading_2' => $this->validator_obj->sanitise_string('Invalid Information'),
                        'page_text' => $this->validator_obj->sanitise_string($this->page_text),
                    ]);
            }


        }



    })->setName('commands');
















