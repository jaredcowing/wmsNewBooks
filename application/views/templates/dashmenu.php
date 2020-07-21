<h1>New books display: Data loading dashboard</h1>
<div class='dash'>
<p>Login accepted. Please enter the desired command as a URL, or click one of the below options.</p>
<p>First time running this program? Try loading 100 orders at a time to see how it goes before trying to load everything at once: <br/><a href='<?php echo $baseURL."/index.php/Bookfeed/load/orders/TRUE/0"?>'>Load first 100 orders</a></p>
<h2>Routine maintenance commands</h2>
<p>Step 1. <a href=<?php echo $baseURL."/index.php/Bookfeed/load/orders"?>>Load all new orders</a><i> (finds any orders not yet in this tool's db, loads them if they were placed after cutoff date in preferences, will try to load copy info too if any yet available)</i></p>
<p>Step 2. <a href=<?php echo $baseURL."/index.php/Bookfeed/updateReceived/default"?>>Update received status for loaded orders</a> <i>(looks for "unreceived" items, checks to see if they were received, if so then updates receipt status and copy-level info)</i></p>
<p><i>Use one or more of the below commands to keep your copy-level info up to date; you may only need one of them to suit your workflow.</i></p>
<p>Step 3a. <a href=<?php echo $baseURL."/index.php/Bookfeed/updateCopies/blankCN"?>>Update copy info for received but unprocessed items [version 1]</a> <i>(looks for copies with blank call number or blank branch, attempts to refresh copy info)</i></p>
<p>Step 3b. <a href=<?php echo $baseURL."/index.php/Bookfeed/updateCopies/processing"?>>Update copy info for received but unprocessed items [version 2]</a> <i>(if your library writes something like "in processing" in the item's call number, this version will try to search for those copies to refresh)</i></p>
<p>Step 3c. <a href=<?php echo $baseURL."/index.php/Bookfeed/updateReceived/awaitingcopy"?>>Load copies for items that are received but missing any copy</a> <i>(this shouldn't be needed often, usually when you receive a bare-bones copy will be made)</i></p>
<br />
<p><i>There are a couple other "off-menu" functions available for database maintenance, and you can always write some of your own or request one be written. Check controllers->Bookfeed.php file for more.</i></p>
</div>