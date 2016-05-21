<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class UssdController extends Controller
{


public function index()
{


    $input = self::getInput();
    $text = $input['text'];

//------------Get User Entry------------

    $entry = explode('*', $text);

//------------Get Level-----------------

    $level = count($entry);

//--------------------------------------

    if ($text == "") {
        echo "CON SBC '06 CLASS" . PHP_EOL . " 1: Register" . PHP_EOL . " 2: Share";
    } else {

        switch ($level) {
            case 1:
                if ($entry[0] == 1) {
                    echo "END To be continued...";
                } else if ($entry[0] == 2) {
                    echo "CON Andika number ya raia...";
                }
                break;

            case 2:
                if ($entry[0] == 2) {
                    $number = $entry[1];
                    sendSMS($number);
                }
                break;

            default:
                echo "Hatujafika hapo" . PHP_EOL . "powered by Macharia and Le-yo";
                break;
        }
    }
}
public function getInput()
{
    $input=array();
    $input['text']=$_REQUEST['text'];
    $input['phoneNumber']=$_REQUEST['phoneNumber'];
    $input['serviceCode']=$_REQUEST['serviceCode'];
    $input['sessionId']=$_REQUEST['sessionId'];
    return $input;
}

public function sendSMS($number)
{
    //Include Africa'sTalking SMS' sending class
    echo "CON ".$number;
}


}
