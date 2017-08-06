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

namespace IPS\nexus\Gateway;


if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}


class _zarinpal extends \IPS\nexus\Gateway
{
	const ZARINPAL_URL = 'https://de.zarinpal.com/pg/services/WebGate/wsdl';

	public function checkValidity( \IPS\nexus\Money $amount, \IPS\GeoLocation $billingAddress = NULL, \IPS\nexus\Customer $customer = NULL  )
	{
		if ($amount->currency != 'IRR')
		{
			return FALSE;
		}
				
		return parent::checkValidity( $amount, $billingAddress );
	}
		

	public function auth( \IPS\nexus\Transaction $transaction, $values, \IPS\nexus\Fraud\MaxMind\Request $maxMind = NULL, $recurrings = array() )
	{
		$transaction->save();

		$data = array(
			'Amount' 	=> $transaction->amount->amountAsString() /10,
			'Description' 	=> $transaction->invoice->title,
			'Email' 	=> $transaction->member->email,
			'Mobile' 	=> $transaction->member->cm_phone,
			'CallbackURL' 	=> (string) \IPS\Settings::i()->base_url . 'applications/nexus/interface/gateways/zarinpal.php?nexusTransactionId=' . $transaction->id
		);


		$res = $this->api($data);

		if($res['Status'] == 100) {
			$settings = json_decode( $this->settings, TRUE );
			\IPS\Output::i()->redirect( \IPS\Http\Url::external( 'https://www.zarinpal.com/pg/StartPay/'.$res['Authority'].($settings['vault']?'/zarinGate':'') ) );
		}
		throw new \RuntimeException;
	}
	public function capture( \IPS\nexus\Transaction $transaction ) {
	}
	public function settings( &$form )
	{
		$settings = json_decode( $this->settings, TRUE );
		$form->add( new \IPS\Helpers\Form\Text( 'zarinpal_client_id', $this->id ?$settings['client_id']:'', TRUE ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'zarinpal_vault', $this->id ?$settings['vault']:'', TRUE ) );
	}
	public function testSettings( $settings )
	{		
		return $settings;
	}
	public function api( $data, $verify = FALSE )
	{
		$settings = json_decode( $this->settings, TRUE );
		$data['MerchantID'] = $settings['client_id'];
		$func = $verify?'PaymentVerification':'PaymentRequest';

		if(class_exists('SoapClient')) {
			$client = new \SoapClient(self::ZARINPAL_URL, array('encoding' => 'UTF-8')); 
			$result = $client->$func($data);
		}else{
			require_once "nusoap.php";
			$client = new \nusoap_client(self::ZARINPAL_URL, 'wsdl'); 
			$client->soap_defencoding = 'UTF-8';
			$result = $client->call($func, array($data));
		}

		return (array) $result;
	}
}