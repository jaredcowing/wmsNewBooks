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
		$this->load->library('pagination');
		$this->load->library('newBooksConfig');
	}
	
	/* Load main menu. */
	public function index()
	{
		$data=$this->newbooksconfig->getScriptBrandingURLs();
		$data['age']="";
		$data['fund']="";
		$data['subjDict']=$this->newbooksconfig->getSubjectDict();
		$data['size']='n';
		$data['baseURL']=$this->newbooksconfig->getBaseURL();
		$this->load->view('templates/header',$data);
		$this->load->view('templates/menu',$data);
		$this->load->view('templates/footer');
	}

	/* Load main menu, but remember some settings from a previous search. */
	public function repeat($age,$facet,$size)
	{
		$facet=urldecode($facet);
		$data=$this->newbooksconfig->getScriptBrandingURLs();
		$data['age']=$age;
		$data['fund']=$facet;
		$data['subjDict']=$this->newbooksconfig->getSubjectDict();
		$data['size']=$size;
		$data['baseURL']=$this->newbooksconfig->getBaseURL();
		if($size!='m'){
			$this->load->view('templates/header',$data);
		}
		else{
			$this->load->view('templates/headerM',$data);
		}
		$this->load->view('templates/menu',$data);
		$this->load->view('templates/footer');
	}
	
	/* View books filtered to a fund. */
	public function viewF($fund){				//If no size specified, default to normal
		$this->viewFS($fund,"n");
	}
	
	/* View books filtered to a fund and display size. */
	public function viewFS($fund,$size){
		$fund=urldecode($fund);
		$list=$this->Newbooks_model->loadList($fund);
		if(strpos($fund,"SFormat_")!==false){
			$type='format';
			$facet=substr($fund,8);
		}
		else{
			$type='subject';
			$facet=$fund;
		}
		$dateCutoff="none";
		$age="all";
		$this->displayBookResults($type,$list,$facet,$dateCutoff,$age,$size,0,count($list));
	}
	
	/* View books filtered to a fund and age. */
	public function viewFA($fund,$age){			//Fund, Age; If no size specified, default to normal
		$this->viewFAS($fund,$age,'n');
	}
	
	/* View books filtered to a fund, age and display size. */
	public function viewFAS(){	//Fund, Age, Size, StartPosition; Fund variable may now also contain format, might re-name this
		$args=func_get_args();
		$fund=$args[0];
		$age=$args[1];
		$size=$args[2];
		if(count($args)>3){
			$startPos=$args[3];
		}
		else{$startPos=0;}
		$startPosInt=intval($startPos);
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
			/* De-duplication currently happens later during display, which can throw off the number of items per page
			determined up here.
			If inconsistent number of items per page becomes a bother, de-duplicate up here and see if that additional
			loop has impact on performance with large results.
			*/
			$resultsTotal=count($list);
			if($resultsTotal>30){
				$list=array_slice($list,$startPosInt,30);
			}
			$this->displayBookResults($type,$list,$facet,$dateCutoff,$age,$size,$startPosInt,$resultsTotal);				//Fund and cutoff needed for now in case function needs to call itself again with another fund name. These two parameters can be removed when more efficient multi-fund query created.
		}
	}

	/* View books filtered to a fund, age and in test mode.
	Currently "test mode" will try to load cover images from Google Books in real-time.
	It's much slower than getting those cover URL's ahead of time and stored in MySQL
	(which Bookfeed controller already does).
	*/
	public function viewFAT($fund,$age,$startPos){			//Fund, Age, Testmode
		$startPosInt=intval($startPos);
		$this->viewFAS($fund,$age,'t',$startPosInt);
	}
	
	/* Translate holdings code to the human-readable name of a library branch. */
	public function branchTranslate($code){
		$branchesArr=$this->newbooksconfig->getBranches();
		foreach($branchesArr as $memCode=>$memBranch){
			if($code==$memCode){
				return $memBranch;
			}
		}
	}
	
	/* Once book results have been retrieved, this method will load necessary views to present them to the end user. */
	public function displayBookResults($type,$list,$facet,$dateCutoff,$age,$size,$startPos,$resultsTotal){
		$keysArr=$this->newbooksconfig->getKeys();
		$baseURL=$this->newbooksconfig->getBaseURL();
		$data=$this->newbooksconfig->getScriptBrandingURLs();
		if($size!='m'){
			$this->load->view('templates/header',$data);
		}
		else{
			$this->load->view('templates/headerM',$data);
		}
		echo "<div id='loadingCover' style='width:100%;height:100%;background-color:rgba(255,255,255,.8);position:fixed;z-index:4;'><img class='centerSpin' style='position:relative;width:24px;left:50%;top:50px;' src='" . $baseURL ."/images/spinning-wheel.gif'></img></div>";		//Using inline style to ensure it's applied early in the load process
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
			$data['baseURL']=$baseURL;
			$this->load->view('templates/menu',$data);
		}
		else{
			$catalogURL=$this->newbooksconfig->getCatalogURL();
			if($type=='subject'){
				$subjDict=$this->newbooksconfig->getSubjectDict();
			}
			echo "<a href='".$baseURL."/index.php/Bookview/repeat/".$age."/".$fundPad.urlencode($facet)."/".$size."'><div id='newBooksBack' role='button' tabindex='0'><img src='" . $baseURL ."/images/ic_arrow_back_black_24dp_2x.png' alt='New books search: Go back'></img></div></a>";		//Make this link read from newbooksconfig
			
			echo "<br /><div class='resultsHead'><strong>";
			if($dateCutoff=='none'){
				if($type=='subject'){ echo "New ".$subjDict[$facet]." books and videos "; }
				else if($type=='format'){ echo "New ".$facet." "; }
			}
			else if($dateCutoff!='ordered'){
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
				$echoString.="<a href='".$catalogURL."/oclc/".$result[1]."' target='_blank'><div class='book'>";

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
					if($result[5]!=0){
						if($size!='t'){		//Default method for determining the URL of a book cover.
							if($result[2]!=""){
								$echoString.="<img class='cover' src='".$result[2]."' alt='".$result[0]." cover image'></img>";
							}
							//Currently if no Google Books cover URL on file, videos will try to get an image from Library Thing and books will try OpenLibrary.
							else if($result[4]=='VIDEO_DVD'||$result[4]=='VIDEO_BLURAY'){
								$echoString.="<img class='cover' src='https://www.librarything.com/devkey/".$keysArr[6]."/large/isbn/".$result[5]."' alt='".$result[0]." cover image'></img>";
							}
							else{
								$echoString.="<img class='cover' src='http://covers.openlibrary.org/b/isbn/".$result[5]."-M.jpg' alt='".$result[0]." cover image'></img>";
							}
						}
						else{				//This isn't really a "size," it's triggered when in "testing mode" which forces Google Books cover URL's to be retrieved in real-time. Slow and not recommended.															//Use this space for testing new image loading sources, accessed through viewFAT method
							/*$coverURL=$this->googleTransmit($result[5]);
							if($coverURL!="{}"){										//If Google returned an image
								$echoString.="<img class='cover' src='".$coverURL."' alt='".$result[0]." cover image'></img>";
							}
							else{
								$echoString.="<img class='cover' src='https://www.librarything.com/devkey/".$keysArr[6]."/large/isbn/".$result[5]."' alt='".$result[0]." cover image'></img>";
							}*/
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
		$data['baseURL']=$this->newbooksconfig->getBaseURL();
		$config['base_url']=$data['baseURL'].'/index.php/Bookview/viewFAS/'.$facet.'/'.$age.'/'.$size;
		$config['total_rows']=$resultsTotal;
		$config['per_page']=30;
		$config['full_tag_open'] = "<div class='pag'>";
		$config['full_tag_close'] = '</div>';
		$config['first_tag_open'] = "<div class='pagFirst'>";
		$config['first_tag_close'] = '</div>';
		$config['first_url'] = $data['baseURL'].'/index.php/Bookview/viewFAS/'.$facet.'/'.$age.'/'.$size.'/0';
		$config['prev_tag_open'] = "<div class='pagPrev'>";
		$config['prev_tag_close'] = '</div>';
		$config['cur_tag_open'] = "<div class='pagCur'>";
		$config['cur_tag_close'] = '</div>';
		$config['num_tag_open'] = "<div class='pagNum'>";
		$config['num_tag_close'] = '</div>';
		$config['next_tag_open'] = "<div class='pagNext'>";
		$config['next_tag_close'] = "</div>";
		$config['last_tag_open'] = "<div class='pagLast'>";
		$config['last_tag_close'] = "</div>";
		$this->pagination->initialize($config);
		echo $this->pagination->create_links();
		$this->load->view('templates/footer');
	}
	
	public function googleTransmit($isbn){											//Conducts all exchanges with Google Books API
		$keysArr=$this->newbooksconfig->getKeys();
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
}
