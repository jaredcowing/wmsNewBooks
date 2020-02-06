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
			return "https://yoursite.com/newBooks";	//The location of your install, without a slash at end.
		}
		
		public function getBranches(){
			$branchesArr=array(			//List your library branches here. The holdings code is the array key,
			"OMBL"=>"Burbank",			//and the branch's name is the value. Remember to add a comma after
			"OMBS"=>"San Diego"			//all but the last branch if you intend on adding more than 2 lines.
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
		
}