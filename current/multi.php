<!--************************************************************************/
/* multi.php                                                               */
/*   - Example for including two copies of whosonline in one page          */
/*   - Distributed under the GPL terms - see the readme for info           */
/*   - Copyright Brian Porter <beporter@users.sourceforge.net>             */
/*   - Part of HL Who's Online v1.0                                        */
/***************************************************************************/
/* $Id$ */
/*************************************************************************-->
<table border="1" cellspacing="2" cellpadding="4">
	<tr>
		<td valign="top">
			<?php
				// Specify a different server IP to query
				$override['ip'] = "server.csreloaded.com";
				
				// Specify a different server port to use
				$override['port'] = "27015";
				
				// Include Who's Online
				include("whosonline.php"); 
			?>
		</td>
		<td valign="top">
			<?php
				// How about a different server!
				$override['ip'] = "67.18.10.28";
				$override['port'] = "27015";
				
				
				// You can also override what template to use to format
				// the output:
				//$override['template'] = "whosonline-tiny.html";
				
				include("whosonline.php"); 
			?>
		</td>
	</tr>
</table>
<!--**********************************************************************-->