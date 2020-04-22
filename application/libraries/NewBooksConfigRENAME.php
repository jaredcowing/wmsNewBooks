<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class NewBooksConfig {
        public function getKeys()
        {
			$secretKey="";				//Place your OCLC shared secret key here
			$wsKey="";					//Place your OCLC WSkey here
			$principalID="";
			$principalIDNS="";
			$userAgent="";				//Place your user agent here
			$googleBooksKey="";			//Optional, leave blank if not using Google Books covers
			$libraryThingKey="";		//Optional, leave blank if not using LibraryThing covers
			return array($secretKey,$wsKey,$principalID,$principalIDNS,$userAgent,$googleBooksKey,$libraryThingKey);
        }
		
		public function getBaseURL(){
			return "https://yoursite.com/newBooks";		//The location of your install, without a slash at end.
		}
		
		public function getCatalogURL(){
			return "https://woodbury.on.worldcat.org";	//The location of your catalog, without a slash at end.
		}
		
		public function getScriptBrandingURLs(){
			$baseURL=$this->getBaseURL();
			return array(
				'favicon'=>'https://libapps.s3.amazonaws.com/accounts/83281/images/sealtransp.png',	//URL of the favicon you want to use
				'cssRegular'=>$baseURL.'/newBooks.css',	//filename of your CSS script (leave alone unless you changed the name)
				'cssMobile'=>$baseURL.'/newBooksM.css',	//filename of your mobile CSS script (leave alone unless you changed the name)
				'jquery'=>'https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js',	//URL of jquery you want to use
				'javascript'=>$baseURL.'/newBooks.js'	//URL of your javascript (leave alone unless you changed the name)
			);
		}
		
		/*List your library branches below. The holdings code is the array key,
		and the branch's name is the value. Remember to add a comma after
		each line except the last if you intend on adding more than 2 lines.
		*/
		public function getBranches(){
			$branchesArr=array(
			"OMBL"=>"Burbank",
			"OMBS"=>"San Diego"
			);
			return $branchesArr;
		}
		
		public function getStatute(){
			return "2018-07-01";		//Books ordered before this date won't continually be checked in on ("we give up, that book isn't ever coming" or "that record may be a fluke/data entry error")
		}
		
		public function getSubjectDict(){	//Place your fund codes and fund names here
			return array(
				'ANIMB'=>'Animation',
				'ANTHB'=>'Anthropology',
				'METEB'=>'Applied Computer Science',
				'ARCHB'=>'Architecture (Burbank)',
				'ARCHSD'=>'Architecture (San Diego)',
				'BUMAB'=>'Business Management/Marketing/Accounting',
				'COMMB'=>'Communication',
				'DESFB'=>'Design Foundation',
				'FASHB'=>'Fashion Design',
				'FILMB'=>'Filmmaking',
				'FAAHB'=>'Fine Arts & Art History',
				'GAMEB'=>'Game Art & Design',
				'GRAPB'=>'Graphic Design',
				'HISTB'=>'History',
				'INDSB'=>'Interdisciplinary Studies',
				'INTAB'=>'Interior Architecture',
				'LITWB'=>'Literature & Writing',
				'PHILB'=>'Philosophy',
				'POLIB'=>'Political Science',
				'POPUB'=>'Popular/Leisure (Burbank)',
				'POPUSB'=>'Popular/Leisure (San Diego)',
				'PSADB'=>'Public Safety Administration',
				'PSYCB'=>'Psychology',
				'SCMAB'=>'Science & Math',
				'URBAB'=>'Urban Studies'
			);
		}
		
		/*You can determine a books "made available" date either by:
		1) using the WMS Acquisitions order date (and adding some padding time for shippping & processing)
		2) using the date the application first noticed an item record was created.
		Note: Don't start using option #2 until after your application has been running for a few months;
		the first time you load orders, all items received by that time will get 'dateAppear' set to that day (old items will appear to be new).
		*/
		public function getAgeDeterminant(){																
			return 'order';		//For option #1 described above, set this string to 'order'; for option #2, set to 'receipt'.
		}
		
		/*If you chose 'order' (option #1) for determining date of availability, enter here the number of months it typically takes from order to shelf-ready.
		If you chose 'receipt,' set this number to 0 (zero)*/
		public function getMonthPad(){
			return 3;
		}
		
		/* Set the username and password to authenticate before executing any data loading commands. */
		public function getUNPW(){
			return array(
				'UN'=>'test',
				'PW'=>'test123'
			);
		}
		
}