<?php
/**
 * @brief		PayPal Exception
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		10 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Gateway\PayPal;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PayPal Exception
 */
class _Exception extends \DomainException
{
	/**
	 * @brief	Name
	 */
	protected $name;
	
	/**
	 * @brief	Details
	 */
	protected $details = array();
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\Http\Response	$response	Response from PayPal
	 * @param	bool				$refund		request is for a refund?
	 * @return	void
	 */
	public function __construct( \IPS\Http\Response $response, $refund=FALSE )
	{
		\IPS\Log::debug( (string) $response, 'paypal' );
		
		$details = $response->decodeJson();
		$this->name = $details['name'];
		if ( isset( $details['details'] ) )
		{
			$this->details = $details['details'];
		}
		
		switch ( $this->name )
		{				
			case 'EXPIRED_CREDIT_CARD':
				$message = \IPS\Member::loggedIn()->language()->get( 'card_expire_expired' );
				break;
							
			case 'CREDIT_CARD_REFUSED':
				$message = \IPS\Member::loggedIn()->language()->get( 'card_refused' );
				break;
			
			case 'CREDIT_CARD_CVV_CHECK_FAILED':
				$message = \IPS\Member::loggedIn()->language()->get( 'ccv_invalid' );
				break;
				
			case 'REFUND_EXCEEDED_TRANSACTION_AMOUNT':
			case 'FULL_REFUND_NOT_ALLOWED_AFTER_PARTIAL_REFUND':
				$message = \IPS\Member::loggedIn()->language()->get( 'refund_amount_exceeds' );
				break;
				
			case 'REFUND_TIME_LIMIT_EXCEEDED':
				$message = \IPS\Member::loggedIn()->language()->get( 'refund_time_limit' );
				break;
				
			case 'TRANSACTION_ALREADY_REFUNDED':
				$message = \IPS\Member::loggedIn()->language()->get( 'refund_already_processed' );
				break;
			
			case 'ADDRESS_INVALID':
			case 'VALIDATION_ERROR':
				$message = \IPS\Member::loggedIn()->language()->get( 'address_invalid' );
				break;
			
			default:
				if ( $refund )
				{
					$message = \IPS\Member::loggedIn()->language()->get( 'refund_failed' );
				}
				else
				{
					$message = \IPS\Member::loggedIn()->language()->get( 'gateway_err' );
				}
				break;
		}
		
		return parent::__construct( $message, $response->httpResponseCode );
	}
	
	/**
	 * Get Name
	 *
	 * @return	string
	 */
	public function getName()
	{
		return $this->name;
	}
}