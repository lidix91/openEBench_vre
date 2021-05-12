<?php
//Allows to edit metadata file before submit

require __DIR__."/../../../config/bootstrap.php";
redirectOutside();

require "../../htmlib/header.inc.php";


//project list of the user
$projects = getProjects_byOwner();

//$communities = getCommunities("OEBC004", "name");
//var_dump($communities);
//var_dump($_SESSION['errorData']['Warning']);


if (!is_null ($_SESSION['User']['TokenInfo']['oeb:roles'])) {
    $communityList = getCommunitiesFromRoles($_SESSION['User']['TokenInfo']['oeb:roles']);
} else {
    $communityList = array("Filter files by community");
}


?>


<script src="https://unpkg.com/@trevoreyre/autocomplete-js"></script>
<link rel="stylesheet" href="https://unpkg.com/@trevoreyre/autocomplete-js/dist/style.css"/>
<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-container-bg-solid page-sidebar-fixed">
    <div class="page-wrapper">
        <input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>" />

        <?php
        require "../../htmlib/top.inc.php"; 
        require "../../htmlib/menu.inc.php";
        ?>


        <!-- BEGIN CONTENT -->
        <div class="page-content-wrapper">
            <!-- BEGIN CONTENT BODY -->
            <div class="page-content">
                <!-- BEGIN PAGE HEADER-->
                <!-- BEGIN PAGE BAR -->
                <div class="page-bar">
                    <ul class="page-breadcrumb">
                        <li>
                            <a href="home/">Home</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span>OEB</span>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span>Edit metadata</span>
                        </li>
                    </ul>
                </div>
                <!-- END PAGE BAR -->

                <!-- BEGIN PAGE TITLE-->
                <h1 class="page-title"> Edit metadata's file</h1>
                <!-- END PAGE TITLE -->
                <!-- END PAGE TITLE-->
                <!-- END PAGE HEADER-->
                <div class="row">
                    <div class="col-md-12">
                        <div class="mt-element-step">
                            <div class="row step-line">
                                <div class="col-md-4 mt-step-col first active">
                                    <div class="mt-step-number bg-white">1</div>
                                    <div class="mt-step-title uppercase font-grey-cascade">Select datasets</div>
                                </div>
                                <div class="col-md-4 mt-step-col second active">
                                    <div class="mt-step-number bg-white">2</div>
                                    <div class="mt-step-title uppercase font-grey-cascade">Edit metadata's file</div>
                                </div>
                                <div id ="step3" class="col-md-4 mt-step-col last">
                                    <div class="mt-step-number bg-white">3</div>
                                    <div class="mt-step-title uppercase font-grey-cascade">Summary</div>
                                </div>
                            </div>
                        </div>
					</div>
			    </div>

                <!-- BEGIN LIST OF ALL FILES -->

                <div class="row">
                    <div class="col-md-12">
                        <div class="portlet light portlet-fit bordered">
                            <div class="portlet-body">
                         
				                <h3 style="font-weight: bold; color: #666;">List of files</h3>

                                <div data-always-visible="1" data-rail-visible="0">
                                    <div>List of files to request to publish.</div><br/>
                                    <ul class="feeds" id="list-files-run-tools">
                                        <?php 
                                        if (isset($_REQUEST['files']) ){
                                           
                                            $fns = json_decode($_REQUEST['files']);
                                            foreach($fns as $file){
                                                $fnPath    = getAttr_fromGSFileId($file->id,'path');
                                                $filename  = basename($fnPath);
                                                $filedir   = basename(dirname($fnPath));
                                                $be_id     = $file->benchmarkingEvent_id;

                                        ?>	
                                        <li>
                                            <div class="col1">
                                                <div class="cont">
                                                    <div class="cont-col1">
                                                        <div class="label label-sm label-info">
                                                            <i class="fa fa-file"></i>
                                                        </div>
                                                    </div>
                                                    <div class="cont-col2">
                                                        <div class="desc">
                                                            <span class="text-info">
                                                                <b><?php echo $filedir; ?>/</b><?php echo $filename;?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            

                                        <?php }}?>

                                        </li>
                                    </ul>
                                   
                                </div>
			                </div>
                        </div>
                    </div>
                </div>
                <!-- END LIST OF ALL FILES -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="portlet light portlet-fit bordered">
                            <div id="blocks" class="portlet-body">
                                <!-- LOADING SPINNER-->
                                <div id="loading-datatable" class="loadingForm">
                                    <div id="loading-spinner">LOADING</div>
                                    <div id="loading-text">It could take a few minutes</div>
                                </div>
                                
                                <div id ="formMetadata">
                                    <div id ="editor_holder"></div>
                                    <br>
                                    <button id="sendForm" class="btn btn-primary" disabled>Send</button><span id='valid_indicator'></span>
                                    <br>
                                    <p class="errorClass" id="idP" style="display:none;"></p>
                                    
                                    
                                </div>
                                <!-- Show errors from frontend-->
                	            <div id="myError"style="display:none;"></div>
                            
                            </div>
                        </div>  
                    </div>

                </div>

            </div>
        </div> 
    </div>
            

<!-- Footer-->
<?php 
require "../../htmlib/footer.inc.php"; 
require "../../htmlib/js.inc.php";
?>                                    
<style type="text/css">
    label {
        font-weight: bold;
    }
    .form-group .required {
        font-size: 14px;
        color: #333;
    }
    .invalid-feedback {
        color: red;
    }

</style>
<?php
   $files = $_REQUEST['files'];
?>

<script type="text/javascript">
    var files = '<?php echo $files;?>'
</script>