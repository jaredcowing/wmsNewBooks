<div id="header">New books display: Login</div>
<?php echo validation_errors(); ?>
<div class='ci_element_wrapper'>
<?php echo form_open('Login/auth'); ?>
<br />
<label for="username">Please enter your username below:</label>
<br />
<input type="input" name="username" /> <br />
<br />
<label for="password">Please enter your password below:</label>
<br />
<input type="password" name="password"></textarea><br />
<br />
<input type="submit" name="submit" value="Login" />
</form>
</div>



