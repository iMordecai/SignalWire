<?php

// CAPTCHA on SignalWire

/////////////////////////////////////////////////////

// SETTINGS
$whitelist = ['12125551212','17185551212']; // array of whitelisted phone numbers (in this format 12125551212 for US numbers)
$dial      = '212-555-1212'; // where to be forwarded

// gather the stuff from the url 
$url      = pathinfo(__FILE__, PATHINFO_BASENAME);           // the name of this page
$callerID = preg_replace('/\D/', '', $_REQUEST['From']);     // the "from" number, remove anything that's not a digit
$expect   = $_REQUEST['expect'] ?? NULL;;                    // the expected number (in 2nd stage), NULL as default
$digits   = $_REQUEST['Digits'];                             // the digits that were typed (WITHOUT the # sign)
$stage    = $_REQUEST['stage'] ?? 'start';                   // which stage are we at (set to 'start' as default)

// if the caller is whitelisted, forward the call
if (in_array($callerID, $whitelist)) { echo forwardCall($dial); }

// which stage are we in
if ($stage == 'start') {
        // present the challange
        //$challange = challangeRandom();
        //$challange = challangePlus();
        $challange = challangeWhichIsMore();
        echo <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <Response>
            <Gather action="$url?stage=challanged&amp;expect={$challange[0]}" actionOnEmptyResult="true" method="GET" timeout="60">
                {$challange[1]}
            </Gather>
        </Response>
        XML;
} elseif ($stage == 'challanged') {
    if ($digits == $expect) {
        // challange was success, forward the call
        echo forwardCall($dial);      
    }
    else {
        // challange was wrong, disconnect
        echo "<?xml version='1.0' encoding='UTF-8'?><Response><Say>Sorry, try another time</Say></Response>";      
    }
}

///////////////////////////////////////////////////////////////
// function to return a challange and an expected result
function challangeRandom() {
    // random number between 10 and 99
    $random = rand(10,99);
    $say    = "<Say voice='gcloud.en-GB-Standard-F' loop='2'>Please type the following number, $random, then press pound.</Say>";
    return [$random, $say];
}

// another function to return a challange and an expected result
function challangePlus() {
    // random number between 8 and 94
    $random1 = rand(8,94);
    $random2 = rand(2,5);
    $say    = "<Say voice='gcloud.en-GB-Standard-F' loop='2'>Please type the total of <prosody rate='75%'>$random1 plus $random2</prosody>, then press pound.</Say>";
    return [$random1+$random2, $say];
}

// another function to return a challange and an expected result
function challangeWhichIsMore() {
    // random numbers between 10 and 99
    $random1 = rand(10,99);
    $random2 = rand(10,99);
    while ($random1==$random2) { // ensure that the numbers are not the same
        $random2 = rand(10,99);
    }
    $say    = "<Say voice='gcloud.en-AU-Standard-D' loop='2'>Which number is more. <break strength='strong' /> <prosody rate='75%'>$random1, or $random2</prosody>. <break strength='strong' /> Please type the answer then press pound.<break strength='x-strong' /></Say>";
    return [max($random1, $random2), $say];
}

// helper function to forward call
function forwardCall($num) {
    return "<?xml version='1.0' encoding='UTF-8'?><Response><Dial>$num</Dial></Response>";      
}