<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bookview extends CI_Controller {
	public function __construct(){
		parent::__construct();
		$this->load->model('Newbooks_model');
		$this->load->helper('url_helper');
		$this->load->library('session');
		$this->load->helper('form');
		$this->load->library('form_validation');
		$this->load->library('newBooksConfig');
	}
	
	public function index()
	{
		$data['age']="";
		$data['fund']="";
		$data['subjDict']=$this->newbooksconfig->getSubjectDict();
		$data['size']='n';
		$this->load->view('templates/header');
		$this->load->view('templates/wumenu',$data);
		$this->load->view('templates/footer');
	}

	public function repeat($age,$facet,$size)
	{
		$facet=urldecode($facet);
		$data['age']=$age;
		$data['fund']=$facet;
		$data['subjDict']=$this->newbooksconfig->getSubjectDict();
		$data['size']=$size;
		if($size!='m'){
			$this->load->view('templates/header');
		}
		else{
			$this->load->view('templates/headerM');
		}
		$this->load->view('templates/wumenu',$data);
		$this->load->view('templates/footer');
	}
	
	public function viewF($fund){				//If no size specified, default to normal
		$this->viewFS($fund,"n");
	}
	
	public function viewFS($fund,$size){
		$fund=urldecode($fund);
		$list=$this->Newbooks_model->loadList($fund);
		$this->displayBookResults(array_push($list,$size));
	}
	
	public function viewFA($fund,$age){			//If no size specified, default to normal
		$this->viewFAS($fund,$age,'n');
	}
	
	public function viewFAS($fund,$age,$size){	//Fund variable may now also contain format, might re-name this
		while(strpos($fund,"%5E")!=FALSE){
			$whereisit=strpos($fund,"%5E");
			$fund=substr($fund,0,$whereisit)."&".substr($fund,$whereisit+6);
		}
		while(strpos($fund,"~~")!=FALSE){
			$whereisit=strpos($fund,"~~");
			$fund=substr($fund,0,$whereisit)."/".substr($fund,$whereisit+2);
		}
		$fund=urldecode($fund);
		$date=date('Y-m-d');
		$dateCutoff="";
		$monthPad=$this->newbooksconfig->getMonthPad();
		$ageDeterminant=$this->newbooksconfig->getAgeDeterminant();
		switch($age){
			case '2Y':
				$dateCutoff=strval(intval(substr($date,0,4))-2).substr($date,4);
				break;
			case '1Y':
				$dateCutoff=strval(intval(substr($date,0,4))-1).substr($date,4);
				break;
			case '6M':
				if(strval(substr($date,5,6))>=(7+$monthPad)){
					$newMonth=strval(intval(substr($date,5,6))-6-$monthPad);
					if($newMonth<10){
						$newMonth="0".$newMonth;
					}
					$dateCutoff=substr($date,0,4)."-".$newMonth.substr($date,7);
				}
				else{
					$newMonth=strval(intval(substr($date,5,6))+(6-$monthPad));
					$dateCutoff=strval(intval(substr($date,0,4))-1)."-".$newMonth.substr($date,7);
				}
				break;
			case '3M':
				if(strval(substr($date,5,6))>=(4+$monthPad)){
					$newMonth=strval(intval(substr($date,5,6))-3-$monthPad);
					if($newMonth<10){
						$newMonth="0".$newMonth;
					}
					$dateCutoff=substr($date,0,4)."-".$newMonth.substr($date,7);
				}
				else{
					$newMonth=strval(intval(substr($date,5,6))+(9-$monthPad));
					$dateCutoff=strval(intval(substr($date,0,4))-1)."-".$newMonth.substr($date,7);
				}
				break;
			case '1M':
				if(strval(substr($date,5,6))>=(2+$monthPad)){
					$newMonth=strval(intval(substr($date,5,6))-(1+$monthPad));
					if($newMonth<10){
						$newMonth="0".$newMonth;
					}
					$dateCutoff=substr($date,0,4)."-".$newMonth.substr($date,7);
				}
				else{
					$newMonth=strval(intval(substr($date,5,6))+(11-$monthPad));
					$dateCutoff=strval(intval(substr($date,0,4))-1)."-".$newMonth.substr($date,7);
				}
				break;
		}
		if(strlen($dateCutoff)>0||$age=='order'){
			if(strpos($fund,"SFormat_")!==false){
				$type='format';
				$facet=substr($fund,8);
			}
			else{
				$type='subject';
				$facet=$fund;
			}
			if($age=='order'){
				$dateCutoff='ordered';
			}
			$list=$this->Newbooks_model->loadList2($type,$facet,$dateCutoff,$ageDeterminant);
			$this->displayBookResults($type,$list,$facet,$dateCutoff,$age,$size);				//Fund and cutoff needed for now in case function needs to call itself again with another fund name. These two parameters can be removed when more efficient multi-fund query created.
		}
	}
	
	public function viewFAT($fund,$age){			//For testing
		$this->viewFAS($fund,$age,'t');
	}
	
	public function branchTranslate($code){
		$branchesArr=$this->newbooksconfig->getBranches();
		foreach($branchesArr as $memCode=>$memBranch){
			if($code==$memCode){
				return $memBranch;
			}
		}
	}
	
	public function displayBookResults($type,$list,$facet,$dateCutoff,$age,$size){
		$baseURL=$this->newbooksconfig->getBaseURL();
		if($size!='m'){
			$this->load->view('templates/header');
		}
		else{
			$this->load->view('templates/headerM');
		}
		echo "<div id='loadingCover' style='width:100%;height:100%;background-color:rgba(255,255,255,.8);position:fixed;z-index:4;'><img class='centerSpin' style='position:relative;width:24px;left:50%;top:50px;' src='/newBooks/images/spinning-wheel.gif'></img></div>";		//Using inline style to ensure it's applied early in the load process
		if($type=='format'){
			$fundPad='SFORMAT_';
		}
		else{
			$fundPad="";
		}
		if(empty($list)){
			echo "<br />There's nothing new to show for this ".$type." & time. Try extending how far back to show new books from.<br /><br />";
			$data['age']=$age;
			$data['fund']=$facet;		//Consider renaming data sent to view since can now contain format
			$data['subjDict']=$this->newbooksconfig->getSubjectDict();
			$data['size']=$size;
			$this->load->view('templates/wumenu',$data);
		}
		else{
			if($type=='subject'){
				$subjDict=$this->newbooksconfig->getSubjectDict();
			}
			echo "<a href='".$baseURL."/index.php/Bookview/repeat/".$age."/".$fundPad.urlencode($facet)."/".$size."'><div id='newBooksBack' role='button' tabindex='0'><img src='https://s3.amazonaws.com/libapps/accounts/83281/images/ic_arrow_back_black_24dp_2x.png' alt='New books search: Go back'></img></div></a>";		//Make this link read from newbooksconfig
			
			echo "<br /><div class='resultsHead'><strong>";
			if($dateCutoff!='ordered'){
				if($facet=='All'){ echo "All newly bought books and videos from the last "; }
				else if($type=='subject'){ echo "New ".$subjDict[$facet]." books and videos bought from the last "; }
				else if($type=='format'){ echo "New ".$facet." bought from the last "; }
				switch($age){
					case '1M':
						echo "1 month";
						break;
					case '3M':
						echo "3 months";
						break;
					case '6M':
						echo "6 months";
						break;
					case '1Y':
						echo "1 year";
						break;
					case '2Y':
						echo "2 years";
						break;
				}
			}
			else if($dateCutoff=='ordered'){
				if($facet=='All'){ echo "All books and videos expected soon"; }
				else if($type=='subject'){ echo "New ".$subjDict[$facet]." books and videos expected soon"; }
				else if($type=='format'){ echo "New ".$facet." expected soon"; }
			}
			echo ":</strong></div><br /><br /><br />";
		}
		$ocns=array();
		$echoString="";
		foreach($list as $result){
			$dupFlag=false;
			$start=0;
			while(strpos($result[0],'?',$start)!=false){		//My database doesn't handle extended unicode characters, so remove any question mark not followed by a space.
				$point=strpos($result[0],'?',$start);
				if(strlen($result[0])>$point+1&&$result[0][$point+1]==' '){
					$start++;
				}
				else{
					if($point>0){
						$result[0]=substr($result[0],0,$point).substr($result[0],$point+1);
					}
					else{
						$result[0]=substr($result[0],$point+1);
					}
					$start=0;
				}
			}
			foreach($ocns as $ocn){
				if($result[1]==$ocn){
					$dupFlag=true;
				}
			}
			if($dupFlag==false){
				array_push($ocns,$result[1]);
				$echoString.="<a href='https://woodbury.on.worldcat.org/oclc/".$result[1]."' target='_blank'><div class='book'>";

				if($result[4]=="BOOK"){
					$echoString.="<img class='format' src='".$baseURL."/images/book.png' alt='book format'></img>";
				}
				else if($result[4]=="VIDEO_DVD"){
					$echoString.="<img class='format' src='".$baseURL."/images/dvd.png' alt='dvd format'></img>";
				}
				else if($result[4]=="VIDEO_BLURAY"){
					$echoString.="<img class='format' src='".$baseURL."/images/dvd.png' alt='bluray format'></img>";
				}
				if(substr($result[2],-5)!="isbn/"){
					//$echoString.="<img class='cover' src='".$result[2]."'></img>";
					if($result[5]!=0){
						if($size!='t'){
							if($result[2]!=""){
								$echoString.="<img class='cover' src='".$result[2]."' alt='".$result[0]." cover image'></img>";
							}
							else{
								$echoString.="<img class='cover' src='http://covers.openlibrary.org/b/isbn/".$result[5]."-M.jpg' alt='".$result[0]." cover image'></img>";
							}
						}
						else{															//Use this space for testing new image loading sources, accessed through viewFAT method
							$coverURL=$this->googleTransmit($result[5]);
							if($coverURL!="{}"){										//If Google returned an image
								$echoString.="<img class='cover' src='".$coverURL."' alt='".$result[0]." cover image'></img>";
							}
							else{
								$echoString.="<img class='cover' src='http://covers.openlibrary.org/b/isbn/".$result[5]."-M.jpg' alt='".$result[0]." cover image'></img>";
							}
						}
					}
				}
				$echoString.="<div class='title'>".$result[0]."</div><br /><div class='details'>";
				foreach($result[3] as $copy){
					$branch=$this->branchTranslate($copy[0]);
					$echoString.="<div class='bloc'><div class='branch'>".$branch."</div><div class='location'>".$copy[1]."</div></div><div class='callNum'>".$copy[2]."</div>";
				}
				$echoString.="</div></div></a>";
			}
		}
		echo $echoString;		
		$this->load->view('templates/footer');
	}
	
	public function googleTransmit($isbn){											//Conducts all exchanges with Google Books API
		$keysArr=$this->newbooksconfig->getKeys();
		if($keysArr[5])!=""{
			$resourceURLp2="/books/v1/volumes?q=isbn:".$isbn."&fields=items/volumeInfo/imageLinks&key=".$keysArr[5];
			$resourceURLp1="https://www.googleapis.com";
			$header=array("GET ".$resourceURLp2." HTTP/1.1","Accept: application/json");
			$ua=$keysArr[4];
			$curl=curl_init($resourceURLp1.$resourceURLp2);
			curl_setopt($curl,CURLOPT_POST,false);
			curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);
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
				if(property_exists($respJSON,'items')==TRUE){
					$coverURL=$respJSON->items[0]->volumeInfo->imageLinks->thumbnail;
				}
				else{
					$coverURL="";
				}
				return $coverURL;
			}
			curl_close($curl);
		}
		else{
			return "";
		}
	}
}
