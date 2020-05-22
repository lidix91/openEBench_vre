<?php

//Get all the workflows to show into the datatable -> depending on the user show ones or others.
function getProcesses() {
	//initiallize variables
	$process_json="{}";
	$processes = array();
	$users = array();

	//user logged
	$userId = $_SESSION["User"]["id"];

	//type of user
	$typeUser = $GLOBALS['usersCol']->find(array("id"=>$userId), array("Type"=>1));

	foreach($typeUser as $user) {
		array_push($users, $user);
	}

	foreach($users as $user) {
		//if the user is the administrator
		if($user["Type"] == 0) {
			$allProcesses = $GLOBALS['processCol']->find();
			//if the user is not the administrator (=community manager)
		} elseif($user["Type"] == 1) {
			//the workflows has to be registered to see 
			$allProcesses = $GLOBALS['processCol']->find(array('$or' => array(array("data.owner" => $userId), array("request_status" => "registered", "publication_status" => 1))));
		}
	}

	//add query to an array
	foreach($allProcesses as $process) {
		array_push($processes, $process);
	}

	//convert array into json 
	$process_json = json_encode($processes, JSON_PRETTY_PRINT);

	return $process_json;
}

//status = 0; private
//status = 1; public
//status = 2; coming soon
//for update the publication_status of workflows
function updateStatusProcess($processId, $statusId) {
	//jsonResponse class (errors or successfully)
	$response_json = new JsonResponse();
	$users = array();
	$processes = array();

	//variables
	$userId = $_SESSION["User"]["id"];
	$typeUser = $GLOBALS['usersCol']->find(array("id"=>$userId), array("Type"=>1));

	foreach($typeUser as $user) {
		array_push($users, $user);
	}

	//collection processes
	$processCol = $GLOBALS['processCol'];

	// check if user is authorized to update object
	$authorized = false;

	//check what type of user it is
	foreach($users as $user) {
		//if admin
		if($user["Type"] == 0) {
			$authorized = true;
		//if community manager
		} else if ($user["Type"] == 1) {
			$processesToolDev = $processCol->find(array("data.owner" => $userId, "_id" => $processId));

			foreach($processesToolDev as $process) {
				array_push($processes, $process);
			}
		
			if(empty($processes)) {
				$authorized = false;
			} else {
				$authorized = true;
			}
		} else {
			$authorized = false;
		}
	}

	// return error if unauthorized action
    if (!$authorized){
		// return error msg via ProcessResponse
		$response_json->setCode(401);
		$response_json->setMessage("Not authorized to update the status of the OEB-Process with Identifier='$processId'. Double check its ownership.");
		
		return $response_json->getResponse();
	}
	// update process status in Mongo
	try  {
		$processCol->update(['_id' => $processId], [ '$set' => [ 'publication_status' => 'NumberLong('+$statusId+')']]);
		$processFound = $processCol->find(array("publication_status"=>'NumberLong('+$statusId+')', "_id"=>$processId));

		if($processFound != "") {
			$response_json->setCode(200);
			$response_json->setMessage("OK");
		} else {
			$response_json->setCode(500);
			$response_json->setMessage("Cannot update data in Mongo. Mongo Error(".$e->getCode()."): ".$e->getMessage());
		}
		return $response_json->getResponse();
	} catch (MongoCursorException $e) {

		$response_json->setCode(500);
		$response_json->setMessage("Cannot update data in Mongo. Mongo Error(".$e->getCode()."): ".$e->getMessage());
		return $response_json->getResponse();
	}

	return $response_json->getResponse();
}

//function to obtain the plain list of the ontologies. An enum for the JSON Schema
function getListOntologyForForm($formOntology, $ancestors) {
	//variables
	$resource = "";
	$graph = "";
	$classArray = array();
	$subClassArray = array();
	$array_gen;
	$process_json="{}";
	$label;
	//ERR, LOG, SVG, pptx, IMG, 
	$firstERR = 0;
	$firstLOG = 0;
	$firstSVG = 0;
	$firstpptx = 0;
	$firstIMG = 0;
	$firstJSON = 0;

	if (isset($GLOBALS['oeb_dataModels'][$formOntology])) {
		//is only to validate that the ontology exists in DB. 
		$nameUrlOntology = $GLOBALS['oeb_dataModels'][$formOntology];
		//ontology-general
		//we use the link of .owl and not the pURLs because this function does not accepted it
		$graph = EasyRdf_Graph::newAndLoad("https://raw.githubusercontent.com/inab/OEB-ontologies/master/oebDatasets-complete.owl","rdfxml");
		
		$resource = $graph->resource($ancestors);
		
 		//get all the classes that are subclass of the uri 'https://w3id.org/oebDataFormats/FormatDatasets'
		$classes = $graph->resourcesMatching("rdfs:subClassOf",$resource);

		//get the first classes (without showing the childrens)
		foreach ($classes as $class) {
			//get the label of first classes (without showing the childrens)
            $label = $class->getLiteral('rdfs:label');
            $label = (string)$label; 

			//get the uri of classes that extends from the previous class find
			$resourceClassesInherited = $graph->resource($class);

			//get all the classes that are subclass of the uri found in the previous step (all the uris of classes that extends from the first classes found)
			$classesInherited = $graph->resourcesMatching("rdfs:subClassOf",$resourceClassesInherited);

			$URILabel = (string)$resourceClassesInherited->getUri();

			if (($label == "LOG" && $firstLOG == 0) || ($label == "IMG" && $firstIMG == 0) || ($label == "JSON" && $firstJSON == 0) || ($label != "LOG" && $label != "IMG" && $label != "JSON")) {
				if ($label == "LOG") {
					$firstLOG++;
					$ClassPair = array("label" => $label, "URI" => $URILabel);
				} elseif ($label == "IMG") {
					$firstIMG++;
					$ClassPair = array("label" => $label, "URI" => $URILabel);
				} elseif($label == "JSON") {
					$firstJSON++;
					$ClassPair = array("label" => $label, "URI" => $URILabel);		
				} elseif ($label != "LOG" && $label != "IMG" && $label != "JSON") {
					$ClassPair = array("label" => $label, "URI" => $URILabel);
				}
			}

			//if there are not any format inherited in the first classes do not do it
			$subClassArray = array();
			if ($classesInherited != null) {
				array_push($classArray, $ClassPair);
				//get the classes inherited (the childrens)
				foreach($classesInherited as $classInherited) {
					//get the label of the classes inherited (the childrens)
					$labelClassInherited = (string)$classInherited->getLiteral('rdfs:label');
					$URIClassInherited = (string)$classInherited->getUri();
					if (($labelClassInherited == "ERR" && $firstERR == 0) || ($labelClassInherited == "SVG" && $firstSVG == 0) || ($labelClassInherited == "pptx" && $firstpptx == 0) || ($labelClassInherited != "ERR" && $labelClassInherited != "SVG" && $labelClassInherited != "pptx" && $labelClassInherited != "LOG")){
						if ($labelClassInherited == "ERR") {
							$firstERR++;
							$subClassPair = array("label" => $labelClassInherited, "URI" => $URIClassInherited);
							array_push($classArray, $subClassPair);
						} if ($labelClassInherited == "SVG") {
							$firstSVG++;
							$subClassPair = array("label" => $labelClassInherited, "URI" => $URIClassInherited);
							array_push($classArray, $subClassPair);
						} if ($labelClassInherited == "pptx") {
							$firstpptx++;
							$subClassPair = array("label" => $labelClassInherited, "URI" => $URIClassInherited);
							array_push($classArray, $subClassPair);					
						} elseif ($labelClassInherited != "ERR" && $labelClassInherited != "SVG" && $labelClassInherited != "pptx") {
							$subClassPair = array("label" => $labelClassInherited, "URI" => $URIClassInherited);
							array_push($classArray, $subClassPair);		
						}
					}

				}
			} else {
				array_push($classArray, $ClassPair);
			}
		}
		//$array_gen = array("labels" => $classArray);
		$process_json = json_encode($classArray, JSON_PRETTY_PRINT);
		return $process_json;
	} else {
		return $process_json;
	}
}

//put the default values into the JSON Schema to inserted it in MongoDB
function getDefaultValues() {
	$process_json = "{}";

	//user logged
	$userId = $_SESSION["User"]["id"];

	$_schema = "https://openebench.bsc.es/vre/process-schema";
	
	$userInfo = getUser($userId);
	$userInfoJSON = json_decode($userInfo, true);

	$processWithVars = array("owner" => array("institution"=>$userInfoJSON[0]["Inst"], "author"=>$userInfoJSON[0]["Name"], "contact"=>$userInfoJSON[0]["_id"], "user"=>$userInfoJSON[0]["id"]), "_schema" => $_schema);
	
	$process_json = json_encode($processWithVars, JSON_PRETTY_PRINT);

	return $process_json;
}

//Get the actual user (the user logged in)
function getUser($id) {

	if ($id == "current") {
		//initiallize variables
		$process_json="{}";
		$users = array();

		//user logged
		$userId = $_SESSION["User"]["id"];

		//type of user
		$typeUser = $GLOBALS['usersCol']->find(array("id"=>$userId), array("Type"=>1));

		foreach($typeUser as $user) {
			array_push($users, $user);
		}

		$process_json = json_encode($users, JSON_PRETTY_PRINT);

		return $process_json;
	} else {
		//initiallize variables
		$process_json="{}";
		$users = array();

		//user logged
		$userId = $_SESSION["User"]["id"];

		//type of user
		$typeUser = $GLOBALS['usersCol']->find(array("id"=>$id), array("Inst"=>1, "Name"=>1, "Email"=>1, "id"=>1));

		foreach($typeUser as $user) {
			array_push($users, $user);
		}

		$process_json = json_encode($users, JSON_PRETTY_PRINT);

		return $process_json;
	}
}

//Get the workflow information from the form, validate all the data and inserted in MongoDB 
function setProcess($processStringForm) {
	$response_json= new JsonResponse();

	$response_json->setCode("405");
	$response_json->setMessage("ERROR");

	$processForm = json_decode($processStringForm, true);

	// store public dataset
	$file = $processForm["inputs_meta"]["public_ref_dir"]["value"];

	//check git repositories and parse the workflow file
	$gitURL_workflow = $processForm["nextflow_files"]["workflow_file"]["workflow_gitURL"];
	$gitTag_workflow = $processForm["nextflow_files"]["workflow_file"]["workflow_gitTag"];

	//get errors (or not) workflow git
	$resultWorkflow = _validationStep4($gitURL_workflow, $gitTag_workflow);
	
	//errors nextflow files
	if ($resultWorkflow != "OK"){
		$response_json->setCode(422);
		$response_json->setMessage($resultWorkflow);

		return $response_json->getResponse();
	} 

	//materialize public data
	$result = _getPublicData_fromBase64($file, $processForm);	

	//CHECK PUBLIC DATA
	if ($result[0] != "OK") {
		$response_json->setCode(422);
		$response_json->setMessage($result[0]);

		return $response_json->getResponse();
	} 

	//assign the path of the public_ref_dir
	$processForm["inputs_meta"]["public_ref_dir"]["value"] = $result[1];
	
	//user logged
	$userId = $_SESSION["User"]["id"];

	//MongoDB query
	$data = array();
	$data['_id'] = createLabel($GLOBALS['AppPrefix']."_process",'processCol');
	$data['data'] = $processForm;
	$data['request_status'] = "submitted";
	$data['request_date'] = date('l jS \of F Y h:i:s A');
	$data['publication_status'] = 3;
	
	try {
		//$GLOBALS['processCol']->insert($processJSONForm);
		$GLOBALS['processCol']->insert($data);

	} catch (Exception $e) {
		$response_json->setCode(501);
		$response_json->setMessage("Cannot update data in Mongo. Mongo Error(".$e->getCode()."): ".$e->getMessage());
	
		return $response_json->getResponse();
	}

	$response_json->setCode(200);
	$response_json->setMessage("OK");

	return $response_json->getResponse();
}

//validate the file TAR
function _getPublicData_fromBase64($file_base64, $processForm) {
	$MAX_SIZE = 500000;
	$response_json= new JsonResponse();
	 
	//create temporal file to check the file and if it is a tar or tar.gz
	$tempDir = $GLOBALS['dataDir'].$_SESSION['User']['id']."/".$_SESSION['User']['activeProject']."/".$GLOBALS['tmpUser_dir'];

	$tempFile = $tempDir . "public_dataset";

	
	//if it is not exist the folde tmp create it because file_put_contents only create the file if not exist
	if (!is_dir($tempDir)) {
		mkdir($tempDir);
	}
	
	//move the content to that file: public_dataset
	//return the size
	$r = file_put_contents($tempFile ,file_get_contents($file_base64));

	//check return something
	if (!$r){
		unlink($tempFile);
		return ["Public Reference Dataset -> TAR file. The file cannot be uploaded.", $processForm["inputs_meta"]["public_ref_dir"]["value"]];
	}

	//check size
	if ($r > $MAX_SIZE) {
		unlink($tempFile);
		return ["Public Reference Dataset -> TAR file. The file is too large.", $processForm["inputs_meta"]["public_ref_dir"]["value"]];
	}

	//check if it is a file
	if (!is_file($tempFile)){
		unlink($tempFile);
		return ["Public Reference Dataset -> TAR file. You do not have upload a file.", $processForm["inputs_meta"]["public_ref_dir"]["value"]];
	}

	// guess compression
	$file_info  = shell_exec("file $tempFile | cut -d: -f2");

	// uncompress data
	$uncompress_cmd="";
	if (preg_match('/gzip/i',$file_info) == 1 || preg_match('/x-tar/i',$file_info) == 1){
		$uncompress_cmd = "tar xzvf $tempFile -C $tempDir";
	//check if it is not a tar of a tar.gz

	} else {
		unlink($tempFile);
		return ["Public Reference Dataset -> TAR file. The file is not a TAR or a TAR.GZ.", $processForm["inputs_meta"]["public_ref_dir"]["value"]];
	}
		
	//execute the command line
	$r = shell_exec($uncompress_cmd); 

	//check the content of the tar or tar.gz. If it is empty do not return anything
	if (!$r){
		unlink($tempFile);
		return ["Public Reference Dataset -> TAR file. The TAR or TAR.GZ is empty.", $processForm["inputs_meta"]["public_ref_dir"]["value"]];
	}

	// create target dir = where the information is saved
	$target_folder = hash_file('sha256',$tempFile);
	$targetDir = $GLOBALS['pubDir'].$target_folder;
	mkdir($targetDir);

	$r = shell_exec("tar xzvf $tempFile -C $targetDir");

	if (!$r){
		unlink($tempFile);
		rmdir($targetDir);
		return ["Public Reference Dataset -> TAR file. The file cannot be uploaded.", $processForm["inputs_meta"]["public_ref_dir"]["value"]];
	}

	// clean temporary data
	unlink($tempFile);

	//return the target folder because is the folder name (without all the route)
	return ["OK", $target_folder];
}

//validate the Git URL 
function _validationStep4($gitURL, $gitTag) {
	$resultValidation = "";

	$tempDir = _cloneGit($gitURL, $gitTag);

	//validate the git url
	$gitValidation = _validateGit($tempDir);

	switch($gitValidation) {
		case 0: 
			$resultValidation = "Nextflow files -> Workflow file. The git URL cannot be uploaded.";
			break;
		case 1:
			$resultValidation = "Nextflow files -> Workflow file. The git link is empty.";
			break;
		case 2:
			$resultValidation = "Some error ocurred";
			break;
	}

	if ($gitValidation == 2) {
		//validate the content of the git (nextflow files)
		$nextflowFileValidation = _validateNextflowFiles($tempDir);
		
		switch($nextflowFileValidation) {
			case 0: 
				$resultValidation = "Nextflow files -> Workflow file. The 'main.nf' file is not found.";
				break;
			case 1: 
				$resultValidation = "Nextflow files -> Workflow file. The 'nextflow.config' file is not found.";
				break;
			case 2: 
				$resultValidation = "Nextflow files -> Workflow file. Missing parameters. Make sure that the following paratemers are set: input, public_ref_dir, participant_id, challenges_id and community_id.";
				break;
			case 3: 
				$resultValidation = "OK";
				break;
		}
	}
	
	//remove temperal directory
	$r = shell_exec("rm -rf $tempDir");
	
	return $resultValidation;	
}

//only clone the git url given
function _cloneGit($gitURL, $gitTag) {
	$response_json= new JsonResponse();

	//create temporal file to check the git url
	$tempDir = $GLOBALS['dataDir'].$_SESSION['User']['id']."/".$_SESSION['User']['activeProject']."/".$GLOBALS['tmpUser_dir']."gitDir/";

	//clone the git if exist (taking into account the tag)
	$cmnd = "git clone -b $gitTag $gitURL $tempDir";

	$r = shell_exec($cmnd);

	return $tempDir;
}

//valudate the git url
function _validateGit($tempDir) {
	//check if the git URL and Tag exist
	if (!is_dir($tempDir)) {
		return 0;
	}

	//check if the git link is empty
	$files = count(glob($tempDir . '*', GLOB_MARK));
	if ($files == 0) {
		return 1;
	}

	return 2;
}

//validate the nextflow files inside the git url
function _validateNextflowFiles($tempDir) {
	//get the git directory file paths (clone it in the tempDir)
	$files = glob($tempDir . '*', GLOB_MARK);

	$mainExist = 0;
	$nextflowExist = 0;

	foreach ($files as $file) {
		//check if exist the main.nf file
		if (strtoupper($file) == strtoupper($tempDir. "main.nf")) {
			$mainExist++;
		} 
		//check if exist the nextflow.config file
		if(strtoupper($file) == strtoupper($tempDir . "nextflow.config")) {
			$nextflowExist++;
		}
	}

	//check only there are a main.nf
	if($mainExist != 1) {
		return 0;	
	} 

	//check only there are a nextflow.config
	if($nextflowExist != 1) {
		return 1;	
	} 

	$input = false;
	$public_ref_dir = false;
	$participant_id = false;
	$challenges_ids = false;
	$community_id = false;

	//check that there are all the necessary params in the main.nf file
	foreach ($files as $file) {
		if (strtoupper($file) == strtoupper($tempDir . "main.nf")) {
			$fp = fopen($file, "r");
			while (!feof($fp)){
				$linea = fgets($fp);
				if (preg_match('/params.input/i',$linea) == 1) {
					$input = true;
				} else if (preg_match('/params.public_ref_dir/i',$linea) == 1) {
					$public_ref_dir = true;
				} elseif (preg_match('/params.participant_id/i',$linea) == 1) {
					$participant_id = true;
				} elseif(preg_match('/params.challenges_ids/i',$linea) == 1) {
					$challenges_ids = true;
				} elseif(preg_match('/params.community_id/i',$linea) == 1) {
					$community_id = true;
				}
			}
			fclose($fp);
		}
	}

	//if not are all the necessary params return an error
	if(!$input || !$public_ref_dir || !$participant_id || !$challenges_ids || !$community_id) {
		return 2;
	} 
	
	return 3;
}

//return a process from the id
function _getProcess($id) {
	//initiallize variables
	$process_json="{}";
	$processFinal = array();

	$theProcess = $GLOBALS['processCol']->find(array('_id' => $id));

	//add query to an array
	foreach($theProcess as $process) {
		array_push($processFinal, $process);
	}

	//convert array into json 
	$process_json = json_encode($processFinal, JSON_PRETTY_PRINT);

	//createTool($process_json);

	return $process_json;
}

//general function of create the VRE tool
function createTool_fromWFs($id) {

	$response_json = new JsonResponse();

	$errors = array();

	$process_json="{}";

	$process_data = _getProcess($id);

	$tool_data = _createToolSpecification_fromWF($process_data);

 	$errors = _validateToolSpefication($tool_data);



	if ($errors) { 	 

		$process_json = json_encode($errors, JSON_PRETTY_PRINT);

		$response_json->setCode(422);
		$response_json->setMessage($process_json);

		return $response_json->getResponse();
	} 

	//	_createTool_fromToolSpecification($tool_data);
	//	_insertToolMongo($tool_data);
	$registration = _register_workflow($id);

	if (!$registration) {
		$response_json->setCode(500);
		$response_json->setMessage("Cannot update data in Mongo. Mongo Error");
		return $response_json->getResponse();
	}

	
	
	$process_json = json_encode($tool_data, JSON_PRETTY_PRINT);
	
	$response_json->setCode(200);
	$response_json->setMessage("OK");

	return $response_json->getResponse();
}

//if the administrator click the reject button
function reject_workflow($id) {
	
	$processCol = $GLOBALS['processCol'];

	$response_json = new JsonResponse();

	try  {
		$processCol->update(['_id' => $id], [ '$set' => [ 'request_status' => 'rejected']]);
		$processFound = $processCol->find(array("request_status"=>'rejected', "_id"=>$id));

		if($processFound != "") {
			$response_json->setCode(200);
			$response_json->setMessage("OK");
		} else {
			$response_json->setCode(500);
			$response_json->setMessage("Cannot update data in Mongo. Mongo Error(".$e->getCode()."): ".$e->getMessage());
		}
		return $response_json->getResponse();
	} catch (MongoCursorException $e) {

		$response_json->setCode(500);
		$response_json->setMessage("Cannot update data in Mongo. Mongo Error(".$e->getCode()."): ".$e->getMessage());
		return $response_json->getResponse();
	}
	return $response_json->getResponse();
}

//if the administrator click the create tool vre button
function _register_workflow($id) {
	
	$processCol = $GLOBALS['processCol'];

	$response_json = new JsonResponse();

	try  {
		$processCol->update(['_id' => $id], [ '$set' => [ 'request_status' => 'registered']]);
		$processFound = $processCol->find(array("request_status"=>'registered', "_id"=>$id));

		if($processFound != "") {
			return true;
		} else {
			return false;
		}
	} catch (MongoCursorException $e) {
		return false;
	}
}

//when the administrator click the create tool vre button generates the json tool to insert it in MongoDB 
function _createToolSpecification_fromWF($process) {
	//what the function will return
	$stringTool = '{}';

	//the tool in format json
	$jsonTool = array();
	
	//descriptions of the challenges
	$descriptions = array();
	//names of the challenges
	$names = array();

	//
	$process_json = json_decode($process, true);
	$process_json = $process_json[0];

	//the ontology of data type and file type
	$fileOntology = getListOntologyForForm("https://w3id.org/oebDataFormats", "https://w3id.org/oebDataFormats/FormatDatasets");
	$dataOntology = getListOntologyForForm("https://w3id.org/oebDatasets", "https://w3id.org/oebDatasets/dataset");

	$ontology_file_type = json_decode($fileOntology, true);
	$ontology_data_type = json_decode($dataOntology, true);

	

	$jsonTool = array();

	$jsonTool["_id"] =  $process_json["_id"];
	$jsonTool["name"] =  $process_json["data"]["name"];
	$jsonTool["title"] =  $process_json["data"]["title"];
	$jsonTool["short_description"] = $process_json["data"]["description"];
	$jsonTool["long_description"] = $process_json["data"]["description_long"];
		$jsonTool["owner"]["institution"] = $process_json["data"]["owner"]["institution"];
		$jsonTool["owner"]["author"] =  $process_json["data"]["owner"]["author"];
		$jsonTool["owner"]["contact"] = $process_json["data"]["owner"]["contact"];
		$jsonTool["owner"]["user"] = $process_json["data"]["owner"]["user"];
	//external boolean
	if($process_json["data"]["external"] == 1) {
		$jsonTool["external"] = true;
	} elseif ($process_json["data"]["external"] == 0) {
		$jsonTool["external"] = false;
	};
	$jsonTool["keywords"] = $process_json["data"]["keywords"];
	$jsonTool["keywords_tool"] = $process_json["data"]["keywords_tool"];
	$jsonTool["status"] = $process_json["publication_status"];
	//infrastructure array
		$jsonTool["infrastructure"]["memory"] = $process_json["data"]["infrastructure"]["memory"];
		$jsonTool["infrastructure"]["cpus"] = $process_json["data"]["infrastructure"]["cpus"];
		$jsonTool["infrastructure"]["executable"] = $GLOBALS["oeb_tool_wrapper"];
		$jsonTool["infrastructure"]["wallTime"] = $process_json["data"]["infrastructure"]["wallTime"];
		for($i = 0; $i < sizeof($process_json["data"]["infrastructure"]["clouds"]); $i++) {
			if ($process_json["data"]["infrastructure"]["clouds"][$i] == "life-bsc") {
				$jsonTool["infrastructure"]["clouds"]["life-bsc"]["launcher"] = "SGE";
				$jsonTool["infrastructure"]["clouds"]["life-bsc"]["queue"] = "default.q";
			}
		}
	$jsonTool["has_custom_viewer"] = true;
		
	//Initialize variables
	$number_input_files = 0;
	$number_input_files_public_dir = 0;
	$number_arguments = 0;
	$nextflow_repo_uri = array();
	$nextflow_repo_tag = array();

	//get the number of all the keys
	$keys = array_keys($process_json["data"]["inputs_meta"]);
	$keysChallenge = array_keys($process_json["data"]["inputs_meta"]["challenges_ids"]["challenges"]);
	
	//define variables that contain the different arrays inside
	$jsonTool["input_files"] = [];
	$jsonTool["input_files_public_dir"] = [];
	$jsonTool["arguments"] = [];

	//input_files_combination
	$jsonTool["input_files_combinations"] = [array(
		"description" => "Run benchmarking workflow",
		"input_files" =>["input"]
	)];

	//input_files_combination_internal
	$jsonTool["input_files_combinations_internal"] = [[array(
		"participant" => 1
	)]];

	//arguments always in the same way
	//arguments - nextflow_repo_uri
	$nextflow_repo_uri = array(
		"name" => "nextflow_repo_uri",
		"description" => "Nextflow Repository URI",
		"help" => "Nextflow Repository (i.e https:\/\/github.com\/prj\/reponame)",
		"type" => "hidden",
		"value" => $process_json["data"]["nextflow_files"]["workflow_file"]["workflow_gitURL"],
		"required" => true
	);

	//arguments - nextflow_repo_tag
	$nextflow_repo_tag = array(
		"name" => "nextflow_repo_tag",
		"description" => "Nextflow Repository tag",
		"help" => "Nextflow Repository Tag version",
		"type" => "hidden",
		"value" => $process_json["data"]["nextflow_files"]["workflow_file"]["workflow_gitTag"],
		"required" => true
	);

	$jsonTool["arguments"] = [$nextflow_repo_tag, $nextflow_repo_uri];

	//do the functions knowing how many of each type there are
	for($i = 0; $i < sizeof($keys); $i++) {
		$type = $process_json["data"]["inputs_meta"][$keys[$i]]["type"];
		
		//structure of file user
		if ($type == "file_user") {

			//creating file_type array
			$file_types = array();
			for($x = 0; $x < sizeof($ontology_file_type); $x++) {
				for($j = 0; $j < sizeof($process_json["data"]["inputs_meta"][$keys[$i]]["file_type"]); $j++) {
					if ($process_json["data"]["inputs_meta"][$keys[$i]]["file_type"][$j] == $ontology_file_type[$x]["URI"]) {
						array_push($file_types, $ontology_file_type[$x]["label"]);
					}
				}
			}
			//creating data_type array
			$data_types = array();
			for($x = 0; $x < sizeof($ontology_data_type); $x++) {
				for($j = 0; $j < sizeof($process_json["data"]["inputs_meta"][$keys[$i]]["data_type"]); $j++) {
					if ($process_json["data"]["inputs_meta"][$keys[$i]]["data_type"][$j] == $ontology_data_type[$x]["URI"]) {
						array_push($data_types, $ontology_data_type[$x]["label"]);
					}
				}
			}

			//input_files
			array_push($jsonTool["input_files"], array(
				"name" => $process_json["data"]["inputs_meta"][$keys[$i]]["name"],
				"description" => $process_json["data"]["inputs_meta"][$keys[$i]]["label"],
				"help" => $process_json["data"]["inputs_meta"][$keys[$i]]["help"],
				"file_type" => $file_types,
				"data_type" => $data_types,
				"required" => true,
				"allow_multiple" => false
			));
		} 
		
		if ($type == "file_community" || $type == "dir_community") {

			//creating file_type array
			$file_types = array();
			for($x = 0; $x < sizeof($ontology_file_type); $x++) {
				for($j = 0; $j < sizeof($process_json["data"]["inputs_meta"][$keys[$i]]["file_type"]); $j++) {
					if ($process_json["data"]["inputs_meta"][$keys[$i]]["file_type"][$j] == $ontology_file_type[$x]["URI"]) {
						array_push($file_types, $ontology_file_type[$x]["label"]);
					}
				}
			}

			//creating data_type array
			$data_types = array();
			for($x = 0; $x < sizeof($ontology_data_type); $x++) {
				for($j = 0; $j < sizeof($process_json["data"]["inputs_meta"][$keys[$i]]["data_type"]); $j++) {
					if ($process_json["data"]["inputs_meta"][$keys[$i]]["data_type"][$j] == $ontology_data_type[$x]["URI"]) {
						array_push($data_types, $ontology_data_type[$x]["label"]);
					}
				}
			}

			//input_files_public_dir
			array_push($jsonTool["input_files_public_dir"], array(
				"name" => $process_json["data"]["inputs_meta"][$keys[$i]]["name"],
				"description" => $process_json["data"]["inputs_meta"][$keys[$i]]["label"],
				"help" => $process_json["data"]["inputs_meta"][$keys[$i]]["help"],
				"type" => "hidden",
				"value" => $process_json["data"]["inputs_meta"][$keys[$i]]["value"] . "/",
				"file_type" => $file_types,
				"data_type" => $data_types,
				"required" => true,
				"allow_multiple" => false
			));
		}

		//structure of the different arguments (string, integer, number, boolean, enum, enum_mult and hidden)
		if($type == "string" || $type == "integer" || $type == "number" || $type == "boolean" || $type == "enum" || $type == "enum_mult" || $type == "hidden") {
  			if ($process_json["data"]["inputs_meta"][$keys[$i]]["name"] == "challenges_ids") {
				for ($x = 0; $x < sizeof($keysChallenge); $x++) {
					array_push($descriptions, $process_json["data"]["inputs_meta"]["challenges_ids"]["challenges"][$keysChallenge[$x]]["description"]);
					array_push($names, $process_json["data"]["inputs_meta"]["challenges_ids"]["challenges"][$keysChallenge[$x]]["value"]);
				}
				array_push($jsonTool["arguments"], array(
					"name" => $process_json["data"]["inputs_meta"][$keys[$i]]["name"],
					"description" => $process_json["data"]["inputs_meta"][$keys[$i]]["label"],
					"help" => $process_json["data"]["inputs_meta"][$keys[$i]]["help"],
					"type" => $process_json["data"]["inputs_meta"][$keys[$i]]["type"],
					"default" => [],
					"required" => true,
					"enum_items" => array(
						"description" => $descriptions,
						"name" => $names
					)
				));
			} 
			if ($process_json["data"]["inputs_meta"][$keys[$i]]["name"] != "challenges_ids") {
				array_push($jsonTool["arguments"], array(
					"name" => $process_json["data"]["inputs_meta"][$keys[$i]]["name"],
					"description" => $process_json["data"]["inputs_meta"][$keys[$i]]["label"],
					"help" => $process_json["data"]["inputs_meta"][$keys[$i]]["help"],
					"type" => $process_json["data"]["inputs_meta"][$keys[$i]]["type"],
					"required" => true
				));
			} 
		}
	}


 	$keysOutput = array_keys($process_json["data"]["outputs_meta"]);
	
	$jsonTool["output_files"] = [];

	for($i = 0; $i < sizeof($keysOutput); $i++) {
		//creating file_type array
		$file_type = "";
		for($x = 0; $x < sizeof($ontology_file_type); $x++) {
			for($j = 0; $j < sizeof($process_json["data"]["outputs_meta"][$keysOutput[$i]]["file_type"]); $j++) {
				if ($process_json["data"]["outputs_meta"][$keysOutput[$i]]["file_type"][$j] == $ontology_file_type[$x]["URI"]) {
					$file_type = $ontology_file_type[$x]["label"];
				}
			}
		}

		//creating data_type array
		$data_type = "";
		for($x = 0; $x < sizeof($ontology_data_type); $x++) {
			for($j = 0; $j < sizeof($process_json["data"]["outputs_meta"][$keysOutput[$i]]["data_type"]); $j++) {
				if ($process_json["data"]["outputs_meta"][$keysOutput[$i]]["data_type"][$j] == $ontology_data_type[$x]["URI"]) {
					$data_type = $ontology_data_type[$x]["label"];
				}
			}
		}

		if ($process_json["data"]["outputs_meta"][$keysOutput[$i]]["name"] == "validation_results" || $process_json["data"]["outputs_meta"][$keysOutput[$i]]["name"] == "assessment_results") {
			array_push($jsonTool["output_files"], array(
				"name" => $process_json["data"]["outputs_meta"][$keysOutput[$i]]["name"],
				"required" => true,
				"allow_multiple" => false,
				"file" => array(
					"file_type" => $file_type,
					"file_path" => "assessment_datasets.json",
					"data_type" => $data_type,
					"compressed" => null,
					"meta_data" => array(
						"description" => "Metrics derivated from the given input data",
						"tool" => $process_json["_id"],
						"visible" => true
					)
				)
			));
		}

		if ($process_json["data"]["outputs_meta"][$keysOutput[$i]]["name"] == "tar_nf_stats") {
			array_push($jsonTool["output_files"], array(
				"name" => $process_json["data"]["outputs_meta"][$keysOutput[$i]]["name"],
				"required" => true,
				"allow_multiple" => false,
				"file" => array(
					"file_type" => $file_type,
					"data_type" => $data_type,
					"compressed" => "gzip",
					"meta_data" => array(
						"description" => "Other execution associated data",
						"tool" => $process_json["_id"],
						"visible" => true
					)
				)
			));
		}
	}

	$stringTool = json_encode($jsonTool, JSON_PRETTY_PRINT);
	return $stringTool;
}

function _validateToolSpefication($tool_data) {
	
	$errors = array();
	$data = json_decode($tool_data);

	// Validate
	$validator = new JsonSchema\Validator();
	//$validator->check($data, (object) array('$ref' => 'file://' . realpath('tool_schema_dev.json')));
	$validator->check($data, (object) array('$ref' => 'file://'.$GLOBALS['oeb_tool_json_schema']));
	//$validator->check($data, (object) array('$ref' => 'https://raw.githubusercontent.com/Multiscale-Genomics/VRE_tool_jsons/master/tool_specification/tool_schema_dev.json'));

	if ($validator->isValid()) {
		return $errors;
	} else {
		foreach ($validator->getErrors() as $error) {
			array_push($errors, array(
				$error['property'] => $error['message']
			));
		}
		return $errors;
	}
}