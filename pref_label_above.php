<?php
//////////////////////////////////////////////////////////
// 
// prev_label_above.php 
// - set all users preferences
//   field_name_placement to field_on_top
// 
//////////////////////////////////////////////////////////
$base_url = "https://localhost/sugarent1000/rest/v10";
$username = "admin";  // user who is admin
$password = "*****";  // password
$platform = "mobile"; // not base to prevent kicking out of the desktop app
//////////////////////////////////////////////////////////

ini_set('max_execution_time', 0);
$script_start = time();
$time_start = time();					 

//////////////////////////////////////////////////////////
// Login - POST /oauth2/token
//////////////////////////////////////////////////////////
$login_url = $base_url . "/oauth2/token";
$logout_url = $base_url . "/oauth2/logout";										   

$oauth2_token_arguments = array(
    "grant_type" => "password",
    "client_id" => "sugar",
    "client_secret" => "",
    "username" => $username,
    "password" => $password,
    "platform" => $platform
);

//////////////////////////////////////////////////////////
//FOR ALL users
//////////////////////////////////////////////////////////

$offset = 0;

while ($offset >=0) {
	
	//////////////////////////////////////////////////////////
	// Login
	// To be sure there is no side effect, renew login after 20 changes
	//////////////////////////////////////////////////////////
	$oauth2_token_response = call($login_url, '', 'POST', $oauth2_token_arguments);
	$DEBUG .= "## TOKEN RESPONSE: ".print_r($oauth2_token_response,true)." ##</hr>\n";
	echo $DEBUG; $DEBUG="";

	if ($oauth2_token_response->access_token == "") die("No Login");

	// POINT OF NO RETUN
	die(); // die to prevent unwanted execution

	//////////////////////////////////////////////////////////
	// READ users
	//////////////////////////////////////////////////////////
	$url = $base_url . '/Users';
	$users_arguments = array(
		"filter" => array(
			array(
				"deleted" => 0					   
			)
		),
		"max_num" => 20,
		"offset" => $offset,
		"fields" => "id,user_name,status,UserType,is_group,is_admin,license_type",
	);
	$DEBUG .= "## SEARCH USERS: ".print_r($users_arguments,true)." ##</br>\n";
	
	$users_response = call($url, $oauth2_token_response->access_token, 'GET', $users_arguments);
	$DEBUG .= "## SEARCH RESULT: ".print_r($users_response,true)." ##</br>\n";
	
	if (count($users_response->records) > 0) {
		
		$offset = $users_response->next_offset;
		
		foreach($users_response->records as $idc => $usr) {
					
			//////////////////////////////////////////////////////////
			// SUDO for single user
			//////////////////////////////////////////////////////////
			$usr_name = str_replace(" ","%20",$usr->user_name);
			$url = $base_url . '/oauth2/sudo/'.$usr_name;
			$sudo_arguments = array(
				"platform" => $platform
			);
			$DEBUG .= "## SUDO USER: ".$url." ##</br>\n";
			$sudo_response = call($url, $oauth2_token_response->access_token, 'POST', $sudo_arguments);
			$DEBUG .= "## SUDO RESULT: ".print_r($sudo_response,true)." ##</br>\n";
	
			$usr_token = $sudo_response->access_token;
			
			//////////////////////////////////////////////////////////
			// ME PREFERENCE for this single user
			//////////////////////////////////////////////////////////
			$url = $base_url . '/me/preference/field_name_placement';
			$me_arguments = array(
				"value" => "field_on_top"
			);
			$DEBUG .= "## ME call: ".$url." ##</br>\n";
			$me_response = call($url, $usr_token, 'PUT', $me_arguments);
			$DEBUG .= "## ME RESULT: ".print_r($me_response,true)." ##</br>\n";				
	
		} 
	} else {
		$DEBUG .= "## ERROR: ".count($users_response->records)." ##</br>\n";
		$offset = -2;
	}
		
	//////////////////////////////////////////////////////////
	// logout to clean up
	//////////////////////////////////////////////////////////
	call($logout_url, '', 'POST', $oauth2_token_arguments);

	echo $DEBUG; $DEBUG="";

}

$script_runtime = time()-$script_start;
$DEBUG .= "TIME needed: ".$script_runtime."<br>\n";
echo $DEBUG; $DEBUG="";

////////////////////////////////////////////////////////////////////
// END OF MAIN
////////////////////////////////////////////////////////////////////


/**
 * Generic function to make cURL request.
 * @param $url - The URL route to use.
 * @param string $oauthtoken - The oauth token.
 * @param string $type - GET, POST, PUT, DELETE. Defaults to GET.
 * @param array $arguments - Endpoint arguments.
 * @param array $encodeData - Whether or not to JSON encode the data.
 * @param array $returnHeaders - Whether or not to return the headers.
 * @param array $filenHeader - Whether or not to upload a file
 * @return mixed
 */
function call(
    $url,
    $oauthtoken='',
    $type='GET',
    $arguments=array(),
    $encodeData=true,
    $returnHeaders=false,
	$fileHeader=false
)
{
    $type = strtoupper($type);

    if ($type == 'GET')
    {
        $url .= "?" . http_build_query($arguments);
    }

    $curl_request = curl_init($url);

    if ($type == 'POST')
    {
        curl_setopt($curl_request, CURLOPT_POST, 1);
    }
    elseif ($type == 'PUT')
    {
        curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, "PUT");
    }
    elseif ($type == 'DELETE')
    {
        curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, "DELETE");
    }

    curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($curl_request, CURLOPT_HEADER, $returnHeaders);
    curl_setopt($curl_request, CURLOPT_SSL_VERIFYHOST, 0);  // wichtig
    curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);  // wichtig
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);

    if (!empty($oauthtoken)) 
    {
		if ($fileHeader) {
			curl_setopt($curl_request, CURLOPT_HTTPHEADER, array(
				"oauth-token: {$oauthtoken}"));
		} else {
            curl_setopt($curl_request, CURLOPT_HTTPHEADER, array(
				"oauth-token: {$oauthtoken}",
				"Content-Type: application/json"));
		}		
    }
    else
    {
        curl_setopt($curl_request, CURLOPT_HTTPHEADER, array(
			"Content-Type: application/json"));
    }

    if (!empty($arguments) && $type !== 'GET')
    {
        if ($encodeData)
        {
            //encode the arguments as JSON
            $arguments = json_encode($arguments);
        }
        curl_setopt($curl_request, CURLOPT_POSTFIELDS, $arguments);
    }

    $result = curl_exec($curl_request);
	
    if ($returnHeaders)
    {
        //set headers from response
        list($headers, $content) = explode("\r\n\r\n", $result ,2);
        foreach (explode("\r\n",$headers) as $header)
        {
            header($header);
        }

        //return the nonheader data
        return trim($content);
    }

    curl_close($curl_request);

    //decode the response from JSON
    $response = json_decode($result);

    return $response;
}
?>