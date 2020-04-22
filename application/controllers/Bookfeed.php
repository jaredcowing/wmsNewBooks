<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bookfeed extends CI_Controller {
	public function __construct(){
		parent::__construct();
		$this->load->model('Newbooks_model');
		$this->load->helper('url_helper');
		$this->load->library('session');
		$this->load->helper('form');
		$this->load->library('form_validation');
		$this->load->library('NewBooksConfig');
	}

	public function index()															//Default/homepage, I am not utilizing so just load a bare page.
	{
		$this->load->view('header');
		$this->load->view('footer');
	}
	
	public function load($target){
	/*Load function will request & ingest data from OCLC
	orders=load new orders & trigger functions to populate any copies/items therein
	items=manually trigger item load on a hard-coded order number and display results, for testing only
	copies=manually trigger copy load on a hard-coded OCN and display results, for testing only
	*/
		$baseURL=$this->newbooksconfig->getBaseURL();
		$userok=$this->session->all_userdata();
		if(array_key_exists('ok', $userok)) {
			if($userok['ok']=='yes'){
				if($target=='orders'){
					$args=func_get_args();
					if(count($args)>2){			//Check whether a reduced load amount and start number have been indicated
						$startIndex=intval($args[2]);
						$stopAfter100=boolval($args[1]);
					}
					else{
						$startIndex=0;
						$stopAfter100=FALSE;
					}
					$doneFlag=false;
					$newAdds=array();														//This is just to populate the log afterwards
					$newItems=array();														//This is just to populate the log afterwards
					$statuteLimitations=$this->newbooksconfig->getStatute();
					while($doneFlag==false){
						$resourceURLp2="/purchaseorders?q=SUBMITTED&startIndex=".$startIndex."&itemsPerPage=100";
						/* The first time you run application, you may want to comment above line and uncomment the below.
						If you do, also see similar comment a few lines below.
						This will allow you to manually determine how many orders to test loading, instead of letting
						application keep loading and loading tens of thousands of books all at once.
						Especially important if using Google Books API because they will cap you at 1,000 requests per day!
						*/
						//$resourceURLp2="/purchaseorders?q=SUBMITTED&startIndex=0&itemsPerPage=10";
						$resourceURLp1="https://acq.sd00.worldcat.org";
						$data=$this->oclcTransmit($resourceURLp1,$resourceURLp2);
						$dataP=json_decode($data);
						$dataP2=$dataP->entry;
						$orderCount=0;
						foreach($dataP2 as $order){
							$orderCount++;
							$orderDate=$order->insertTime;
							if(date("Y:m:d",$orderDate/1000)>$statuteLimitations){		//If order was placed after statute of limitations
								$msg=$this->Newbooks_model->saveOrder($order->purchaseOrderNumber);
								if($msg=='ok'){
									array_push($newAdds,$order->purchaseOrderNumber);
									$newItemsMsg=$this->autoLoadItems($order->purchaseOrderNumber);
									array_push($newItems,$newItemsMsg);
								}
								else{
									$newItemsMsg="";
									echo $msg;
								}
							}
							else{echo "<br />".$order->purchaseOrderNumber." ignored b/c ".date("Y:m:d",$orderDate/1000);					//Remove after testing
								echo " precedes statute.";
							}
						}
						if($orderCount<100||$stopAfter100==TRUE){
							$doneFlag=TRUE;
						}
						else{
							$startIndex+=100;
						}
					}
					$newNum=count($newAdds);
					echo "<br /><br />Update complete! ".$newNum." new orders added to local database.";
					if($stopAfter100==TRUE){
						echo "<br/><strong>To proceed to the next 100 orders, take the number at the end of the URL in your browser and increase it by 100.</strong><br/>
						Example: example.com/index.php/Bookfeed/load/orders/TRUE/<b>0</b><br/>...changes to: example.com/index.php/Bookfeed/load/orders/TRUE/<b>100</b>
						<br/>...after which changes to: example.com/index.php/Bookfeed/load/orders/TRUE/<b>200</b> (etc)";
					}
					foreach($newAdds as $newOrder){
						echo "<br />".$newOrder;
					}
				}
				if($target=='items'){											//This is purely for testing purposes. Live method is autoLoadItems which will be called in cascading manner from load/orders
					$resourceURLp2="/purchaseorders/PO-2018-15/items?itemsPerPage=400";
					$resourceURLp1="https://acq.sd00.worldcat.org";
					$data=$this->oclcTransmit($resourceURLp1,$resourceURLp2);
					$dataP=json_decode($data);
					$dataP2=$dataP->entry;
					var_dump($dataP2);
				}
				if($target=='copies'){											//This is purely for testing purposes. Live method is autoLoadItems which will be called in cascading manner from load/orders
					$resourceURLp2="/LHR/?q=oclc:889181872";					//Acq-Copy data is almost always outdated; I assume it must be from when copy is recieved. Use LHR instead.
					$resourceURLp1="https://circ.sd00.worldcat.org";
					$data=$this->oclcTransmit($resourceURLp1,$resourceURLp2);
					$dataP=json_decode($data);
					$dataP2=$dataP->entry;
					var_dump($dataP2);
				}
			}
			else{	//Userok 'ok' exists but invalid
				header("Location: ".$baseURL."/index.php/Login/login");
			}
		}
		else{		//Index does not exist in userok
			header("Location: ".$baseURL."/index.php/Login/login");
		}
	}

	public function autoLoadItems($orderNum){							//Could be called via URL but generally this function will only be called internally by above load order function
		$baseURL=$this->newbooksconfig->getBaseURL();
		$userok=$this->session->all_userdata();
		if(array_key_exists('ok', $userok)) {
			if($userok['ok']=='yes'){
				$resourceURLp2="/purchaseorders/".$orderNum."/items?itemsPerPage=400";
				$resourceURLp1="https://acq.sd00.worldcat.org";
				$data=$this->oclcTransmit($resourceURLp1,$resourceURLp2);
				$dataP=json_decode($data);
				echo "<br /><br />New items have been imported from the new order ".$orderNum.": ";
				$dataP2=$dataP->entry;
				$newAdds=array();
				foreach($dataP2 as $orderItem){
					$orderItemNum=$orderItem->orderItemNumber;
					if(count($orderItem->resource->worldcatResource->title)>0){
						$title=$orderItem->resource->worldcatResource->title;
					}
					else{
						$title="";
					}
					if(count($orderItem->resource->worldcatResource->materialType)>0){
						$matType=$orderItem->resource->worldcatResource->materialType;
					}
					else{
						$matType="";
					}
					if(count($orderItem->resource->worldcatResource->author)>0){
						$person1=$orderItem->resource->worldcatResource->author[0];
					}
					else{
						$person1="";
					}
					if(count($orderItem->resource->worldcatResource->oclcNumber)>0){
						$ocn=$orderItem->resource->worldcatResource->oclcNumber;
					}
					else{
						$ocn="";
					}
					$ISBNcount=sizeof($orderItem->resource->worldcatResource->isbn);
					if($ISBNcount>0){												//Seek 13-digit if one exists, otherwise use 10 (Does LibraryThing have a preferred ISBN format?)
						$ISBN13Flag=false;
						for($c=0;$c<$ISBNcount;$c++){
							if(strlen($orderItem->resource->worldcatResource->isbn[$c])==13||substr($orderItem->resource->worldcatResource->isbn[$c],0,3)=='978'){
								$isbn=substr($orderItem->resource->worldcatResource->isbn[$c],0,13);
								$ISBN13Flag=true;
								break;
							}
						}
						if($ISBN13Flag==false){
							$isbn=$orderItem->resource->worldcatResource->isbn[0];
						}
						if(strlen($isbn)>0&&strpos($isbn," ")>0){					//Just in case an ISBN with text sneaks in, just take the number
							$isbn=substr($isbn,0,strpos($isbn," ")-1);
						}
					}
					else{
						$isbn="";
					}
					if(property_exists($orderItem,"copyConfigs")){
						if(count($orderItem->copyConfigs->copyConfig[0]->booking[0]->budgetAccountCode)>0){
							$fund=$orderItem->copyConfigs->copyConfig[0]->booking[0]->budgetAccountCode;			//Revisit if issue arises with multi-copy orders
						}
						else{
							$fund="";
						}
						if(count($orderItem->copyConfigs->copyConfig[0]->receiptStatus)>0){
							$receiptStat=$orderItem->copyConfigs->copyConfig[0]->receiptStatus;						//Revisit if issue arises with multi-copy orders
						}
						else{
							$receiptStat="";
						}
						if(count($orderItem->copyConfigs->copyConfig[0]->orderStatus)>0){
							$orderStat=$orderItem->copyConfigs->copyConfig[0]->orderStatus;							//Revisit if issue arises with multi-copy orders
						}
						else{
							$orderStat="";
						}
					}
					else{
						$fund="";
						$receiptStat="";
						$orderStat="";
					}
					if(count($orderItem->insertTime)>0){
						$orderDateTS=$orderItem->insertTime;							//Revisit if issue arises with multi-copy orders
						$orderDate=date("Y:m:d",$orderDateTS/1000);						//OCLC's timestamp is 13-digit, we need Unix format (in seconds vs milliseconds)
					}
					else{
						$orderDate="";
					}
					$coverURL=$this->googleTransmit($isbn);								//Retrieve cover image from Google Books API
					$msg=$this->Newbooks_model->saveOrderItem($orderNum,$orderItemNum,$title,$matType,$person1,$ocn,$isbn,$fund,$orderStat,$receiptStat,$orderDate,$coverURL);
					if($msg=='ok'){
						array_push($newAdds,$title);
						echo "<br />".$title;
						if($receiptStat=="RECEIVED"){
							$this->autoLoadCopies($ocn);
						}
					}
				}
				return($newAdds);
			}
			else{	//Userok 'ok' exists but invalid
				header("Location: ".$baseURL."/index.php/Login/login");
			}
		}
		else{		//Index does not exist in userok
			header("Location: ".$baseURL."/index.php/Login/login");
		}
	}
	
	public function autoLoadCopies($ocn){										//Could be called from URL but generally this function will only be accessed internally from load order function
		$baseURL=$this->newbooksconfig->getBaseURL();
		$userok=$this->session->all_userdata();
		if(array_key_exists('ok', $userok)) {
			if($userok['ok']=='yes'){
				$resourceURLp2="/LHR/?q=oclc:".$ocn;
				$resourceURLp1="https://circ.sd00.worldcat.org";
				$data=$this->oclcTransmit($resourceURLp1,$resourceURLp2);
				$dataP=json_decode($data);
				$dataP2=$dataP->entry;
				if(array_key_exists(0,$dataP2)){
					for($c=0;$c<count($dataP2);$c++){
						$copyObj=$dataP2[$c];											//Under what circumstances will there be multiple elements in this array? Multiple copies?
						if(count($copyObj->holdingLocation)>0){
							$branch=$copyObj->holdingLocation;
						}
						else{
							$branch="";
						}
						if(count($copyObj->shelvingLocation)>0){
							$location=$copyObj->shelvingLocation;
						}
						else{
							$location="";
						}
						
						/* ------------------------------- */
						if(!empty($copyObj->shelvingDesignation)){
							if(count($copyObj->shelvingDesignation->information)>0){
								$callNum=$copyObj->shelvingDesignation->information;
								foreach($copyObj->shelvingDesignation->itemPart as $cutter){
									$callNum=$callNum." ".$cutter;						//Revisit to implement spacing nuances (Dewey might have different needs)
								}
							}
							else{
								$callNum="";
							}
						}
						else{
							$callNum="";
						}
						if(count($copyObj->holding[0]->pieceDesignation)>0){			//Revisit to ensure that there aren't ever multiple "holdings" children in this type of data
							$barcode=$copyObj->holding[0]->pieceDesignation[0];
						}
						else{
							$barcode="";
						}
						$msg=$this->Newbooks_model->saveCopy($ocn,$branch,$location,$callNum,$barcode);
						echo "<br />".$branch." ".$location." ".$callNum." ".$barcode. " copy loaded";
						if($callNum==""){
							echo " with no call number.";
						}
					}
				}
				else{
					$msg=$this->Newbooks_model->saveCopy($ocn,"","","","");
				}
			}
			else{	//Userok 'ok' exists but invalid
				header("Location: ".$baseURL."/index.php/Login/login");
			}
		}
		else{		//Index does not exist in userok
			header("Location: ".$baseURL."/index.php/Login/login");
		}
	}
	
	/*
	This function is intended to be called manually via URL. It will check on unreceived items and, if cancelled or received, update status accordingly.
	Items found to be received since last check will have copy info retrieved or updated.
	*/
	public function autoUpdateReceived($ph){								//If there are items marked received with no copy attached (rare), enter parameter 'awaitingcopy'
		$baseURL=$this->newbooksconfig->getBaseURL();
		$userok=$this->session->all_userdata();
		if(array_key_exists('ok', $userok)) {
			if($userok['ok']=='yes'){
				$statuteLimitations=$this->newbooksconfig->getStatute();			//Checks in on not received items ordered after date set in config file
				if($ph=='awaitingcopy'){
					$list=$this->Newbooks_model->loadExpecting2($statuteLimitations);
				}
				else{
					$list=$this->Newbooks_model->loadExpecting($statuteLimitations);
				}
				foreach($list as $orderItemNum=>$item){								//Will not account for changed OCN, but autoUpdateBranchE will so will catch such cases when item reaches that error stage
					$resourceURLp2="/purchaseorders/".$item[0]."/items/".$orderItemNum;
					$resourceURLp1="https://acq.sd00.worldcat.org";
					$data=$this->oclcTransmit($resourceURLp1,$resourceURLp2);
					$dataP=json_decode($data);
					if(property_exists($dataP,"copyConfigs")){
						if(count($dataP->copyConfigs->copyConfig>0) && !empty($dataP->copyConfigs->copyConfig[0]->booking) && count($dataP->copyConfigs->copyConfig[0]->booking[0]->budgetAccountCode)>0){
							$fund=$dataP->copyConfigs->copyConfig[0]->booking[0]->budgetAccountCode;	//Revisit if issue arises with multi-copy orders
						}
						else{
							$fund="";
						}
						if(count($dataP->copyConfigs->copyConfig[0]->receiptStatus)>0){
							$receiptStat=$dataP->copyConfigs->copyConfig[0]->receiptStatus;				//Revisit if issue arises with multi-copy orders
						}
						else{
							$receiptStat="";
						}
						if(count($dataP->copyConfigs->copyConfig[0]->orderStatus)>0){
							$orderStat=$dataP->copyConfigs->copyConfig[0]->orderStatus;					//Revisit if issue arises with multi-copy orders
						}
						else{
							$orderStat="";
						}
						$title=$item[2];
						echo "<br /><br />The item ".$title." (".$orderItemNum.") from ".$fund."fund is currently ".$orderStat." and ".$receiptStat;
						
						if($receiptStat=='RECEIVED'){
							echo "______________________ RECEIVING ".$orderItemNum." ________________________";
							$this->autoLoadCopies($item[1]);
							$msg=$this->Newbooks_model->receiveItem($orderItemNum,$orderStat,$fund);
						}
						if($orderStat=='CANCELLED'){
							echo "______________________ MARKING CANCELLED _________________________";
							$msg=$this->Newbooks_model->updateCancelled($orderItemNum);
						}
					}
				}
			}
			else{	//Userok 'ok' exists but invalid
				header("Location: ".$baseURL."/index.php/Login/login");
			}
		}
		else{		//Index does not exist in userok
			header("Location: ".$baseURL."/index.php/Login/login");
		}
	}
	
	/*
	This function is for testing. It simply lists all items in local db with a) no call number, or b) "in processing" as call number to update.
	Use it for when you'd like to see which items would be affected by the autoUpdateCopies method.
	*/
	public function testBranchE($ph){
		$baseURL=$this->newbooksconfig->getBaseURL();
		$userok=$this->session->all_userdata();
		if(array_key_exists('ok', $userok)) {
			if($userok['ok']=='yes'){
				$list=$this->Newbooks_model->loadBranchE();
				var_dump($list);
			}
			else{	//Userok 'ok' exists but invalid
				header("Location: ".$baseURL."/index.php/Login/login");
			}
		}
		else{		//Index does not exist in userok
			header("Location: ".$baseURL."/index.php/Login/login");
		}
	}
	
	/*
	This function is intended to be called manually via URL. It will find any copies in local db that are lacking call numbers
	(or alternatively, have 'in processing' as the call number). Any such copies will be updated/replaced with refreshed copy
	data from OCLC (Collection Management API).
	*/
	public function autoUpdateCopies($ph){	
		$baseURL=$this->newbooksconfig->getBaseURL();
		$userok=$this->session->all_userdata();
		if(array_key_exists('ok', $userok)) {
			if($userok['ok']=='yes'){
				$statuteLimitations=$this->newbooksconfig->getStatute();			//Can also use this function to alternatively handle "in-processing" call numbers if placeholder $ph so specifies
				if($ph=='processing'){
					$list=$this->Newbooks_model->loadCallProc();
				}
				else{
					$list=$this->Newbooks_model->loadBranchE();
				}
				foreach($list as $ocn){
					$orderItemNums=$this->Newbooks_model->getOINfromOCN($ocn,$statuteLimitations);		//Will return a match ONLY if item is marked received (otherwise no point looking for copy info) and ordered after cutoff date
					foreach($orderItemNums as $orderItemNum=>$orderNum){
						$resourceURLp2="/purchaseorders/".$orderNum."/items/".$orderItemNum;
						$resourceURLp1="https://acq.sd00.worldcat.org";
						$data=$this->oclcTransmit($resourceURLp1,$resourceURLp2);
						$dataP=json_decode($data);
						if(count($dataP->resource->worldcatResource->oclcNumber)>0){
							$ocnNew=$dataP->resource->worldcatResource->oclcNumber;
						}
						else{
							$ocnNew="";
						}
						if(count($dataP->resource->worldcatResource->title)>0){
							$title=$dataP->resource->worldcatResource->title;
						}
						else{
							$title="";
						}
						if(count($dataP->resource->worldcatResource->materialType)>0){
							$matType=$dataP->resource->worldcatResource->materialType;
						}
						else{
							$matType="";
						}
						if(count($dataP->resource->worldcatResource->author)>0){
							$person1=$dataP->resource->worldcatResource->author[0];
						}
						else{
							$person1="";
						}
						$ISBNcount=sizeof($dataP->resource->worldcatResource->isbn);
						if($ISBNcount>0){												//Seek 13-digit if one exists, otherwise use 10 (Does LibraryThing have a preferred ISBN format?)
							$ISBN13Flag=false;
							for($c=0;$c<$ISBNcount;$c++){
								if(strlen($dataP->resource->worldcatResource->isbn[$c])==13||substr($dataP->resource->worldcatResource->isbn[$c],0,3)=='978'){
									$isbn=substr($dataP->resource->worldcatResource->isbn[$c],0,13);
									$ISBN13Flag=true;
									break;
								}
							}
							if($ISBN13Flag==false){
								$isbn=$dataP->resource->worldcatResource->isbn[0];
							}
							if(strlen($isbn)>0&&strpos($isbn," ")>0){					//Just in case an ISBN with text sneaks in, just take the number
								$isbn=substr($isbn,0,strpos($isbn," ")-1);
							}
						}
						else{
							$isbn="";
						}
						if(strlen($isbn)>0&&strpos($isbn," ")>0){
							$isbn=substr($isbn,0,strpos($isbn," ")-1);
						}
						echo "<br /><br />Loading ".$orderItemNum.": ".$title;
						$msg=$this->Newbooks_model->deleteCopies($ocn);
						$msg=$this->Newbooks_model->updateItem($orderItemNum,$ocnNew,$title,$matType,$person1,$isbn);
						
						$this->autoLoadCopies($ocnNew);
					}
				}
			}
			else{	//Userok 'ok' exists but invalid
				header("Location: ".$baseURL."/index.php/Login/login");
			}
		}
		else{		//Index does not exist in userok
			header("Location: ".$baseURL."/index.php/Login/login");
		}
	}
	
	/*Use carefully, this is for database maintenance if you think coverURLs from Google Books need a refresh.
	Without modification, it will do so for all your ISBN's.. which will be a lot and probably cause
	you to hit your 1,000-request daily cap with Google Books.
	public function refreshCoverURL($pw){
		if($pw=='LearningDoingSucceeding1884'){
			$isbnArray=$this->Newbooks_model->loadISBNs();
			$coverURLarray=array();
			$counter=0;
			foreach($isbnArray as $orderItemNum=>$isbn){
				echo strval($counter)." of ".sizeof($isbnArray).":<br />";
				echo "ISBN=".$isbn;
				$coverURL=$this->googleTransmit($isbn);
				echo "<br />Func return: ".$coverURL."<br />";
				$coverURLarray[$orderItemNum]=$coverURL;
				$counter++;
			}
			//echo(var_dump($isbnArray));
			echo(var_dump($coverURLarray));
		}
		else{
			echo "Access denied.";
		}
		$msg=$this->Newbooks_model->updateCoverURLs($coverURLarray);
		echo $msg;
	}*/
	
	/*
	Use carefully, this is for database maintenance if you think ISBNs need a refresh.
	Without modification, it will do so for all your ISBN's.. which will be a lot.
	public function refreshISBNs($pw){
		if($pw=='LearningDoingSucceeding1884'){
			//$statuteLimitations=$this->newbooksconfig->getStatute();			//Checks in on not received items ordered after date set in config file
			$list=$this->Newbooks_model->loadOINs();
			echo(var_dump($list));
			$isbnArray=array();
			$counter=1;
			foreach($list as $orderItemNum=>$orderNum){
				$resourceURLp2="/purchaseorders/".$orderNum."/items/".$orderItemNum;
				$resourceURLp1="https://acq.sd00.worldcat.org";
				$data=$this->oclcTransmit($resourceURLp1,$resourceURLp2);
				//echo("<br />URL: ".$resourceURLp1.$resourceURLp2." DATA: ".$data."<br />");
				$orderItem=json_decode($data);
				if(property_exists($orderItem,'resource')){
					$ISBNcount=sizeof($orderItem->resource->worldcatResource->isbn);
					if($ISBNcount>0){												//Seek 13-digit if one exists, otherwise use 10 (Does LibraryThing have a preferred ISBN format?)
						$ISBN13Flag=false;
						for($c=0;$c<$ISBNcount;$c++){
							if(strlen($orderItem->resource->worldcatResource->isbn[$c])==13||substr($orderItem->resource->worldcatResource->isbn[$c],0,3)=='978'){
								$isbn=substr($orderItem->resource->worldcatResource->isbn[$c],0,13);
								$ISBN13Flag=true;
								break;
							}
						}
						if($ISBN13Flag==false){
							$isbn=$orderItem->resource->worldcatResource->isbn[0];
						}
						if(strlen($isbn)>0&&strpos($isbn," ")>0){					//Just in case an ISBN with text sneaks in, just take the number
							$isbn=substr($isbn,0,strpos($isbn," ")-1);
						}
					}
					else{
						$isbn="";
					}
					$isbnArray[$orderItemNum]=$isbn;
					echo "<br />".strval($counter)." of ".sizeof($list).":<br />";
					echo "ORDER I#:".$orderItemNum." ISBN=".$isbn;
				}
				else{
					echo "<br />".strval($counter)." of ".sizeof($list).":<br />";
					echo "ORDER I#:".$orderItemNum." ISBN=ORDER ITEM NOT FOUND";
				}
				$counter++;
			}
			echo "UPDATING DATABASE WITH NEW ISBNs...";
			$msg=$this->Newbooks_model->updateISBNs($isbnArray);
			echo " COMPLETE.".$msg;
		}
	}*/
	
	/*
	This is the method that will make API requests to OCLC. Several lines of code borrowed with gratitude from
	Karen Coombs's authentication library (and so marked in comments).
	*/
	public function oclcTransmit($resourceURLp1,$resourceURLp2){					//Conducts all exchanges with API
		$baseURL=$this->newbooksconfig->getBaseURL();
		$userok=$this->session->all_userdata();
		if(array_key_exists('ok', $userok)) {
			if($userok['ok']=='yes'){
				$resourceURL=$resourceURLp1.$resourceURLp2;
				$keysArr=$this->newbooksconfig->getKeys();
				$secretKey=$keysArr[0];
				$signatureP1=$keysArr[1];													//This is the WSkey
				$time=time();
				$nonce = sprintf("%08x", mt_rand(0, 0x7fffffff));
				//$nonce = $this->makeRandomString();										//If line above fails, switch to this function
				//Now that all ingredients for the signature created, form the signature and hash/encode
				$signatureP2="\n".$time."\n".$nonce."\n\n"."GET\n"."www.oclc.org\n"."443\n"."/wskey\n";
				$parsedUrl = parse_url($resourceURL);
				if(!array_key_exists("query",$parsedUrl)){									//Added this for copies query (getting error for no 'query' index in array)
					$parsedUrl["query"]="";
				}
				else{																		//This else statement is re-used code from Karen Coomb's OCLC authentication library
					$params = array();
					foreach (explode('&', $parsedUrl["query"]) as $pair) {
						list ($key, $value) = explode('=', $pair);
						$params[] = array(
							urldecode($key),
							urldecode($value)
						);
					}
					sort($params);

					foreach ($params as $param) {
						$name = urlencode($param[0]);
						$value = urlencode($param[1]);
						$nameAndValue = "$name=$value";
						$nameAndValue = str_replace("+", "%20", $nameAndValue);
						$nameAndValue = str_replace("*", "%2A", $nameAndValue);
						$nameAndValue = str_replace("%7E", "~", $nameAndValue);
						$signatureP2 .= $nameAndValue . "\n";
					}
				}
				$signature=$signatureP1.$signatureP2;
				$signature=base64_encode(hash_hmac('sha256',$signature,$secretKey,True));
				$principalID=$keysArr[2];
				$principalIDNS=$keysArr[3];
				//Now that signature made, put it in an authorization string intended for HTTPheader
				$authForHeader="Authorization: http://www.worldcat.org/wskey/v2/hmac/v1 clientId=\"".$signatureP1."\", timestamp=\"".$time."\", nonce=\"".$nonce."\", signature=\"".$signature."\", principalID=\"".$principalID."\", principalIDNS=\"".$principalIDNS."\"";
				//Create HTTPheader using above auth string
				$header=array("GET ".$resourceURLp2." HTTP/1.1","Accept: application/json",$authForHeader);
				$ua=$keysArr[4];
				$curl=curl_init($resourceURL);
				curl_setopt($curl,CURLOPT_POST,false);
				curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
				curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
				curl_setopt($curl,CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
				curl_setopt($curl,CURLOPT_USERAGENT,$ua);
				curl_setopt($curl, CURLOPT_VERBOSE, 0);
					//curl_setopt($curl,CURLOPT_VERBOSE, true);								//For debugging
					//$log='templog.txt';
					//$filehandle=fopen($log,'a');
					//curl_setopt($curl,CURLOPT_STDERR,$filehandle);
				$resp=curl_exec($curl);
					//$resperror=curl_error($curl);
				if (curl_errno($curl)) {
					//print_r("Error: ".curl_error($curl).curl_errno($curl)); 
					//print_r("\r\nResponse: ");
					return "Error";
				}
				else { 
					return $resp;
				}
				curl_close($curl);
			}
			else{	//Userok 'ok' exists but invalid
				header("Location: ".$baseURL."/index.php/Login/login");
			}
		}
		else{		//Index does not exist in userok
			header("Location: ".$baseURL."/index.php/Login/login");
		}
	}
	
	public function googleTransmit($isbn){											//Conducts all exchanges with Google Books API
		$baseURL=$this->newbooksconfig->getBaseURL();
		$userok=$this->session->all_userdata();
		if(array_key_exists('ok', $userok)) {
			if($userok['ok']=='yes'){
				$keysArr=$this->newbooksconfig->getKeys();
				$resourceURLp2="/books/v1/volumes?q=isbn:".$isbn."&fields=items/volumeInfo/imageLinks&key=".$keysArr[5];
				$resourceURLp1="https://www.googleapis.com";
				$header=array("GET ".$resourceURLp2." HTTP/1.1","Accept: application/json");
				$ua=$keysArr[4];
				$curl=curl_init($resourceURLp1.$resourceURLp2);
				curl_setopt($curl,CURLOPT_POST,false);
				curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
				curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
				curl_setopt($curl,CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
				curl_setopt($curl,CURLOPT_USERAGENT,$ua);
					//curl_setopt($curl,CURLOPT_VERBOSE, true);								//For debugging
					//$log='templog.txt';
					//$filehandle=fopen($log,'a');
					//curl_setopt($curl,CURLOPT_STDERR,$filehandle);
				$resp=curl_exec($curl);
					//$resperror=curl_error($curl);
				if (curl_errno($curl)) {
					print_r("Error: ".curl_error($curl).curl_errno($curl)); 
					print_r("\r\nResponse: ");
					return "Error ".$resourceURLp1.$resourceURLp2.var_dump($header);
				}
				else {
					$respJSON=json_decode($resp);
					//echo "RESPONSE: ".$resp;
					if(is_Object($respJSON)&&property_exists($respJSON,'items')==TRUE){
						$coverURL=$respJSON->items[0]->volumeInfo->imageLinks->thumbnail;
					}
					else if($respJSON=='{}'){
						$coverURL="";
					}
					else{$coverURL="APIERROR";}
					return $coverURL;
				}
				curl_close($curl);
			}
			else{	//Userok 'ok' exists but invalid
				header("Location: ".$baseURL."/index.php/Login/login");
			}
		}
		else{		//Index does not exist in userok
			header("Location: ".$baseURL."/index.php/Login/login");
		}
	}
	
	
	//If random string generator fails, this function taken from Karen Coombs OCLC authentication library
	/*function makeRandomString($bits = 256) {
		$bytes = ceil($bits / 8);
		$return = '';
		for ($i = 0; $i < $bytes; $i++){
			$return .= (mt_rand(0, 9));
		}
		return $return;
	}*/
}
