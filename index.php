<?php
define("DB_SERVER","localhost");
define("DB_USERNAME","root");
define("DB_PASSWORD","");
define("DB_DBNAME","unity");

$con = mysql_connect(DB_SERVER,DB_USERNAME,DB_PASSWORD);
if (!$con)
{
	die('Could not connect: ' . mysql_error());
}
mysql_select_db(DB_DBNAME, $con);

//===================================================
//===================================================
//===================================================
//===================================================

if (!isset($_GET["act"]))
	return;

if ($_GET["act"] == 'GetModelFile')
{
	GetModelFile($_POST["sid"]);
}
elseif ($_GET["act"] == 'DownloadModelFile')
{
	DownloadModelFile($_POST["filename"]);
}
elseif ($_GET["act"] == 'GetSemanticFile')
{
	GetSemanticFile($_POST["sid"]);
}
elseif ($_GET["act"] == 'SetServerAct')
{
	SetServerAct($_POST["sid"]);
}
elseif ($_GET["act"] == 'OpenHost')
{
	echo OpenHost();
}
elseif ($_GET["act"] == 'CloseHost')
{
	CloseHost($_POST["sid"]);
}
elseif ($_GET["act"] == 'ClientJoin')
{
	ClientJoin($_POST["sid"]);
}
elseif ($_GET["act"] == 'ClientSendModelFile')
{
	ClientSendModelFile($_GET["sid"],$_FILES["building"]["tmp_name"]);
}
elseif ($_GET["act"] == 'ClientSendSemanticFile')
{
	ClientSendSemanticFile($_GET["sid"],$_FILES["semantic"]["tmp_name"]);
}
elseif ($_GET["act"] == 'ListAvailableHost')
{
	ListAvailableHost();
}
elseif($_GET["act"] == "ClientIP")
{
	ClientIP($_POST["sid"]);
}
elseif ($_GET["act"] == 'UploadSemanticBack')
{
	UploadSemanticBack($_POST["sid"],$_POST["semantic"]);
}
elseif ($_GET["act"] == 'DownloadSemanticBack')
{
	DownloadSemanticBack($_POST["sid"]);
}
//===================================================
//===================================================
//===================================================
//===================================================

function DownloadSemanticBack($sid)
{
	$query = mysql_query("SELECT * FROM session WHERE s_id='{$sid}'");
	$row = mysql_fetch_array($query);
	return $row["s_semantic_back"];
}

function UploadSemanticBack($sid,$semantic)
{
	mysql_query("UPDATE session SET s_semantic_back='{$semantic}' WHERE s_id='{$sid}'");
}

function SetServerAct($sid)
{
	$query = mysql_query("SELECT * FROM session WHERE s_id='$sid'");
	$row = mysql_fetch_array($query);
	if ($row)
	{
		echo $row["s_activity"];
	}
}

function ClientIP($sid)
{
	$query = mysql_query("SELECT * FROM session WHERE s_id='$sid'");
	$row = mysql_fetch_array($query);
	if ($row)
	{
		mysql_query("UPDATE session SET s_activity='server_received_client' WHERE s_id='$sid'");
		echo $row["s_client_ip"];
	}
}

function GetModelFile($sid)
{
	$query = mysql_query("SELECT * FROM session WHERE s_id='$sid' AND s_status='opened' AND s_activity='send_file_to_host'");
	$row = mysql_fetch_array($query);
	if ($row)
	{
		file_put_contents("building.fbx",$row['s_model_file']);
	}
}

function GetSemanticFile($sid)
{
	$query = mysql_query("SELECT * FROM session WHERE s_id='$sid' AND s_status='opened'");
	$row = mysql_fetch_array($query);
	if ($row)
	{
		mysql_query("UPDATE session SET s_activity='server_downloaded' WHERE s_id='$sid'");
		echo $row['s_semantic_file'];
	}
}
/*
function DownloadModelFile($file_name)
{
	$file = 'model_files/' . $file_name;

	if (file_exists($file)) {
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.basename($file));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		ob_clean();
		flush();
		readfile($file);
		exit;
	}
}
*/
function OpenHost()
{
	$ip = GetRemoteIP();	
	mysql_query("UPDATE session SET s_status='closed' WHERE s_host_ip='$ip'");
	mysql_query("INSERT INTO session SET s_host_ip='$ip', s_status='opened', s_activity='waiting_client'");
	return mysql_insert_id();
}

function CloseHost($sid)
{
	mysql_query("UPDATE session SET s_status='closed' WHERE s_id='$sid'");
}

function ClientJoin($sid)
{
	$ip = GetRemoteIP();	
	mysql_query("UPDATE session SET s_client_ip='$ip', s_activity='client_connected' WHERE s_id='$sid'");
}

function ClientSendModelFile($sid,$buidling)
{
	move_uploaded_file($buidling, "models/".$sid.".fbx");
	mysql_query("UPDATE session SET s_model_file='".$sid.".fbx',
		s_activity='model_uploaded_1' WHERE s_id='$sid'");
}

function ClientSendSemanticFile($sid,$semantic)
{
	$semanticfile = file_get_contents($semantic);
	
	mysql_query("UPDATE session SET s_semantic_file='$semanticfile', 
		s_activity='model_uploaded' WHERE s_id='$sid'");
}

function ListAvailableHost()
{
	$host = '';
	$query = mysql_query("SELECT * FROM session WHERE s_status='opened' AND s_activity='waiting_client'");
	while($row = mysql_fetch_array($query))
	{
		if ($host == '')
			$host .= $row['s_id'].",".$row["s_host_ip"];
		else
			$host .= ";".$row['s_id'].",".$row["s_host_ip"];
	}
	echo $host;
}

function GetRemoteIP()
{
	$ip = "";
	if ( isset($_SERVER["REMOTE_ADDR"]) )    { 
		$ip = $_SERVER["REMOTE_ADDR"]; 
	} else if ( isset($_SERVER["HTTP_X_FORWARDED_FOR"]) )    { 
		$ip = $_SERVER["HTTP_X_FORWARDED_FOR"]; 
	} else if ( isset($_SERVER["HTTP_CLIENT_IP"]) )    { 
		$ip = $_SERVER["HTTP_CLIENT_IP"]; 
	} 
	return $ip;
}
?>