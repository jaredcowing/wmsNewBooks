<script type='text/javascript' src='https://jaredcowing.com/newBooks/newBooks.js'></script>
Show me print (physical) items the library has bought that are:
<br />
<select id='subjectChooser'>
	<!--<option value='all'>All subjects</option>-->
	<!--<optgroup label='Format'>
	<option value='SFormat_books' <?php if($fund=='SFORMAT_books'){echo "selected";}?>>books</option>
	<option value='SFormat_videos' <?php if($fund=='SFORMAT_videos'){echo "selected";}?>>videos</option>
	</optgroup>-->
	<optgroup label='Subject'>
	<?php
		foreach($subjDict as $fundCode=>$fundName){
			echo "<option value='".$fundCode."'";
			if($fund==$fundCode){
				echo " selected";
			}
			echo ">".$fundName."</option>";
		}
	?>
	</optgroup>
	<option value='All'>Show me everything!</option>
</select>
<br /><br />
...and that have arrived in the library in the past:
<br />
<select id='dateChooser'>
	<!--<option value='1M' <?php if($age=='1M'){echo "selected";}?>>1 month</option>-->
	<option value='3M' <?php if($age=='3M'||($age!='1M'&&$age!='6M'&&$age!='1Y'&&$age!='2Y')){echo "selected";}?>>3 months</option>
	<option value='6M' <?php if($age=='6M'){echo "selected";}?>>6 months</option>
	<option value='1Y' <?php if($age=='1Y'){echo "selected";}?>>1 year</option>
	<!--<option value='2Y' <?php if($age=='2Y'){echo "selected";}?>>2 years</option>-->
	<option value='order' <?php if($age=='order'){echo "selected";}?>>Show me what will be arriving soon!</option>
</select>

<div id='newBooksGo' role='button' tabindex='0'><img src='https://s3.amazonaws.com/libapps/accounts/83281/images/ic_arrow_forward_black_24dp_2x.png' alt='Execute new books search'></img></div>