<?php
/*
+--------------------------------------------------------------------+
| Added PHP script dgwConvContr.php                                  |
+--------------------------------------------------------------------+
| Project       :       Implementation at De Goede Woning            |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       3 June 2011                                  |
| Description   :       Corrigeren adres        					 |
+--------------------------------------------------------------------+
*/
ini_set( 'display_startup_errors', '0' );
ini_set( 'display_errors', '1' );
@date_default_timezone_set( 'Europe/Amsterdam' );
set_time_limit(0);
/*
 * laad configuratiebestand
 */
require_once( '/var/www/intranet/sites/all/modules/dgwphp/dgwConvConfig.php' );
/*
 * lezen alle records uit adrrefno1
 */
$qry1 = "SELECT * FROM adrrefno1";
$daoRef = CRM_Core_DAO::executeQuery( $qry1 );
while ( $daoRef->fetch( ) ) {
	$start = date("Y-m-d", strtotime( $daoRef->start_date ) );
	$end = null;
	if ( !empty( $daoRef->end_date) ) {
		$end = date("Y-m-d", strtotime($daoRef->end_date));
	}
	$par = $daoRef->par_refno;
	$adr = $daoRef->adr_refno;
	$insQry = "INSERT INTO adrrefno SET start_date = '$start', end_date = '$end',
		par_refno = '$par', adr_refno = '$adr'";
	CRM_Core_DAO::executeQuery( $insQry );
}
echo "<p>Corrigeren datum adrrefno klaar!</p>";
?>
