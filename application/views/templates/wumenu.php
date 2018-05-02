<script type='text/javascript' src='https://jaredcowing.com/newBooks/newBooks.js'></script>
Show me books the library acquired for this subject:
<br />
<select id='subjectChooser'>
	<!--<option value='all'>All subjects</option>-->
	<option value='All'>All subjects</option>
	<option value='Accounting & Finance' <?php if($fund=='Accounting & Finance'){echo "selected";}?>>Accounting & Finance</option>
	<option value='Animation' <?php if($fund=='Animation'){echo "selected";}?>>Animation</option>
	<option value='Anthropology' <?php if($fund=='Anthropology'){echo "selected";}?>>Anthropology</option>
	<option value='Applied Computer Science' <?php if($fund=='Applied Computer Science'){echo "selected";}?>>Applied Computer Science</option>
	<option value='Architecture-Burbank' <?php if($fund=='Architecture-Burbank'){echo "selected";}?>>Architecture (Burbank)</option>
	<option value='Architecture-SD' <?php if($fund=='Architecture-San Diego'){echo "selected";}?>>Architecture (San Diego)</option>
	<option value='Business & Management' <?php if($fund=='Business & Management'){echo "selected";}?>>Business Management</option>
	<option value='Communication' <?php if($fund=='Communication'){echo "selected";}?>>Communication</option>
	<option value='Design Foundation' <?php if($fund=='Design Foundation'){echo "selected";}?>>Design Foundation</option>
	<option value='Fashion Design' <?php if($fund=='Fashion Design'){echo "selected";}?>>Fashion Design</option>
	<option value='Filmmaking' <?php if($fund=='Filmmaking'){echo "selected";}?>>Filmmaking</option>
	<option value='Fine Arts & Art History' <?php if($fund=='Fine Arts & Art History'){echo "selected";}?>>Fine Arts & Art History</option>
	<option value='Game Art & Design' <?php if($fund=='Game Art & Design'){echo "selected";}?>>Game Art & Design</option>
	<option value='Graphic Design' <?php if($fund=='Graphic Design'){echo "selected";}?>>Graphic Design</option>
	<option value='History & Political Science' <?php if($fund=='History & Political Science'){echo "selected";}?>>History & Political Science</option>
	<option value='Interdisciplinary Studies' <?php if($fund=='Interdisciplinary Studies'){echo "selected";}?>>Interdisciplinary Studies</option>
	<option value='Interior Architecture' <?php if($fund=='Interior Architecture'){echo "selected";}?>>Interior Architecture</option>
	<option value='Literature/Writing' <?php if($fund=='Literature/Writing'){echo "selected";}?>>Literature & Writing</option>
	<option value='Marketing & Fashion Marketing' <?php if($fund=='Marketing & Fashion Marketing'){echo "selected";}?>>Marketing & Fashion Marketing</option>
	<option value='Philosophy' <?php if($fund=='Philosophy'){echo "selected";}?>>Philosophy</option>
	<option value='Popular-Burbank' <?php if($fund=='Popular-Burbank'){echo "selected";}?>>Popular/Leisure (Burbank)</option>
	<option value='Popular-San Diego' <?php if($fund=='Popular-San Diego'){echo "selected";}?>>Popular/Leisure (San Diego)</option>
	<option value='Psychology' <?php if($fund=='Psychology'){echo "selected";}?>>Psychology</option>
	<option value='Science & Math' <?php if($fund=='Science & Math'){echo "selected";}?>>Science & Math</option>
	<option value='Urban Studies' <?php if($fund=='Urban Studies'){echo "selected";}?>>Urban Studies</option>
</select>
<br /><br />
Show me books and DVDs that have arrived in the library in the past:
<br />
<select id='dateChooser'>
	<option value='1M' <?php if($age=='1M'){echo "selected";}?>>1 month</option>
	<option value='3M' <?php if($age=='3M'){echo "selected";}?>>3 months</option>
	<option value='6M' <?php if($age=='6M'){echo "selected";}?>>6 months</option>
	<option value='1Y' <?php if($age=='1Y'){echo "selected";}?>>1 year</option>
	<option value='2Y' <?php if($age=='2Y'){echo "selected";}?>>2 years</option>
</select>

<div id='newBooksGo'><img src='https://s3.amazonaws.com/libapps/accounts/83281/images/ic_arrow_forward_black_24dp_2x.png' alt='Execute new books search'></img></div>