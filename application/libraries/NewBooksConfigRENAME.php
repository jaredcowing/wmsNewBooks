<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class NewBooksConfig {

        public function getKeys()
        {
			$secretKey="";															//Place your OCLC shared secret key here
			$wsKey="";		//Place your OCLC WSkey here
			$principalID="";
			$principalIDNS="";
			$userAgent="";										//Place your user agent here
			return array($secretKey,$wsKey,$principalID,$principalIDNS,$userAgent);
        }
		
		public function getBranches(){
			$branchesArr=array(																				//List your library branches here. The holdings code is the array key,
			"OMBL"=>"Burbank",																				//and the branch's name is the value. Remember to add a comma after
			"OMBS"=>"San Diego"																				//all but the last branch if you intend on adding more than 2 lines.
			);
			return $branchesArr;
		}
		
		public function getStatute(){
			return "2018-07-01";																			//Books ordered before this date won't continually be checked in on ("we give up, that book isn't ever coming" or "that record may be a data entry error")
		}
		
		public function getSubjectDict(){						//Place your fund codes and fund names here
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
		
		public function getMonthPad(){
			return 3;																						//If you will determine "availability date" of books by their order date, enter here the number of months it typically takes from order to shelf-ready. SET TO ZERO IF YOU WILL BE USING RECEIPT DATE INSTEAD. This helps the application to estimate an "arrival date" (ready date) from patron's perspective.
		}
		
		public function getAgeDeterminant(){																
			return 'order';																					//Set string to 'order' if you would like availability dates to be estimated based on their order date.
																											//Set string to 'receipt' if you would like application to use the date it first noticed an item record was created. Don't start using 'receipt' until after your application has been running for a few months; the first time you load orders, all items received by that time will get 'dateAppear' set to that day (old items will appear to be new).
		}
}