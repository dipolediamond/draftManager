<?php
require_once ("classes/class.pmFunctions.php");
$pmServer = getServerURL();

/*Function to call a ProcessMaker REST endpoint and return the HTTP status code and
response if any.
Parameters:
$method:      HTTP method: "GET", "POST", "PUT" or "DELETE"
$endpoint:    The PM endpoint, not including the server's address and port number.
Ex: "/api/1.0/workflow/cases"
$aVars:       Optional. Associative array containing the variables to use in the request
if "POST" or "PUT" method.
$accessToken: Optional. The access token, which comes from oauth2/token. If not defined
then uses the access token in $_COOKIE['access_token']
Return Value:
object {
response: Response from REST endpoint, decoded with json_decode().
status:   HTTP status code: 200 (OK), 201 (Created), 400 (Bad Request), 404 (Not found), etc.
}                                                                                              */
function pmRestRequest($method, $endpoint, $aVars = null, $accessToken = null) {
    global $pmServer;
    
    if (empty($accessToken))
        $accessToken = getAccessToken();
    
    if (empty($accessToken)) { //if the access token has expired
        //To check if the PM login session has expired: !isset($_COOKIE['PHPSESSID'])
        G::header("Location: login?u=sysworkflow/en/neoclassic/draftManager/main"); //change to match your login method
        die();
    }
    
    //add beginning / to endpoint if it doesn't exist:
    if (!empty($endpoint) and $endpoint[0] != "/")
        $endpoint = "/" . $endpoint;
    
    $ch = curl_init($pmServer . $endpoint);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $accessToken));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $method = strtoupper($method);
    
    switch ($method) {
        case "GET":
            break;
        case "DELETE":
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            break;
        case "PUT":
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            case "POST":
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aVars));
                break;
            default:
                throw new Exception("Error: Invalid HTTP method '$method' $endpoint");
                return null;
        }
        
        $oRet = new StdClass;
        $oRet->response = json_decode(curl_exec($ch));
        $oRet->status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($oRet->status == 401) { //if session has expired or bad login:
            G::header("Location: login?u=sysworkflow/en/neoclassic/draftManager/main"); //change to match your login method
            die();
        }
        elseif ($oRet->status != 200 and $oRet->status != 201) { //if error
            if ($oRet->response and isset($oRet->response->error)) {
                //print "Error in $pmServer:\nCode: {$oRet->response->error->code}\n" .
                //"Message: {$oRet->response->error->message}\n";
            }
            else {
                //print "Error: HTTP status code: $oRet->status\n";
            }
        }
        
        return $oRet;
    }
    
    function getServerURL() {
        $myUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] &&
        !in_array(strtolower($_SERVER['HTTPS']),array('off','no'))) ? 'https' : 'http';
        // Get domain portion
        $myUrl .= '://'.$_SERVER['HTTP_HOST'];
        return $myUrl;
    }
    
    function getAccessToken() {
        //if no active login session, then redirect to the login screen:
        if (!isset($_SESSION['USER_LOGGED'])) {
            //set to the page of the plugin, where it will redirect after logging in:
            G::header("Location: login?u=sysworkflow/en/neoclassic/draftManager/main");
            die();
        }
        $userId = $_SESSION['USER_LOGGED'];
        $query = "SELECT ACCESS_TOKEN FROM OAUTH_ACCESS_TOKENS WHERE USER_ID='$userId' ORDER BY EXPIRES DESC";
        $result = executeQuery($query);
        $accessToken = $result[1]['ACCESS_TOKEN'];
        $_SESSION['DM_ACCESS_TOKEN'] = $accessToken;
        return $accessToken;
    }
    
    
    function getDraftCases($limit, $start, $process) {
        $endpoint = "/api/1.0/workflow/cases/advanced-search/paged?app_status=DRAFT&start=" . $start . "&limit=" . $limit;
        $endpoint .= isset($process) ? "&pro_uid=" . $process : "";
        
        $oRet = pmRestRequest('GET', $endpoint);
        if ($oRet->status == 200) {
            return $oRet->response ;
        }
    }
    
    function getProcesses() {
        
        $endpoint = "/api/1.0/workflow/project";
        
        $oRet = pmRestRequest('GET', $endpoint);
        if ($oRet->status == 200) {
            return $oRet->response;
        }
    }
    
    function cancelCases($cases) {
        $result = array();
        foreach ($cases as $case){
            $app_uid = $case["app_uid"];
            $endpoint = "/api/1.0/workflow/cases/". $app_uid . "/cancel";
            
            $oRet = pmRestRequest('PUT', $endpoint, array() );
            if ($oRet->status == 200) {
                array_push($result, array("app_number" => $case["app_number"], "cancel_result" => "Successfully cancelled"));
            }
            else {
                if ($oRet->response and isset($oRet->response->error)) {
                    array_push($result, array("app_number" => $case["app_number"],
                    "cancel_result" =>$oRet->response->error->message));
                }
            }
        }
        return $result;
        
    }
    
    try {
        $option = $_POST["option"];
        
        switch ($option) {
            case "LST":
                $pageSize = $_POST["pageSize"];
                $process = $_POST["process"];
                
                $limit = isset($_POST["limit"])? $_POST["limit"] : $pageSize;
                $start = isset($_POST["start"])? $_POST["start"] : 0;
                
                //list($userNum, $user) = getCases($limit, $start);
                echo G::json_encode(getDraftCases($limit, $start, $process));
                //echo "{success: " . true . ", resultTotal: " . count($user) . ", resultRoot: " . G::json_encode($user) . "}";
                //echo G::json_encode(array("success" => true, "resultTotal" => $userNum, "resultRoot" => $user));
                break;
            case "PRO" :
                echo G::json_encode(array("data" => getProcesses()));
                //echo G::json_encode(array("success" => true));
                break;
            case "CNL" :
                $cases = json_decode($_POST["cases"], true);
                echo G::json_encode(array("data" => cancelCases($cases)));
            }
        } catch (Exception $e) {
            echo null;
        }
        ?>