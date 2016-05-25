<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\ussd_logs;
use App\ussd_menu;
use App\ussd_menu_items;
use App\ussd_response;
use App\ussd_user;

class UssdController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        error_reporting(0);
        header('Content-type: text/plain');
        set_time_limit(100);

       
        //get inputs
        $sessionId = $_REQUEST["sessionId"];
        $serviceCode = $_REQUEST["serviceCode"];
        $phoneNumber = $_REQUEST["phoneNumber"];
        $text = $_REQUEST["text"];   //


        $data = ['phone' => $phoneNumber, 'text' => $text, 'service_code' => $serviceCode, 'session_id' => $sessionId];

        //log USSD request
        ussd_logs::create($data);

        //verify that the user exists
        $no = substr($phoneNumber, -9);

        $user = ussd_user::where('phone', "0" . $no)->orWhere('phone', "254" . $no)->first();

        if (!$user) {
            //if user phone doesn't exist, we check out if they have been registered to mifos
            $usr = array();
            $usr['phone'] = "0".$no;
            $usr['session'] = 0;
            $usr['progress'] = 0;
            $usr['confirm_from'] = 0;
            $usr['menu_item_id'] = 0;

            $user = ussd_user::create($usr);
        }


        if (self::user_is_starting($text)) {
            //lets get the home menu
            //reset user
            self::resetUser($user);
            //user authentication
            $message = '';
            //get the home menu

            $menu = ussd_menu::find(1);
            //next menu switch
            $response = self::nextMenuSwitch($user,$menu);
            // $response = self::getMenuAndItems($user,1);
            self::sendResponse($response, 1, $user);
        } else {

            //message is the latest stuff
            $result = explode("*", $text);
            if (empty($result)) {
                $message = $text;
            } else {
                end($result);
                // move the internal pointer to the end of the array
                $message = current($result);
            }

            switch ($user->session) {

                case 0 :
                    //neutral user
                    break;
                case 1 :
                    $response = self::continueUssdMenuProcess($user, $message);
                    //echo "Main Menu";
                    break;
                case 2 :
                    //confirm USSD Process
                    $response = self::confirmUssdProcess($user, $message);
                    break;
                case 3 :
                    //Go back menu
                    $response = self::confirmGoBack($user, $message);
                    break;
                case 4 :
                    //Go back menu
                    $response = self::resetPIN($user, $message);
                    break;
                default:
                    break;
            }

            self::sendResponse($response, 1, $user);
        }

    }
    //continue USSD Menu Progress

    public function continueUssdMenuProcess($user,$message){

        $menu = ussd_menu::find($user->menu_id);

        //check the user menu
        switch ($menu->type) {
            case 0:
                //authentication mini app

                break;
            case 1:
                //continue to another menu
                $response = self::continueUssdMenu($user,$message,$menu);
                break;
            case 2:
                //continue to a processs
                $response = self::continueSingleProcess($user,$message,$menu);
                break;
            case 3:
                //infomation mini app
                //
                self::infoMiniApp($user,$menu);
                break;
            default :
                self::resetUser($user);
                $response = "An error occurred";
                break;
        }

        return $response;

    }
    //info mini app

    public function infoMiniApp($user,$menu){

        echo "infoMiniAppbased on menu_id";
        exit;

        switch ($menu->id) {
            case 4:
                //get the loan balance

                break;
            case 5:

                break;
            case 6:

            default :
                $response = $menu->confirmation_message;

                $notify = new NotifyController();
                //$notify->sendSms($user->phone_no,$response);
                //self::resetUser($user);
                self::sendResponse($response,2,$user);

                break;
        }

    }
    //continuation
    public function continueSingleProcess($user,$message,$menu){
        //validate input to be numeric

        self::storeUssdResponse($user,$message);
        $menuItem = ussd_menu_items::whereMenuIdAndStep($menu->id,$user->progress)->first();

        $message = str_replace(",","",$message);
       
         switch ($menu->id) {
             case 1:
                 //get the loan balance
                 self::validateRegistrationDetails($user,$message);
                 echo "Validate stuff";
                 exit;

                 break;
             case 5:

                 break;
             case 6:

             default :
                 $response = $menu->confirmation_message;

                 $notify = new NotifyController();
                 //$notify->sendSms($user->phone_no,$response);
                 //self::resetUser($user);
                 self::sendResponse($response,2,$user);

                 break;
         }

//        if((is_numeric(trim($message)))&&(1000<=$message)&&($message<=50000)){
// //            //save to the db
           //self::storeUssdResponse($user,$message);
           //check if we have another step
           $step = $user->progress + 1;
           $menuItem = ussd_menu_items::whereMenuIdAndStep($menu->id,$step)->first();
           if($menuItem){

               $user->menu_item_id = $menuItem->id;
               $user->menu_id = $menu->id;
               $user->progress = $step;
               $user->save();
               return $menuItem -> description;
           }else{
               $response = self::confirmBatch($user,$menu);
               return $response;

           }
//
//        }else{
//            if((trim($message) < 999) || (trim($message)>50000)){
//
//                $response = "Requested Loan amount must be from Ksh 1,000 to Ksh 50,000";
//
//            }else{
//                $response =  "Invalid Amount".PHP_EOL.$menuItem->description;
//            }
//
//        }

        return $response;
    }

    public function validateRegistrationDetails($user,$message){


        switch ($user->progress) {
            case 1:
                if(is_numeric($message) && strlen(trim($message)) == 5){
                    return TRUE;
                }else{
                    return FALSE;
                }
                break;
            case 2:
                $exploded_name = explode(" ",$message);
                if(count($exploded_name) > 1){
                    return TRUE;
                }else{
                    return FALSE;
                }
                break;
            case 3:
                if((strtolower($message) == 'a') || (strtolower($message) == 'b') || (strtolower($message) == 'c')|| (strtolower($message) == 'd')|| (strtolower($message) == 'e')|| (strtolower($message) == 'f')){
                    return TRUE;
                }else{
                    return FALSE;
                }
                break;
            case 4:
                return TRUE;
                break;
            case 5:
                return TRUE;
            break;

            default :
              return TRUE;
                break;
        }

        return $response;

    }

    //continue USSD Menu
    public function continueUssdMenu($user,$message,$menu){
        //verify response
        $menu_items = self::getMenuItems($user->menu_id);

        $i = 1;
        $choice = "";
        $next_menu_id = 0;
        foreach ($menu_items as $key => $value) {
            if(self::validationVariations(trim($message),$i,$value->description)){
                $choice = $value->id;
                $next_menu_id = $value->next_menu_id;

                break;
            }
            $i++;
        }
        if(empty($choice)){
            //get error, we could not understand your response
            $response = "We could not understand your response". PHP_EOL;


            $i = 1;
            $response = $menu->title.PHP_EOL;
            foreach ($menu_items as $key => $value) {
                $response = $response . $i . ": " . $value->description . PHP_EOL;
                $i++;
            }

            return $response;
            //save the response
        }else{
            //there is a selected choice
            $menu = ussd_menu::find($next_menu_id);
            //next menu switch
            $response = self::nextMenuSwitch($user,$menu);
            return $response;
        }

    }

    public function nextMenuSwitch($user,$menu){

//		print_r($menu);
//		exit;
        switch ($menu->type) {
            case 0:
                //authentication mini app

                break;
            case 1:
                //continue to another menu
                $menu_items = self::getMenuItems($menu->id);
                $i = 1;
                $response = $menu->title.PHP_EOL;
                foreach ($menu_items as $key => $value) {
                    $response = $response . $i . ": " . $value->description . PHP_EOL;
                    $i++;
                }

                $user->menu_id = $menu->id;
                $user->menu_item_id = 0;
                $user->progress= 0;
                //$user->save();
                //self::continueUssdMenu($user,$message,$menu);
                break;
            case 2:
                //start a process
//				print_r($menu);
//				exit;
                self::storeUssdResponse($user,$menu);

                $response = self::singleProcess($menu,$user,1);
                return $response;

                break;
            case 3:
                self::infoMiniApp($user,$menu);
                break;
            default :
                self::resetUser($user);
                $response = "An authentication error occurred";
                break;
        }

        return $response;

    }

    public function validationVariations($message, $option, $value)
    {
        if ((trim(strtolower($message)) == trim(strtolower($value))) || ($message == $option) || ($message == "." . $option) || ($message == $option . ".") || ($message == "," . $option) || ($message == $option . ",")) {
            return TRUE;
        } else {
            return FALSE;
        }

    }
    //store USSD response
    public function storeUssdResponse($user,$message){

        $data = ['user_id'=>$user->id,'menu_id'=>$user->menu_id,'menu_item_id'=>$user->menu_item_id,'response'=>$message];
        return ussd_response::create($data);


    }

    //single process

    public function singleProcess($menu, $user,$step) {
        

        $menuItem = ussd_menu_items::whereMenuIdAndStep($menu->id,$step)->first();

        if ($menuItem) {
            //update user data and next request and send back
            $user->menu_item_id = $menuItem->id;
            $user->menu_id = $menu->id;
            $user->progress = $step;
            $user->session = 1;
            $user->save();
            return $menuItem -> description;

        }

    }
    public function sendResponse($response,$type=1,$user=null)
    {
        if ($type == 1) {
            $output = "CON ";


        } elseif($type == 2) {
            $output = "CON ";
            $response = $response.PHP_EOL."1. Back to main menu".PHP_EOL."2. Log out";
            $user->session = 4;
            $user->progress = 0;
            $user->save();
        }else{
            $output = "END ";
        }

        $output .= $response;
        header('Content-type: text/plain');
        echo $output;
        exit;
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getMenuAndItems($user,$menu_id){
        //get main menu

        $user->menu_id = $menu_id;
        $user->session = 1;
        $user->progress = 1;
        $user->save();
        //get home menu
        $menu =  ussd_menu::find($menu_id);

        $menu_items = self::getMenuItems($menu_id);


        $i = 1;
        $response = $menu->title.PHP_EOL;
        foreach ($menu_items as $key => $value) {
            $response = $response . $i . ": " . $value->description . PHP_EOL;
            $i++;
        }
        return $response;
    }

    //Menu Items Function
    public static function getMenuItems($menu_id)
    {
        $menu_items = ussd_menu_items::whereMenuId($menu_id)->get();
        return $menu_items;
    }
    public function resetUser($user)
    {
        $user->session = 0;
        $user->progress = 0;
        $user->menu_id = 0;
        $user->difficulty_level = 0;
        $user->confirm_from = 0;
        $user->menu_item_id = 0;

        return $user->save();

    }

    public function user_is_starting($text)
    {
        if (strlen($text) > 0) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

        public  function confirmUssdProcess($user,$message){


        $menu = ussd_menu::find($user->menu_id);
        if (self::validationVariations($message, 1, "yes")) {

            //if confirmed


            // self::postConfirmation($user,$menu);
            // self::resetUser($user);
            $response = $menu->confirmation_message;
            // $notify = new NotifyController();
            // $notify->sendSms($user->phone_no,$response);
            self::sendResponse($response,2);

        }else{
            //not confirmed

            $response = "We could not understand your response";
            $output = self::confirmBatch($user,$menu);

            $response = $response.PHP_EOL.$output;
            return $response;
            //request to confirm again

        }


    }

    
    public function confirmBatch($user,$menu){
        //confirm this stuff
//      print_r($user);
//      exit;
        $menu_items = self::getMenuItems($user->menu_id);
//      print_r($menu_items);
//      exit;
        $confirmation = "Confirm: " . $menu -> title;
    
        foreach ($menu_items as $key => $value) {

            $response = ussd_response::whereUserIdAndMenuIdAndMenuItemId($user->id, $user->menu_id,$value->id)->orderBy('id', 'DESC')->first();
        

            $confirmation = $confirmation . PHP_EOL . $value->confirmation_phrase . ": " . $response->response;
        }
        $response = $confirmation . PHP_EOL . "1. Yes" . PHP_EOL . "2. No";
        $user->session = 2;
        $user->confirm_from = $user->menu_id;
        $user->save();
        return $response;
    }

}
