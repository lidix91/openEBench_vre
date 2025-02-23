<?php
//OEB_Publish: Allows to select which run files to publish to OEB

require __DIR__."/../../../config/bootstrap.php";
redirectOutside();

require "../../htmlib/header.inc.php";

//project list of the user
$projects = getProjects_byOwner();


//get bechmarking events particapated from output files in workspace
$benchmarkingEvents_participated = array();
$outputExe_files = json_decode(getPublishableFiles(array("OEB_data_model", "participant_validated")), true);

foreach ($outputExe_files as $key => $value) {
    //not include if already exists
    foreach($value as $k=>$v){
        if(!in_array($value['benchmarking_event'], $benchmarkingEvents_participated)){
            array_push($benchmarkingEvents_participated,$value['benchmarking_event']);
        }
    }
}

?>

<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white 
    page-container-bg-solid page-sidebar-fixed">
    <div class="page-wrapper">
        <input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>"/>

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
                            <button type="button" class="close" data-dismiss="alert" 
                                aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </h4>
                        <p>You don't have the proper permissions for publishing 
                            benchmarking datasets to OpenEBench. Only owners, 
                            managers and benchmarking contributors are allowed.</p>
                        
                        <p class="mb-0">You can request that permision sending a 
                            ticket to helpdesk: <a href="/vre/helpdesk/?sel=roleUpgrade">
                                click here!</a>
                        </p>
                    </div>
                </div>
                <!-- END PAGE WARNING-->

                <!-- BEGIN PAGE TITLE-->
		        <h1 class="page-title"> Benchmarking data Publication &ndash; New Submission
		            <i class="icon-question tooltips" data-container="body" 
                        data-html="true" data-placement="right" data-toggle="tooltip" 
                        data-trigger="click" data-original-title="<p align='left' 
                        style='margin:0'>Create here a request for publishing 
                        your benchmarking data to OpenEBench. <a target='_blank' 
                        href='<?php echo $GLOBALS['OEB_doc'];?>/how_to/participate/publish_oeb.html'> +Info</a>.</p>">
                    </i>
		        </h1>
                <!-- END PAGE TITLE-->
                <!-- END PAGE HEADER-->

                <div class="row">
                    <div class="col-md-12">
                        <div class="mt-element-step">
                            <div class="row step-line">
                                <div class="col-md-4 mt-step-col first active">
                                    <div class="mt-step-number bg-white">1</div>
                                    <div class="mt-step-title uppercase 
                                        font-grey-cascade">Select datasets
                                    </div>
                                </div>
                                <div class="col-md-4 mt-step-col second">
                                    <div class="mt-step-number bg-white">2</div>
                                    <div class="mt-step-title uppercase 
                                        font-grey-cascade">Edit metadata's file
                                    </div>
                                </div>
                                <div class="col-md-4 mt-step-col last">
                                    <div class="mt-step-number bg-white">3</div>
                                    <div class="mt-step-title uppercase 
                                        font-grey-cascade">Summary
                                    </div>
                                </div>
                            </div>
                        </div>
					</div>
			    </div>

            <!-- DISPLAY ALERTS AND INFOS-->
            <div id="alert" style="position: absolute; top: 20px; right: 0; z-index: 2" ></div>
            <!--<div class="alert alert-warning" style="display:none"></div>-->
            <div class="alert alert-info" style="display:none"></div>

            <!-- BEGIN SELECT AND TABLE PORTLET -->
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="portlet light bordered">
                        <div class="portlet-title">
                            <div class="caption">
                                <i class="icon-share font-dark hide"></i>
                                <span class="caption-subject font-dark bold uppercase">Select 
                                    Benchmarking Event</span> <small style="font-size:75%;">
                                    Choose the event where you want to contribute with 
                                    your benchmarking datasets. Notice you'll be asked 
                                    to associate your results to a registered participant 
                                    tool or workfow.</small>
                            </div>
                        </div>
                        <!--only communities you are allowed to submit will be apperar-->
                        <div class="portlet-body">
                            
                            <div class="input-group" style="margin-bottom:20px;">
                                <span class="input-group-addon" style="background:#5e738b;">
                                    <i class="fa fa-users font-white"></i>
                                </span>
                                <select id="beSelector" class="form-control" style="width:100%;">
                                    <option value="">Select Benchmarking Event </option>
                                    <?php 
                                    foreach ($benchmarkingEvents_participated as $k => $v) { ?>
                                        <option value="<?php echo $v['be_id'] ?>"><?php echo $v['be_name'] ?></option>
                                    <?php } ?>
                                </select>
                                    
							</div>
                            <div id="errorNotAllowed" style="display:none; color:red;">
                                <p><i class="fa fa-exclamation-triangle"></i>You 
                                    don't have permisions to publish datasets 
                                    for the selected benchmarking event. You can 
                                    request it by sending a ticket: <a>click here!</a>
                                </p>
                            </div>
                            <div id="maxReqBE" style="display:none; color:red;">
                                <p><i class="fa fa-exclamation-triangle"></i>Remember: you
                                are only allowed to publish a maximum of <b></b> results for your tool.
                                You have done <span></span> requests. 
                                </p>
                            </div>
                            <div id ="availableFiles" style="display:none;">
                            <span class="caption-subject font-dark bold uppercase">
                                FILES AVAILABLE TO BE PUBLISHED </span> <small 
                                style="font-size:85%;">Choose the datasets you 
                                want to request to publish in OpenEBench web page.</small>
                            
                            <div id ="tableMyFiles" >
                                <table id="communityTable" class="table table-striped 
                                    table-hover table-bordered" width="100%"></table>
                            </div>
                            <button  disabled type="button" class=" btn green" 
                                id="btn-selected-files" onclick="submitFiles(event);return false;" 
                                style="margin-top:20px;">Next
                            </button>
                            <br>
                            <br>
                            <span class="caption-subject font-dark bold uppercase 
                                titleBE" style="display:none">PUBLISHED FILES ON OPENEBENCH 
                                FOR THE SELECTED BENCHMARKING EVENT</span>
                            <div id = "tablePublishedFiles" style="display:none">
                                <table id="publishedFiles" class="table table-striped 
                                table-hover table-bordered" width="100%"></table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- END SELECT AND TABLE  PORTLET -->
        <!-- BEGIN LIST TO MANAGE FILES -->
        <div class="row" style="display:none;">
            <div class="col-md-12 col-sm-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="icon-share font-dark hide"></i>
                            <span class="caption-subject font-dark 
                                bold uppercase">Selected File(s)</span>
                        </div>

                        <div class="actions" style="display:none!important;" id="actions-files">
                            <div class="btn-group">
                                <a class="btn btn-sm blue-madison" href="javascript:;" data-toggle="dropdown">
                                    <i class="fa fa-cogs"></i> Actions
                                    <i class="fa fa-angle-down"></i>
                                </a>
                                <ul class="dropdown-menu pull-right" role="menu">
                                    <li>
                                        <form id ="files-form" action="oeb_publish/oeb/oeb_editMetadata.php" method="post">
                                            <input name = "files" id ="filesInput" value="" type="hidden">
                                            <button type="button" onclick="submitFiles(event);return false;">Submit selected files</button>
                                        </form>
                                    </li>
								</ul>
                            </div>
                            <div class="btn-group">
                                <a class="btn btn-sm red pull-right" id="btn-rmv-all" href="javascript: removeFromList('all');">
                                    <i class="fa fa-times-circle"></i> Clear all files from list
                                </a>

                            </div>
                        </div>
                    </div>

                    <div class="portlet-body">
                        <div class="" data-always-visible="1" data-rail-visible="0">
                            <ul class="feeds" id="list-files-submit"></ul>
                            <div id="desc-files-submit">In order to select the file 
                                to submit, please select them clicking on the 
                                checkboxes from the table above.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            
        <!-- END LIST TO MANAGE FILES -->
        <!-- Modal -->
        <div class="modal fade" id="reqSubmitDialog" tabindex="-1" role="dialog" 
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="exampleModalLabel">Are you sure 
                            you want to request to publish this files?
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" 
                            aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table class="table" id ="filesAboutToSubmit"></table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button id = "submitModal" type="button" class="btn btn-primary">Submit</button>
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

            .alert-success {
                background-color: #dff0d8;
                border-color: #dff0d8;
                color: #3c763d;
            }

            ul.hidden :nth-child(n+2) {
                display:none;
            }
            .ul-challenges li:nth-child(n+4) {
                display:none;
            }

        </style>

        <script>
            var redirect_url = "oeb_publish/oeb/oeb_newReq.php";

            
            function loadCommunity(op) {
                console.log(op.value)
                location.href = baseURL + redirect_url + "?community=" + op.value;

            }
                
        </script>
