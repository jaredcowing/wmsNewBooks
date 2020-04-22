<h1>New books display: Data loading dashboard</h1>
<div class='dash'>
<p>Login accepted. Please enter the desired command as a URL, or click one of the below options.</p>
<p>First time running this program? Try loading 100 orders at a time to see how it goes before trying to load everything at once: <br/><a href='<?php echo $baseURL."/index.php/Bookfeed/load/orders/TRUE/0"?>'>Load first 100 orders</a></p>
<h2>Routine maintenance commands</h2>
<p><a href=<?php echo $baseURL."/index.php/Bookfeed/load/orders"?>>Load <i>all</i> new orders after cutoff date in preferences</a></p>
<p><a href=<?php echo $baseURL."/index.php/Bookfeed/autoUpdateReceived/go"?>>Update received status in loaded orders</a></p>
<p><a href=<?php echo $baseURL."/index.php/Bookfeed/autoUpdateCopies/go"?>>Update call number/location from received & unprocessed items</a></p>
</div>