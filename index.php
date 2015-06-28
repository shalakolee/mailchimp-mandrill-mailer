<?php 

if($_REQUEST["function"]=="sendemails"){
	echo sendEmail();
}else{

?>

<!DOCTYPE html>


<html>
<head>
<!-- jquery -->
<script type="text/javascript" src="js/jquery-2.1.1.min.js"></script>
<!-- jquery ui -->
<link rel="stylesheet" href="js/jqueryui/jquery-ui.css" type="text/css" />
<script type="text/javascript" src="js/jqueryui/jquery-ui.js"></script>
<!-- bootstrap -->

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>

 <!-- fontawesome -->
<link rel="stylesheet" href="js/fa/css/font-awesome.css" type="text/css" />

<!-- chosen -->
<link rel="stylesheet" href="js/chosen/chosen.css" type="text/css" />
<script type="text/javascript" src="js/chosen/chosen.jquery.js"></script>


<link rel="stylesheet" href="css/style.css" type="text/css" />
</head>


<body>


<?php
/*
 * Keys for mailchimp and mandrill
 */

//these are your mandrill keys, its an array of displayname=>keyvalue this must match with the mailchimp keys
// need to make this so dont need both.
$mandrill_keys = array(
	'DISPLAY_NAME_HERE' 	=> 'KEY_HERE',
	'DISPLAY_NAME_HERE'			=> 'KEY_HERE'
	);


$mailchimp_keys = array(
	'DISPLAY_NAME_HERE' 	=> 'KEY_HERE',
	'DISPLAY_NAME_HERE'			=> 'KEY_HERE'
	);

?>
<div id="loadingDiv"><img id="loadingimg" src="http://bit.ly/pMtW1K"><span id="loadingtext"></span></div>
<form method="post">
<div class="panel panel-primary" style="margin:auto;margin-top:20px; width:70%">
	<div class="panel-heading">Mandrill & Mailchimp Email System</div>
	<div class="panel-body">
		<div class="lefthalf">
			<div class="panel panel-default">
				<div class="panel-heading">Email Options</div>
				<div class="panel-body">
					
					<em>Select Lists</em><br />
					<select id="apikey" name="apikey">
						<option value=''>-- Please Select --</option>
						<?php
							foreach($mailchimp_keys as $key=>$value){
								if($value==$_REQUEST['apikey']){
									echo "<option value='{$value}' selected='selected'>{$key}</option>";
								}else{
									echo "<option value='{$value}'>{$key}</option>";
								}
							}
						 ?>
					</select>
					<?php if($_REQUEST['apikey']){ ?>
					<?php
						$lists = getlists($_REQUEST['apikey']);
						if($lists){
							?>
							<select id="mailchimplist" name="mailchimplist">
							<option value=''>-- Please Select --</option>
							<?php
							foreach($lists["data"] as $list){
								if($list['id']==$_REQUEST['mailchimplist']){
									echo "<option value='{$list['id']}' selected='selected'>{$list['name']}</option>";
								}else{
									echo "<option value='{$list['id']}'>{$list['name']}</option>";
								}
							}
							?>
							</select>
							<?php
						}
					?>

					<?php } ?>
					<?php if($_REQUEST['mailchimplist']){ ?>

						<hr />
						<em>Select template to send</em><br />
						<select id="template" name="template">
							<option value=''>-- Please Select --</option>
						<?php 
						$key = array_search($_REQUEST['apikey'], $mailchimp_keys);   
						$templates = getTemplates($mandrill_keys[$key]); 
						foreach($templates as $template){
							//echo var_dump($template['slug']);
							if($template['slug']==$_REQUEST['template']){
								echo "<option value='{$template['slug']}' selected='selected'>{$template['name']}</option>";
							}else{
								echo "<option value='{$template['slug']}'>{$template['name']}</option>";
							}
						}
						?>
						</select>
					<?php } ?>



				</div>
			</div>
			<?php if($_REQUEST['mailchimplist']){ ?>
			<?php $listmembers = getMembers($_REQUEST['mailchimplist'],$_REQUEST['apikey']); ?>
			<?php 
				array_shift($listmembers);
				$listmembers = array_filter($listmembers);
				//echo var_dump($listmembers);
			?>

			<div class="panel panel-default">
				<div class="panel-heading">Addresses in this list <span class="badge"><?php echo count($listmembers); ?></span></div>
<!-- 				<div class="panel-body">
				</div> -->
				<?php //echo var_dump($listmembers); ?>
				<ul class="list-group" style="max-height:500px;overflow-y:scroll">
					<?php

						foreach($listmembers as $listmember){
							//echo var_dump($listmember);
							$member = explode(",",preg_replace('~[\["]~','',$listmember));
							echo "<li class='list-group-item'>{$member[0]}</li>";
						}

					?>
					
				</ul>
			</div>
			<?php } ?>
		</div>
		<div class="righthalf">
			<?php if($_REQUEST['template']){ ?>
			<div class="panel panel-default">
				<div class="panel-heading">Template Options</div>
				<div class="panel-body">
					<em>Subject:</em>
					<input type="text" id='subject' name="subject" placeholder="Enter a Subject" class="form-control" required />

					<em>From Email: </em>
					<input type="email" name="from_email" id="from_email" placeholder="Email who this is from" class="form-control" required />

					<em>Display Name: </em>
					<input type="text" name="display_name" id="display_name" placeholder="Name who this is from" class="form-control" required />

					<em>Reply-To Address: </em>
					<input type="email" name="reply_to" id="reply_to" placeholder="Enter Reply to address" class="form-control" required />
					
					<?php if($_REQUEST['sendemails']=="send"){?>
						<div class="btn btn-primary" id="btnSubmit" style="margin-top:10px;float:right;">reset</div>
						<input type="hidden" name="sendemails" value="no" />

					<?php }else{ ?>
						<input type="hidden" name="sendemails" value="send" />
						<div class="btn btn-primary" id="btnSubmit" style="margin-top:10px;float:right;">Send Emails!</div>
					<?php } ?>


				</div>
			</div>
			<?php } ?>
			<div class="panel panel-default">
				<div class="panel-heading">Status</div>
				<div style="max-height:222px;overflow-y:scroll">
				<table class="table table-striped" id="status" >
					<?php 
						$key = array_search($_REQUEST['apikey'], $mailchimp_keys); 
						$mandrillkey = $mandrill_keys[$key];
						$emaillist = [];
						foreach($listmembers as $listmember):
							$email = explode(",",preg_replace('~[\["]~','',$listmember));
							array_push($emaillist, $email[0]);
							?>
						<?php endforeach; ?>
						<script>
							$("#btnSubmit").on("click",function(){
								//$(this).closest("form").submit();
								var emaillist 		= '<?php echo implode(",",$emaillist); ?>';
								var subject 		= $("#subject").val();
								var from_email 		= $("#from_email").val();
								var reply_to 		= $("#reply_to").val();
								var display_name 	= $("#display_name").val();
								var mandrillkey 	= '<?php echo $mandrillkey; ?>';
								var template 		= '<?php echo $_REQUEST["template"]; ?>';
								var tags 			= '<?php echo $_REQUEST["tags"]; ?>';
								/* actually send the email now */
								sendemail(emaillist,subject, from_email, reply_to, display_name, mandrillkey, template, tags);
							});

						</script>

				</table></div>
			</div>
		</div>
		<div style="clear:both;"></div>
	</div>
</div>
</form>

<script>
	$("#mailchimplist").chosen({width: "49%"});
	$("#template").chosen({width: "49%"});
	$("#apikey").chosen({width: "49%"});
	$("#apikey, #mailchimplist, #template").on("change",function(){
		$(this).closest("form").submit();
	});
	var $loading = $('#loadingDiv').hide();
	$(document)
	  .ajaxStart(function () {
	    $loading.show();
	  })
	  .ajaxStop(function () {
	    $loading.hide();
	  });

function sendemail(emaillist, subject, from_email, reply_to, display_name, mandrillkey, template, tags ){
//function sendemail(emaillist, something){
	var emailaddresses = emaillist.split(',');
	var i=0;


	for(i;i< emailaddresses.length;i++){
		//alert(emailaddresses[i]);
    	$("#loadingtext").text("Sending: "+ emailaddresses[i]);

    	$.ajax({ 
        	url: "<?php echo $_SERVER['PHP_SELF']; ?>", 
        	data: {
        		'function': "sendemails",
        		email: emailaddresses[i],
        		subject: subject,
        		from_email: from_email,
        		reply_to: reply_to,
        		display_name: display_name,
        		mandrillkey: mandrillkey,
        		template: template,
        		tags: tags,
        	}, 
        	datatype: 'text',
        	async: true,
       		type: 'post',
        	cache: false })
    	.done(function(thedata) { 
			var obj = $.parseJSON(thedata);
			console.log(obj);
			if(obj.status == "error"){
				alert(obj.message);
			}else{
				if(obj[0].reject_reason == null){
	    			$("#status").append('<tr><td><span class="glyphicon glyphicon-ok" aria-hidden="true" style="color:#5cb85c;"></span><td><td>' + obj[0].email + "</td><td>" + obj[0].status + "</td><td>&nbsp;</td></tr>");
		    	}else{
	    			$("#status").append('<tr><td><span class="glyphicon glyphicon-remove" aria-hidden="true" style="color:#d9534f;"></span><td><td>' + obj[0].email + "</td><td>" + obj[0].status + "</td><td>" + obj[0].reject_reason + "</td></tr>");
		    	}	
	    	}

        });


        
	//$("#status").animate({scrollTop:$("#status")[0].scrollHeight - $("#status").height()},1000);
	}

	/*close the table*/
}

</script>

</body>
</html>
<?php
}//end ifthen for mailer function
/*
 * Mailer Functions
 */

function getMembers($id,$apikey){
 $uri = 'http://us7.api.mailchimp.com/export/1.0/list?apikey='.$apikey.'&id='.$id;

// $uri = 'https://us7.api.mailchimp.com/2.0/lists/members.json';
    // $postString = '{
    // "apikey":"' . $apikey . '",
    // "id": "'.$id.'"
    // }';
    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);

    $result = curl_exec($ch);
	$obj = explode("\n",$result);
	//echo var_dump(explode("\n",$result));
	//echo var_dump($obj);
	return $obj;

}//end getlist
function getLists($apikey){
$uri = 'https://us7.api.mailchimp.com/2.0/lists/list.json';
    $postString = '{"apikey":"' . $apikey . '"}';
    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
    $result = json_decode(curl_exec($ch),true);
    return $result;
}
/* Get the templates available on mandrill */
function getTemplates($apikey){
$uri = 'https://mandrillapp.com/api/1.0/templates/list.json';
    $postString = '{"key":"' . $apikey . '"}';
    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
    $result = json_decode(curl_exec($ch),true);
    return $result;
}
function gettags($apikey){
$uri = 'https://mandrillapp.com/api/1.0/tags/list.json';
    $postString = '{"key":"' . $apikey . '"}';
    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
    $result = json_decode(curl_exec($ch),true);
    return $result;
}
//function sendEmail1($emailaddress){return $emailaddress;}
function sendEmail(){
$uri = 'https://mandrillapp.com/api/1.0/messages/send-template.json';
	
$subject = $_POST['subject'];
$from_email = $_POST['from_email'];
$reply_to = $_POST['reply_to'];
$display_name = $_POST['display_name'];
$mandrillkey = $_POST['mandrillkey'];
$template = $_POST['template'];
$subaccount = $_POST['subaccount'];
$tags=$_POST['tags'];
$email = $_POST['email'];
$customhtml = $_POST['customhtml'];

	
	if($email!=""){
		$postString = '{
		    "key": "'. $mandrillkey . '",
		    "template_name": "'.$template .'",
		    "template_content": [
		            {
		                "name": " ",
		                "content": " "
		            }
		        ],
		    "message": {
		        "subject": "'. $subject . '",
		        "from_email": "'. $from_email . '",
		        "from_name": "'. $display_name . '",
		        "to": [
		            {
		                "email": "'. $email .'",
		                "name": " "
		            }
		        ],
		        "headers": {
		            "Reply-To": "'. $reply_to . '"
		        },
		        "tags": [
		            "' . $tags . '"
		        ],
		        "important": false,
		        "track_opens": true,
		        "track_clicks": true
		    },
		    "async": true
		}';
		$ch = curl_init($uri);
		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);

		$result = curl_exec($ch);
		//echo $result;
		return $result;

	}

}


?>