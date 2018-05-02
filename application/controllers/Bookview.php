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
		$data['age']='';
		$data['fund']='';
		$this->load->view('templates/header');
		$this->load->view('templates/wumenu',$data);
		$this->load->view('templates/footer');
	}
	
	public function viewF($fund){
		$fund=urldecode($fund);
		$list=$this->Newbooks_model->loadList($fund);
		$this->displayBookResults($list);
	}
	
	public function viewFA($fund,$age){
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
		switch($age){
			case '2Y':
				$dateCutoff=strval(intval(substr($date,0,4))-2).substr($date,4);
				break;
			case '1Y':
				$dateCutoff=strval(intval(substr($date,0,4))-1).substr($date,4);
				break;
			case '6M':
				if(strval(substr($date,5,6))>=10){
					$newMonth=strval(intval(substr($date,5,6))-9);			//Changing to 9 so that there's some processing lead time; revert to 6 if using "data available" vs "date ordered"
					if($newMonth<10){
						$newMonth="0".$newMonth;
					}
					$dateCutoff=substr($date,0,4)."-".$newMonth.substr($date,7);
				}
				else{
					$newMonth=strval(intval(substr($date,5,6))+3);			//See above note, but this number is inverted to going to previous year
					$dateCutoff=strval(intval(substr($date,0,4))-1)."-".$newMonth.substr($date,7);
				}
				break;
			case '3M':
				if(strval(substr($date,5,6))>=7){
					$newMonth=strval(intval(substr($date,5,6))-6);			//See above
					if($newMonth<10){
						$newMonth="0".$newMonth;
					}
					$dateCutoff=substr($date,0,4)."-".$newMonth.substr($date,7);
				}
				else{
					$newMonth=strval(intval(substr($date,5,6))+6);			//See above
					$dateCutoff=strval(intval(substr($date,0,4))-1)."-".$newMonth.substr($date,7);
				}
				break;
			case '1M':
				if(strval(substr($date,5,6))>=4){
					$newMonth=strval(intval(substr($date,5,6))-3);			//See above
					if($newMonth<10){
						$newMonth="0".$newMonth;
					}
					$dateCutoff=substr($date,0,4)."-".$newMonth.substr($date,7);
				}
				else{
					$newMonth=strval(intval(substr($date,5,6))+9);			//See above
					$dateCutoff=strval(intval(substr($date,0,4))-1)."-".$newMonth.substr($date,7);
				}
				break;
		}
		if(strlen($dateCutoff)>0){
			$list=$this->Newbooks_model->loadList2($fund,$dateCutoff);
			if($fund=="Applied Computer Science"){										//Find a better way to handle changed fund names or multiple-fund queries. Using fund code might help with the former.
				$list2=$this->Newbooks_model->loadList2("Media Technology",$dateCutoff);
				$list=array_merge($list,$list2);
			}
			$this->displayBookResults($list,$fund,$dateCutoff,$age);					//Fund and cutoff needed for now in case function needs to call itself again with another fund name. These two parameters can be removed when more efficient multi-fund query created.
		}
	}
	
	public function branchTranslate($code){												//Extensible for other libraries, pulls from newbooksconfig file
		$branchesArr=$this->newbooksconfig->getBranches();
		foreach($branchesArr as $memCode=>$memBranch){
			if($code==$memCode){
				return $memBranch;
			}
		}
	}
	
	public function displayBookResults($list,$fund,$dateCutoff,$age){
		$this->load->view('templates/header');
		if(empty($list)){
			echo "<br />There's nothing new to show for this subject & time. Try extending how far back to show new books from.<br /><br />";
			$data['age']=$age;
			$data['fund']=$fund;
			$this->load->view('templates/wumenu',$data);
		}
		else{
			echo "<a href='https://jaredcowing.com/newBooks/index.php/Bookview'><div id='newBooksBack'><img src='https://s3.amazonaws.com/libapps/accounts/83281/images/ic_arrow_back_black_24dp_2x.png' alt='New books search: Go back'></img></div></a>";		//Make this link read from newbooksconfig
			
			echo "<br /><div class='resultsHead'><strong>";
			if($fund=='All'){ echo "All newly bought books and videos from the last "; }
			else{ echo "New ".$fund." books and videos bought from the last "; }
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
			echo ":</strong></div><br />";

		}
		$ocns=array();
		foreach($list as $result){
			$dupFlag=false;
			foreach($ocns as $ocn){
				if($result[1]==$ocn){
					$dupFlag=true;
				}
			}
			if($dupFlag==false){
				array_push($ocns,$result[1]);
				echo "<a href='https://woodbury.on.worldcat.org/oclc/".$result[1]."' target='_blank'><div class='book'>";

				if($result[4]=="BOOK"){
					echo "<img class='format' src='https://jaredcowing.com/newBooks/images/book.png' alt='book format'></img>";
				}
				else if($result[4]=="VIDEO_DVD"){
					echo "<img class='format' src='https://jaredcowing.com/newBooks/images/dvd.png' alt='dvd format'></img>";
				}
				else if($result[4]=="VIDEO_BLURAY"){
					echo "<img class='format' src='https://jaredcowing.com/newBooks/images/dvd.png' alt='bluray format'></img>";
				}
				if(substr($result[2],-5)!="isbn/"){
					echo "<img class='cover' src='".$result[2]."'></img>";
				}
				echo "<div class='title'>".$result[0]."</div><br /><div class='details'>";
				foreach($result[3] as $copy){
					$branch=$this->branchTranslate($copy[0]);
					echo "<div class='bloc'><div class='branch'>".$branch."</div><div class='location'>".$copy[1]."</div></div><div class='callNum'>".$copy[2]."</div>";
				}
				echo "</div></div></a>";
			}
		}	
		$this->load->view('templates/footer');
	}
}
