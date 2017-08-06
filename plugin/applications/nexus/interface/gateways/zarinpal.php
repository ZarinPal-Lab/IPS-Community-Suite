<?php
/*
 * develper hasan ghasminia
 * emial ghasminia@gmail.com
 * phone 09116516722
 * shop http://armital.ir/
 * ips user http://forums.ipbfarsi.ir/profile/3217-%D8%AD%D8%B3%D9%86-%D9%82%D8%A7%D8%B3%D9%85%DB%8C-%D9%86%DB%8C%D8%A7/
 * donate & update linke http://armital.ir/index.php?route=product/product&product_id=65
 * version nexus 4.1.13.2
 */

require_once '../../../../init.php';
\IPS\Session\Front::i();

try
{
	$transaction = \IPS\nexus\Transaction::load( \IPS\Request::i()->nexusTransactionId );
	
	if ( $transaction->status !== \IPS\nexus\Transaction::STATUS_PENDING )
	{
		throw new \OutofRangeException;
	}
}
catch ( \OutOfRangeException $e )
{
	\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=nexus&module=payments&controller=checkout&do=transaction&id=&t=" . \IPS\Request::i()->nexusTransactionId, 'front', 'nexus_checkout', \IPS\Settings::i()->nexus_https ) );
}

try
{
	$res = $transaction->method->api(
		array(
			'Amount' 				=> $transaction->amount->amountAsString() /10,
			'Authority' 			=> \IPS\Request::i()->Authority,
		), TRUE
	);

	if($res['Status'] == 100) {
		$transaction->gw_id = $res['RefID'];
		$transaction->save();
		$transaction->checkFraudRulesAndCapture( NULL );
		$transaction->sendNotification();
		\IPS\Session::i()->setMember( $transaction->invoice->member ); 
		\IPS\Output::i()->redirect( $transaction->url() );
	}
	
	throw new \OutofRangeException;	

}
catch ( \Exception $e )
{
	 \IPS\Output::i()->redirect( $transaction->invoice->checkoutUrl()->setQueryString( array( '_step' => 'checkout_pay', 'err' => $transaction->member->language()->get( 'gateway_err' ) ) ) );

}