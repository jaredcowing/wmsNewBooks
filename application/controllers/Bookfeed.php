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
	
	public function oclcTransmit($resourceURLp1,$resourceURLp2){
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
		curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
		curl_setopt($curl,CURLOPT_USERAGENT,$ua);
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
	//If random string generator fails, this function taken from Karen Coombs OCLC authentication library
	/*function makeRandomString($bits = 256) {
		$bytes = ceil($bits / 8);
		$return = '';
		for ($i = 0; $i < $bytes; $i++){
			$return .= (mt_rand(0, 9));
		}
		return $return;
	}*/

	public function index()
	{
		$this->load->view('welcome_message');
	}
	
	public function load($target){													//Load function will request & ingest data from OCLC; orders=load new orders & populate any copies/items therein
		if($target=='orders'){
			$doneFlag=false;
			$startIndex=0;
			$newAdds=array();														//This is just to populate the log afterwards
			$newItems=array();														//This is just to populate the log afterwards
			while($doneFlag==false){
				$resourceURLp2="/purchaseorders?q=SUBMITTED&startIndex=".$startIndex."&itemsPerPage=100";
				$resourceURLp1="https://acq.sd00.worldcat.org";
				$data=$this->oclcTransmit($resourceURLp1,$resourceURLp2);
				$dataP=json_decode($data);
				$dataP2=$dataP->entry;
				$orderCount=0;
				foreach($dataP2 as $order){
					$orderCount++;
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
				if($orderCount<100){
					$doneFlag=true;
				}
				else{
					$startIndex+=100;
				}
			}
			$newNum=count($newAdds);
			echo "<br /><br />Update complete! ".$newNum." new orders added to local database:";
			foreach($newAdds as $newOrder){
				echo "<br />".$newOrder;
			}
		}
		if($target=='items'){							//This is purely for data reference purposes. Live method is autoLoadItems which will be called in cascading manner from load/orders
			$resourceURLp2="/purchaseorders/PO-2018-15/items?itemsPerPage=400";
			$resourceURLp1="https://acq.sd00.worldcat.org";
			$data=$this->oclcTransmit($resourceURLp1,$resourceURLp2);
			$dataP=json_decode($data);
			$dataP2=$dataP->entry;
			var_dump($dataP2);
			//$this->autoLoadItems("PO-2016-7");
		}
		if($target=='copies'){							//This is purely for data reference purposes. Live method is autoLoadItems which will be called in cascading manner from load/orders
			$resourceURLp2="/LHR/?q=oclc:889181872";					//Acq-Copy data is almost always outdated; I assume it must be from when copy is recieved. Use LHR instead.
			$resourceURLp1="https://circ.sd00.worldcat.org";
			$data=$this->oclcTransmit($resourceURLp1,$resourceURLp2);
			$dataP=json_decode($data);
			$dataP2=$dataP->entry;
			var_dump($dataP2);
			//$this->autoLoadCopies("893894553");
		}
	}

	public function autoLoadItems($orderNum){							//Could be called from URL but generally this function will only be utilized by load order function
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
			if(count($orderItem->resource->worldcatResource->isbn)>0){
				$isbn=$orderItem->resource->worldcatResource->isbn[0];			//Revisit this to test all the isbn's for an "ideal match" (does LibraryThing have a preference?)
			}
			else{
				$isbn="";
			}
			if(strlen($isbn)>0&&strpos($isbn," ")>0){
				$isbn=substr($isbn,0,strpos($isbn," ")-1);
			}
			if(property_exists($orderItem,"copyConfigs")){
				if(count($orderItem->copyConfigs->copyConfig[0]->booking[0]->budgetAccountName)>0){
					$fund=$orderItem->copyConfigs->copyConfig[0]->booking[0]->budgetAccountName;			//Revisit if issue arises with multi-copy orders
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
			$msg=$this->Newbooks_model->saveOrderItem($orderNum,$orderItemNum,$title,$matType,$person1,$ocn,$isbn,$fund,$orderStat,$receiptStat,$orderDate);
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
	
	public function autoLoadCopies($ocn){										//Could be called from URL but generally this function will only be accessed from load order function
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
							$callNum=$callNum." ".$cutter;														//Revisit to implement spacing nuances, and maybe Dewey accommodations
						}
					}
					else{
						$callNum="";
					}
				}
				else{
					$callNum="";
				}
				if(count($copyObj->holding[0]->pieceDesignation)>0){											//Revisit to ensure that there aren't ever multiple "holdings" children in this type of data
					$barcode=$copyObj->holding[0]->pieceDesignation[0];
				}
				else{
					$barcode="";
				}
				//echo $branch.$location.$callNum;
				$msg=$this->Newbooks_model->saveCopy($ocn,$branch,$location,$callNum,$barcode);
				echo "<br />".$branch." ".$location." ".$callNum." ".$barcode. "copy loaded";
			}
		}
		else{
			$msg=$this->Newbooks_model->saveCopy($ocn,"","","","");
		}
	}
	
	public function autoUpdateReceived($ph){
		$statuteLimitations=$this->newbooksconfig->getStatute();			//Checks in on not received items ordered after date set in config file
		$list=$this->Newbooks_model->loadExpecting($statuteLimitations);
		foreach($list as $orderItemNum=>$item){
			$resourceURLp2="/purchaseorders/".$item[0]."/items/".$orderItemNum;
			$resourceURLp1="https://acq.sd00.worldcat.org";
			$data=$this->oclcTransmit($resourceURLp1,$resourceURLp2);
			$dataP=json_decode($data);
			if(property_exists($dataP,"copyConfigs")){
				if(count($dataP->copyConfigs->copyConfig>0) && !empty($dataP->copyConfigs->copyConfig[0]->booking) && count($dataP->copyConfigs->copyConfig[0]->booking[0]->budgetAccountName)>0){
					$fund=$dataP->copyConfigs->copyConfig[0]->booking[0]->budgetAccountName;			//Revisit if issue arises with multi-copy orders
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

				echo "<br /><br />The item ".$orderItemNum." from ".$fund."fund is currently ".$orderStat." and ".$receiptStat;
				
				if($receiptStat=='RECEIVED'){
					$this->autoLoadCopies($item[1]);
					$msg=$this->Newbooks_model->receiveItem($orderItemNum,$orderStat,$fund);
					echo "______________________ RECEIVING ".$orderItemNum." ________________________";
				}
				if($orderStat=='CANCELLED'){
					$msg=$this->Newbooks_model->updateCancelled($orderItemNum);
					echo "______________________ MARKING CANCELLED _________________________";
				}
			}
		}
	}
	
	//This function checks in on items with a) no call number, or b) "in processing" as call number to update.
	public function testBranchE($ph){	
		$list=$this->Newbooks_model->loadBranchE();
		var_dump($list);
	}
	
	public function autoUpdateBranchE($ph){	
		$statuteLimitations=$this->newbooksconfig->getStatute();			//Now piggybacking on this function to handle in-processing call numbers if placeholder $ph so specifies
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
				if(count($dataP->resource->worldcatResource->isbn)>0){
					$isbn=$dataP->resource->worldcatResource->isbn[0];					//Revisit this to test all the isbn's for an "ideal match" (does LibraryThing have a preference?)
				}
				else{
					$isbn="";
				}
				if(strlen($isbn)>0&&strpos($isbn," ")>0){
					$isbn=substr($isbn,0,strpos($isbn," ")-1);
				}
				$msg=$this->Newbooks_model->deleteCopies($ocn);
				$msg=$this->Newbooks_model->updateItem($orderItemNum,$ocnNew,$title,$matType,$person1,$isbn);
				$this->autoLoadCopies($ocnNew);
				echo "Loading ".$orderItemNum.": ".$title."<br/>";
			}
		}
	}
}
