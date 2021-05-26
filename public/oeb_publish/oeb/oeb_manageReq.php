<?php
//Allows to select which files to publish.

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
                            <span>Publish data</span>
                        </li>
                    </ul>
                </div>
            
                <!-- END PAGE BAR -->

                
                <!-- BEGIN PAGE WARNING-->
                <div id= "warning-notAllowed" style="display:none;">
                    <br>
                    <div class="alert alert-warning expand" role="alert">
                        <h4 class="alert-heading bold">You are not allowed
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </h4>
                        <p>You don't have the properly permisions to request to publish datafiles. Only owners, managers and challanege contributors are allowed.</p>
                        
                        <p class="mb-0">You can request that permision sending a ticket to helpdesk: <a href="/vre/helpdesk/?sel=roleUpgrade">click here!</a></p>
                    </div>
                </div>
                <!-- END PAGE WARNING-->

                <!-- BEGIN PAGE TITLE-->
                <h1 class="page-title"> Publish data</h1>

                <!-- END PAGE TITLE -->
                <!-- END PAGE TITLE-->
                <!-- END PAGE HEADER-->

                <!-- BEGIN LIST OF ALL FILES -->

                <div class="row">
					<div class="col-md-12 col-sm-12">
						<div class="portlet light bordered">
                        <?php //var_dump ($_SESSION['User']['TokenInfo']['oeb:roles']);?>
							<div class="portlet-title">
								<div class="caption">
									<span class="caption-subject font-dark bold uppercase">Your Publication requests registers</span>
								</div>
                            </div>
                            <div class="portlet-body">
                                <!-- Show errors from frontend-->
                	            <div id="myError"style="display:none;"></div>
                                <div id="files">
                                    <table id="tableAllFiles" class="table table-striped table-hover table-bordered" width="100%"></table>
                                </div>
                            </div>
                        </div>
                        <div id ="approvalSection" class="portlet light bordered" 
                        style="display:<?php if (str_contains($_SESSION['User']['TokenInfo']['oeb:roles'][0], 'owner') || str_contains($_SESSION['User']['TokenInfo']['oeb:roles'][0], 'supervisor')) echo "block"; else echo "none"?>;" >
                            <div  class="portlet-title">
                                <div class="caption">
                                    <span class="caption-subject font-dark bold uppercase">Pending approval Publication requests</span>
                                </div>
                            </div>
                            <div class="portlet-body">
                            <!-- LOADING SPINNER-->
                                <div id="loading-datatable" class="loadingForm">
                                    <div id="loading-spinner">LOADING</div>
                                    <div id="loading-text">It could take a few minutes</div>
                                </div>
                                <div id="pendingReq">
                                    <table id="tableApprovals" class="table table-striped table-hover table-bordered" width="100%"></table>
                                </div>
                            </div>
                        </div>
                               
                        </div>
                    </div>
                </div>
            </div>
            <!-- END LIST OF ALL FILES -->
            <!-- Modal Action Confirmation -->
            <div class="modal fade" id="actionDialog" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="modalTitle"></h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <ul class="feeds" id="file-action">
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
                                                    <span id="file" class="text-info" style="font-weight:bold;"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                            <br>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button id = "acceptModal" type="button" class="btn btn-primary">Accept</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal Log -->
            <div class="modal fade" id="modalLog" role="dialog">
                <div class="modal-dialog modal-lg">"
                    <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" onclick="closeModal()" class="close" data-dismiss="modal" aria-hidden="true"></button>
                            <h3 class="modal-title">
                                <span>
                                    <i class="fa fa-list"></i>
                                </span>
                                <b>Log error </b>
                            </h3>
                        </div>
                        <div class="modal-body table-responsive">
                            <h4 class="text-info" style="font-weight:bold;" >Log: </h4>
                            <div style="max-height:400px;" id ="modalContent"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" id="closeModal" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer-->
            <?php 
            require "../../htmlib/footer.inc.php"; 
            require "../../htmlib/js.inc.php";
            ?>                                    
            <style>
                .hide_column {
                    display : none;
                }
            </style>
            <script>
$(document).ready(function(){
  $('[data-toggle="popover"]').popover();
});
</script>

           