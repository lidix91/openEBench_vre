<?php
// HTTP post
function npost($data,$url,$headers=array(),$auth_basic=array()){

  $c = curl_init();
  curl_setopt($c, CURLOPT_URL, $url);
  curl_setopt($c, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
  curl_setopt($c, CURLOPT_POST, 1);
  #curl_setopt($c, CURLOPT_TIMEOUT, 7);
  curl_setopt($c, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($c, CURLOPT_POSTFIELDS, $data);
      curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
      if (count($headers))
          curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
      if ($auth_basic['user'] && $auth_basic['pass'])
          curl_setopt($c, CURLOPT_USERPWD, $auth_basic['user'].":".$auth_basic['pass']);
          
  $r = curl_exec ($c);
      $info = curl_getinfo($c);

  if ($r === false){
    $errno = curl_errno($c);
    $msg = curl_strerror($errno);
          $err = "POST call failed. Curl says: [$errno] $msg";
      $_SESSION['errorData']['Error'][]=$err;	
    return array(0,$info);
  }
  curl_close($c);

  return array($r,$info);
}

// HTTP get
function nget($url,$headers=array(),$auth_basic=array()){

$c = curl_init();
      curl_setopt($c, CURLOPT_URL, $url);
      curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
if (isset($_SERVER['HTTP_USER_AGENT'])){
  curl_setopt($c, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
}
if (count($headers)){
  curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
}
if (isset($auth_basic['user']) && isset($auth_basic['pass'])){
  curl_setopt($c, CURLOPT_USERPWD, $auth_basic['user'].":".$auth_basic['pass']);
}
$r = curl_exec ($c);
$info = curl_getinfo($c);

if ($r === false){
  $errno = curl_errno($c);
  $msg = curl_strerror($errno);
    $err = "GET call failed. Curl says: [$errno] $msg";
     $_SESSION['errorData']['Error'][]=$err;	
    return array(0,$info);
}
curl_close($c);

return array($r,$info);
}


/**
 * Gets all communitites with their info or an specific community filtered or not
 * @param $community_id, the id of the community to find
 * @param $filter_field, the attribute of the given community
 * @return community/ies (json format). If an error ocurs it return false.
 */
function getCommunities($community_id = null, $filter_field = null ){

  if ($community_id == null) {
      $url = $GLOBALS['OEB_scirestapi']."/Community";
  } else {
    if ($filter_field == null) {
      $url = $GLOBALS['OEB_scirestapi']."/Community/".$community_id;
    } else {
      $url = $GLOBALS['OEB_scirestapi']."/Community/".$community_id."/".$filter_field;
    }
  }

  $headers= array('Accept: aplication/json');

  $r = nget($url, $headers);
  if ($r[1]['http_code'] != 200){
    $_SESSION['errorData']['Warning'][]="Error getting datasets. Http code= ".$status;
    return false;
  } else {
    return json_decode($r[0], true);
  }
}


/**
 * Gets all datasets with their info or an specific dataset filtered or not
 * @param $dataset_id, the id of the dataset to find
 * @param $filter_field, the attribute of the given dataset
 * @return dataset/s (json format). If an error ocurs it return false.
 */
//var_dump(getDatasets("OEBD0010000003"));
function getDatasets($dataset_id = null, $filter_field = null ){
  //$GLOBALS['OEB_scirestapi'] = 'https://openebench.bsc.es/api/scientific/access';
  if ($dataset_id == null) {
    $url = $GLOBALS['OEB_scirestapi']."/Dataset";

  } else {
    if ($filter_field == null) {
      $url = $GLOBALS['OEB_scirestapi']."/Dataset/".$dataset_id;
    } else {
      $url = $GLOBALS['OEB_scirestapi']."/Dataset/".$dataset_id."/".$filter_field;
    } 
  }

  $headers= array('Accept: aplication/json');

  $r = nget($url, $headers);
  if ($r[1]['http_code'] != 200){
    $_SESSION['errorData']['Warning'][]="Error getting datasets. Http code= ".$status;
    return false;
  } else {
    return json_decode($r[0], true);
  }
}
/** 
 * Gets all challenges with their info or an specific challenge filtered or not
 * @param $challenge_id, the id of the challenge to find
 * @param $filter_field, the attribute of the given challenge
 * @return challenge/s (json format). If an error ocurs it return false.
 */
//var_dump(getChallenges("OEBX0010000001", "benchmarking_event_id"));
function getChallenges ($challenge_id = null, $filter_field = null ){
  if ($challenge_id == null) {
    $url = $GLOBALS['OEB_scirestapi']."/Challenge";

  } else {
    if ($filter_field == null) {
      $url = $GLOBALS['OEB_scirestapi']."/Challenge/".$challenge_id;
    } else {
      $url = $GLOBALS['OEB_scirestapi']."/Challenge/".$challenge_id."/".$filter_field;
    } 
  }
  $headers= array('Accept: aplication/json');

  $r = nget($url, $headers);
  if ($r[1]['http_code'] != 200){
    $_SESSION['errorData']['Warning'][]="Error getting challenges. Http code= ".$status;
    return false;
  } else {
    return json_decode($r[0], true);
  }

}
/** 
 * Gets all benchmarking events with their info or an specific benchmark filtered or not
 * @param $benchmarkingEvent_id, the id of the benchmark to find
 * @param $filter_field, the attribute of the given challenge
 * @return benchmark/s (json format). If an error ocurs it return false.
 */
//var_dump(getBenchmarkingEvents("OEBE0010000000", "community_id"));
function getBenchmarkingEvents ($benchmarkingEvent_id = null, $filter_field = null ){

  if ($benchmarkingEvent_id == null) {
    $url = $GLOBALS['OEB_scirestapi']."/BenchmarkingEvent";

  } else {
    if ($filter_field == null) {
      $url = $GLOBALS['OEB_scirestapi']."/BenchmarkingEvent/".$benchmarkingEvent_id;
    } else {
      $url = $GLOBALS['OEB_scirestapi']."/BenchmarkingEvent/".$benchmarkingEvent_id."/".$filter_field;
    } 
  }
  $headers= array('Accept: aplication/json');

  $r = nget($url, $headers);
  if ($r[1]['http_code'] != 200){
    $_SESSION['errorData']['Warning'][]="Error getting benchmarkings events. Http code= ".$status;
    return false;
  } else {
    return json_decode($r[0], true);
  }

}

/**
 * Get community id
 * @param challenge_id
 * @return the community id from the given challenge or false if an error occur.
 */
//var_dump(getCommunityFromChallenge("OEBX0010000001"));
function getCommunityFromChallenge($challenge_id){

  //1. Get benchmarking event id from challenge collection
  $be = getChallenges($challenge_id, "benchmarking_event_id");

  //2. Get community id from the benchmarking event id
  return getBenchmarkingEvents($be,"community_id");
}

/**
 * Get the communities the user have permisions to submit files
 * @param roles array of user roles
 * @return array of communitites id's
 */

/**
 * Get the communities the user have permisions to submit files
 * @param roles array of user roles
 * @return array of communitites id's
 */
function getCommunitiesFromRoles (array $roles) {
	$communitites_ids = array();
  
	foreach ($roles as $elem) {
	  $r = explode(":", $elem);
	  if($r[0] == "owner") {
		array_push($communitites_ids, $r[1] );
	  }else {
		if($r[0] == "manager" || $r[0] == "contributor") {
		  array_push($communitites_ids, getCommunityFromChallenge($r[1]) );
		}
	  }
  
	}
	return $communitites_ids;
  
}

/**
 * Gets the challenges list given a community id
 * @param community id to search
 * @return array of challege/s obj. If an error ocurs it return false.
 */
//var_dump(getChallengesFromACommunity("OEBC002"));
function getChallengesFromACommunity ($community_id) {
  //1. Get benchmarking event collection
  $response = getBenchmarkingEvents();
  $benchmarkId = array();
  foreach ($response as $e) {
    if ($e["community_id"] == $community_id){
      array_push($benchmarkId, $e['_id']);
    }
  }

  //2. Get challenge collection
  $r = getChallenges();
  $challengeList = array();
  foreach ($r as $c) {
    for ($i=0; $i < count($benchmarkId) ; $i++) { 
      if ($c["benchmarking_event_id"] == $benchmarkId[$i]){
        array_push($challengeList, $c);
      }
    }
  }
  return json_encode($challengeList);

}


/**
 * Gets the email of the contacts (NEED authentification!!!)
 * @param array of contacts ids
 * @return associative array of each contacts id and their emails. If an error ocurs it return false.
 */
//var_dump(getContactEmail(array("Meritxell.Ferret")));
function getContactEmail ($contacts_ids) {
  //get credentials
  $confFile = $GLOBALS['OEBapi_credentials'];

  // fetch nextcloud API credentials
  $credentials = array();
  if (($F = fopen($confFile, "r")) !== FALSE) {
      while (($d = fgetcsv($F, 1000, ";")) !== FALSE) {
          foreach ($d as $a){
              //$r = explode(":",$a);
              $r = preg_replace('/^.:/', "", $a);
              if (isset($r)){array_push($credentials,$r);}
          }
      }
      fclose($F);
  }
  $username = $credentials[0];
  $password = $credentials[1];

  $auth_basic["user"] = $username;
  $auth_basic["pass"] = $password;
  $headers= array('Accept: aplication/json');

  $contacts_emails = array();

  foreach ($contacts_ids as $value) {
    $url = $GLOBALS['OEB_scirestapi']."/Contact/".$value.'/email';

    $r = nget($url, $headers, $auth_basic);

    if ($r[1]['http_code'] != 200){
      $_SESSION['errorData']['Warning'][]="Error getting contacts emails. Http code= ".$status;
      $contacts_emails[$value] = 0;
    } else {
      $contacts_emails[$value] = json_decode($r[0], true);
    }
  }
  return $contacts_emails;
   

}



/**
 * Gets all contacts id's given a community
 * @param community to search
 * @return array with contacts ids
 */
//var_dump(getAllContactsOfCommunity("OEBC002"));
function getAllContactsOfCommunity ($community_id){

  $curl = curl_init();
  $data_query =
    '{"query":" 
        query getContacts($community_id: String!){
          getContacts(contactFilters: {community_id: $community_id}) {
            _id    
          } 
        }",
        "variables":{"community_id": "'.$community_id.'"}}';

  curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://dev-openebench.bsc.es/sciapi/graphql/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS =>$data_query,
    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json'
    ),
  ));
  

  $response = curl_exec($curl);
  $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

  if ($status!= 200) {
    $_SESSION['errorData']['Warning'][]="Error getting contacts. Http code= ".$status;
    return false;
  } else {
     $items = json_decode($response)->data->getContacts;
    return json_encode($items);
     
  }

  curl_close($curl);


}


/**
 * Gets participant tools ids given a community_id
 * @return json with tools ids and names
 */
//var_dump(getTools());
function getTools () {
 
    $curl = curl_init();
    $data_query = 
      '{"query":"{ 
        getTools {
          _id
          name
        }
      }"}';
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://dev-openebench.bsc.es/sciapi/graphql/',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS =>$data_query,
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
      ),
    ));
    
  
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  
    if ($status!= 200) {
      $_SESSION['errorData']['Warning'][]="Error getting contacts. Http code= ".$status;
      return false;
    } else {
      $items = json_decode($response)->data->getTools;
      return json_encode($items);
       
    }
  
    curl_close($curl);
  
    
}
