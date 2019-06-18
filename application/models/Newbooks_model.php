<?php
class Newbooks_model extends CI_Model{
	public function __construct()
	{
		$this->load->database();
		/* DB is now available through $this->db object */
	}
	
	public function saveOrder($order){
		$newRow=array(
			'orderNum' => $order
		);
		$emptycheck=$this->db->get_where('orders',$newRow);
		$emptycheck=$emptycheck->row_array();
		if($emptycheck==NULL){	//If this order hasn't already been entered
			$this->db->insert('orders',$newRow);
			$returnmsg="ok";
		}
		else{
			$returnmsg="full: ".$order;
		}
		return $returnmsg;
	}
	
	public function saveOrderItem($orderNum,$orderItemNum,$title,$matType,$person1,$ocn,$isbn,$fund,$orderStat,$receiptStat,$orderDate){
		$newRow=array(
			'orderItemNum' => $orderItemNum
		);
		$emptycheck=$this->db->get_where('items',$newRow);
		$emptycheck=$emptycheck->row_array();
		if($emptycheck==NULL){	//If this order item hasn't already been entered
			$newRow['orderNum']=$orderNum;
			$newRow['title']=$title;
			$newRow['matType']=$matType;
			$newRow['person1']=$person1;
			$newRow['ocn']=$ocn;
			$newRow['isbn']=$isbn;
			$newRow['fund']=$fund;
			$newRow['orderStat']=$orderStat;
			$newRow['receiptStat']=$receiptStat;
			$newRow['orderDate']=$orderDate;
			$newRow['coverURL']="https://www.librarything.com/devkey/62679c796d05a02ce762ada59b4d826c/large/isbn/".$isbn;
			$this->db->insert('items',$newRow);
			$returnmsg="ok";
		}
		else{
			$returnmsg="full";
		}
		return $returnmsg;
	}
	
	public function saveCopy($ocn,$branch,$location,$callNum,$barcode){
		$newRow=array(
			'ocn' => $ocn,
			'branch' => $branch,
			'location' => $location,
			'callNum' => $callNum,
			'barcode' => $barcode
		);
		$oldRow=array(
			'ocn' => $ocn,
			'barcode' => $barcode
		);
		$oldRowReplacement=array(
			'ocn' => $ocn,
			'branch' => $branch,
			'location' => $location,
			'callNum' => $callNum,
			'barcode' => $barcode
		);
		$oldRowReplacement['dateAppear']=date('Y-m-d');
		$newRow['dateAppear']=date('Y-m-d');
		$emptycheck=$this->db->get_where('copies',$oldRow);
		$emptycheck=$emptycheck->row_array();
		if($emptycheck==NULL){	//If this copy hasn't already been entered
			$this->db->insert('copies',$newRow);
		}
		else{														//Found the old barcode. I'm using this like a copy-URI, but barcodes do sometimes change so a better solution might be worth pursuit.
			$this->db->set($oldRowReplacement);
			$this->db->where('ocn',$ocn);
			$this->db->where('barcode',$barcode);
			$this->db->update('copies');							//Re-work this for multiple copies
		}
	}
	
	public function deleteCopies($ocn){
		$msg=$this->db->query("DELETE FROM copies WHERE ocn = '".$ocn."';");
	}
	
	public function getOINfromOCN($ocn,$date){
		$data=$this->db->query("SELECT * FROM items WHERE receiptStat = 'received' AND ocn = '".$ocn."' AND orderDate >= '".$date."';");
		$resultsArr=array();
		foreach($data->result() as $result){
			$orderItemNum=$result->orderItemNum;
			$orderNum=$result->orderNum;
			$resultsArr[$orderItemNum]=$orderNum;
		}
		return $resultsArr;
	}
	
	public function loadList($fund){
		if($fund=='all'){
			$data=$this->db->query("SELECT * FROM items WHERE receiptStat = 'RECEIVED';");
		}
		else{
			$data=$this->db->query("SELECT * FROM items WHERE receiptStat = 'RECEIVED' AND fund = '".$fund."';");
		}
		$resultsList=array();
		foreach($data->result() as $result){
			$ocn=$result->ocn;
			$dupFlag=false;	/* The below de-duplication loop was at last check not working, filtering loop currently active in controller */
			foreach($resultsList as $resultMaster){
				if($ocn==$resultMaster[1]){
					$dupFlag=true;
				}
			}
			if($dupFlag==false){
				$copiesList=array();
				$data2=$this->db->get_where('copies',array('ocn' => $ocn));
				foreach($data2->result() as $result2){
					if(strlen($result2->callNum)>0){
						array_push($copiesList,array($result2->branch,$result2->location,$result2->callNum));
					}
				}
				if(sizeOf($copiesList>0)){
					array_push($resultsList,array($result->title,$result->ocn,$result->coverURL,$copiesList,$result->matType));			//Will the uneven dimensions cause problem?
				}
			}
		}
		return $resultsList;
	}
	
	public function loadExpecting($date){
		$data=$this->db->query("SELECT * FROM items WHERE orderStat!='CANCELLED' AND (receiptStat = 'NOT_RECEIVED' OR receiptStat = 'NOT_RECEIV' OR receiptStat = '') AND orderDate >= '".$date."';");
		$resultsArr=array();
		foreach($data->result() as $result){
			$ocn=$result->ocn;
			$orderNum=$result->orderNum;
			$orderItemNum=$result->orderItemNum;
			$resultsArr[$orderItemNum][0]=$orderNum;
			$resultsArr[$orderItemNum][1]=$ocn;
		}
		return $resultsArr;
	}
	
	public function loadBranchE(){
		$data=$this->db->query("SELECT * FROM copies WHERE (branch = '' OR callNum = '');");
		$resultsArr=array();
		foreach($data->result() as $result){
			$ocn=$result->ocn;
			$dupFlag=false;
			for($c=0;$c<count($resultsArr);$c++){
				if($resultsArr[$c]==$ocn){
					$dupFlag=true;
				}
			}
			if($dupFlag==false){
				array_push($resultsArr,$ocn);
			}
		}
		return $resultsArr;
	}
	
	public function loadCallProc(){
		$data=$this->db->query("SELECT * FROM copies WHERE (callNum = 'in processing' OR callNum = 'processing' OR callNum = 'in process');");
		$resultsArr=array();
		foreach($data->result() as $result){
			$ocn=$result->ocn;
			$dupFlag=false;
			for($c=0;$c<count($resultsArr);$c++){
				if($resultsArr[$c]==$ocn){
					$dupFlag=true;
				}
			}
			if($dupFlag==false){
				array_push($resultsArr,$ocn);
			}
		}
		return $resultsArr;
	}
	
	public function loadList2($type,$facet,$date,$ageDeterminant){		//For fund/format and date
		if($date=='ordered'){
			$statuteLimitations=$this->newbooksconfig->getStatute();
			if($type=='subject'){
				if($facet=='All'){
					$data=$this->db->query("SELECT * FROM items WHERE orderStat != 'CANCELLED' AND orderDate >= '".$statuteLimitations."';");
				}
				else{
					$data=$this->db->query("SELECT * FROM items WHERE orderStat != 'CANCELLED' AND orderDate >= '".$statuteLimitations."' AND fund = '".$facet."';");
				}
			}
			else if($type=='format'){
				switch($facet){
					case 'books':
						$sqlstring="matType LIKE 'BOOK%' OR matType='OBJECT'";
					break;
					case 'videos':
						$sqlstring="matType LIKE 'VIDEO%'";
					break;
					default:
						$sqlstring="matType='BOOK'";
				}
				if($ageDeterminant=='order'){
					$data=$this->db->query("SELECT * FROM items WHERE orderStat != 'CANCELLED' AND orderDate >= '".$statuteLimitations."' AND (".$sqlstring.");");
				}
			}
		}
		else{
			$dateInTime=strtotime($date);
			if($type=='subject' && $ageDeterminant=='order'){
				if($facet=='All'){
					$data=$this->db->query("SELECT * FROM items WHERE receiptStat = 'RECEIVED' AND orderDate >= '".$date."';");
				}
				else{
					$data=$this->db->query("SELECT * FROM items WHERE receiptStat = 'RECEIVED' AND orderDate >= '".$date."' AND fund = '".$facet."';");
				}
			}
			else if($type=='subject' && $ageDeterminant=='receipt'){
				if($facet=='All'){
					$data=$this->db->query("SELECT * FROM items WHERE receiptStat = 'RECEIVED';");
				}
				else{
					$data=$this->db->query("SELECT * FROM items WHERE receiptStat = 'RECEIVED' AND fund = '".$facet."';");
				}
			}
			else if($type=='format'){
				switch($facet){
					case 'books':
						$sqlstring="matType LIKE 'BOOK%' OR matType='OBJECT'";
					break;
					case 'videos':
						$sqlstring="matType LIKE 'VIDEO%'";
					break;
					default:
						$sqlstring="matType='BOOK'";
				}
				if($ageDeterminant=='order'){
					$data=$this->db->query("SELECT * FROM items WHERE receiptStat = 'RECEIVED' AND orderDate >= '".$date."' AND (".$sqlstring.");");
				}
				else if($ageDeterminant=='receipt'){
					$data=$this->db->query("SELECT * FROM items WHERE receiptStat = 'RECEIVED' AND (".$sqlstring.");");
				}
				//echo "SELECT * FROM items WHERE receiptStat = 'RECEIVED' AND orderDate >= '".$date."' AND (".$sqlstring.");<br />".var_dump($data);
			}
		}
		$resultsList=array();
		foreach($data->result() as $result){
			$ocn=$result->ocn;
			$copiesList=array();
			$data2=$this->db->get_where('copies',array('ocn' => $ocn));
			$copyIgnore=false;
			foreach($data2->result() as $result2){
				if($date!='ordered'&&strlen($result2->callNum)>0){
					if($ageDeterminant=='order'){
						array_push($copiesList,array($result2->branch,$result2->location,$result2->callNum));
					}
					else if($ageDeterminant=='receipt'&&strtotime($result2->dateAppear)>$dateInTime){
						array_push($copiesList,array($result2->branch,$result2->location,$result2->callNum));
					}
				}
				else if($date=='ordered'&&strlen($result2->callNum)<=0){		//Copies exist but at least one has no call number, so not yet ready
					array_push($copiesList,array($result2->branch," ","On order"));
				}
				else if($date=='ordered'&&strlen($result2->callNum)>=0){
					$copyIgnore=true;											//Alert below logic that copiesList, if empty, is so because item has arrived, not because no copies found
				}
			}
			if(sizeOf($copiesList)>0){
				array_push($resultsList,array($result->title,$result->ocn,$result->coverURL,$copiesList,$result->matType));			//Will the uneven dimensions cause problem?
			}
			else if($date=='ordered'&&$copyIgnore==false){			//No copies exist (may or may not be marked received)
				array_push($resultsList,array($result->title,$result->ocn,$result->coverURL,array(array(" "," ","On order")),$result->matType));
			}
		}
		return $resultsList;
	}
	
	public function receiveItem($orderItemNum,$orderStat,$fund){
		$data=$this->db->query("UPDATE items SET receiptStat= 'RECEIVED' WHERE orderItemNum='".$orderItemNum."'");
		$dateRN=date("Y:m:d");
		if($fund!=""&&$orderStat!=""){
			$data=$this->db->query("UPDATE items SET orderStat= '".$orderStat."', fund='".$fund."' WHERE orderItemNum='".$orderItemNum."'");
		}
		$data=$this->db->query("UPDATE items SET receiptDate= '".$dateRN."' WHERE orderItemNum='".$orderItemNum."'");
		return $data;
	}
	
	public function updateItem($orderItemNum,$ocnNew,$title,$matType,$person1,$isbn){
		$data=$this->db->query("UPDATE items SET ocn='".$ocnNew."', title='".addslashes($title)."', matType='".$matType."', person1='".addslashes($person1)."', isbn='".addslashes($isbn)."' WHERE orderItemNum='".$orderItemNum."'");
		return $data;
	}
	
	public function updateCancelled($orderItemNum){
		$data=$this->db->query("UPDATE items SET orderStat='CANCELLED' WHERE orderItemNum='".$orderItemNum."'");
	}
}