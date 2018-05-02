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
}