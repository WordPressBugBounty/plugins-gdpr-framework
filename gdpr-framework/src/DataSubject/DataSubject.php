<?php

namespace Codelight\GDPR\DataSubject;

use Codelight\GDPR\Components\Consent\ConsentManager;

/**
 * A data subject is any person whose data we are storing.
 * A data subject might or might not have a user account on this site.
 *
 * Class DataSubject
 *
 * @package Codelight\GDPR\DataSubject
 */
class DataSubject {

	/* @var string */
	protected $email;

	/* @var \WP_User|null */
	protected $user = null;

	/* @var ConsentManager */
	protected $consentManager;

	/* @var array */
	protected $consents = array();

	protected $dataRepository;

	/**
	 * DataSubject constructor.
	 *
	 * @param                $email
	 * @param \WP_User|null  $user
	 * @param ConsentManager $consentManager
	 */
	public function __construct( $email, $user, ConsentManager $consentManager ) {
		// Fix: `\WP_User $user = null` ahead of a required parameter is an
		// implicitly nullable type, deprecated in PHP 8.4. The PHP 7.1+
		// `?\WP_User` syntax would break PHP 5.6, so the type is enforced
		// here instead. Every caller already passes all three arguments.
		$this->email          = $email;
		$this->user           = $user instanceof \WP_User ? $user : null;
		$this->consentManager = $consentManager;
		$this->dataRepository = new DataRepository( $email );

		$this->consents = $this->consentManager->getAllConsents( $this->email );
	}

	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * @return bool
	 */
	public function hasUser() {
		 return $this->getUser() ? true : false;
	}

	/**
	 * @return null|\WP_User
	 */
	public function getUser() {
		 return $this->user;
	}

	/**
	 * @return int|null
	 */
	public function getUserId() {
		if ( $this->hasUser() ) {
			return $this->getUser()->ID;
		}

		return null;
	}

	  /**
	   * @delete logs
	   */
	public function gdpr_delete_log( $id ) {
		return $this->consentManager->gdpr_delete_log( $id );
	}

	/**
	 * Check if the data subject has consented to something specific
	 */
	public function hasConsented( $consent ) {
		// Fix: $this->consents holds the wp_gdpr_consent rows returned by
		// UserConsentModel::getAll() (objects; arrays on PHP < 7), not
		// consent slugs, so in_array( $consent, $this->consents ) never
		// matched and this always returned false -- the comment form asked
		// logged-in users for privacy-policy consent on every comment.
		// Match each row's slug, and treat a consent past its valid_until
		// date as not given (zero dates on legacy rows mean no expiry).
		$now = current_time( 'mysql' );

		foreach ( (array) $this->consents as $row ) {
			$row = (array) $row;
			if ( ! isset( $row['consent'] ) || $row['consent'] !== $consent ) {
				continue;
			}

			$validUntil = isset( $row['valid_until'] ) ? (string) $row['valid_until'] : '';
			if ( '' !== $validUntil && 0 !== strpos( $validUntil, '0000-00-00' ) && $validUntil < $now ) {
				continue;
			}

			return true;
		}

		return false;
	}

	/**
	 * Get all consent given by the data subject
	 */
	public function getConsents() {
		 return $this->consents;
	}

	/**
	 * Get a list of all consents intersected with the data subjects consents
	 */
	public function getConsentData() {
		return $this->consentManager->getConsentData( $this->consents );
	}

	/**
	 * Get a list of user logs
	 */
	public function getuserlogsData() {
		 return $this->consentManager->getuserlogsData( $this->email );
	}
	/**
	 * Get a list of all visible consents
	 */
	public function getVisibleConsentData() {
		if ( $this->getConsentData() ) {

			return array_filter(
				$this->getConsentData(),
				function ( $item ) {
					return $item['visible'];
				}
			);

		}
		return array();
	}

	/**
	 * Save given consent to data subject
	 *
	 * @param $consent
	 */
	public function giveConsent( $consent, $valid_until = null ) {
		$this->consentManager->giveConsent( $this->email, $consent, $valid_until );
	}

	/**
	 * Remove given consent from data subject
	 *
	 * @param $consent
	 */
	public function withdrawConsent( $consent ) {
		$this->consentManager->withdrawConsent( $this->email, $consent );
	}

	/**
	 * Check if there's any data stored about this data subject at all
	 */
	public function hasData() {
		 return ! empty( $this->getData() );
	}

	/**
	 * Just get the data subjects data, without sending emails or anything.
	 * Applies 'gdpr/data-subject/data' filter.
	 *
	 * @return array
	 */
	public function getData() {
		 return $this->dataRepository->getData( $this->getEmail() );
	}

	/**
	 * Export the data subject's data as per admin configuration.
	 * Applies 'gdpr/data-subject/data' filter.
	 *
	 * @return array|null
	 */
	public function export( $format, $force = false ) {
		 return $this->dataRepository->export( $this->getEmail(), $format, $force );
	}

	/**
	 * Forget the data subject as per admin configuration.
	 * Triggers 'gdpr/data-subject/anonymize' or 'gdpr/data-subject/delete' action.
	 *
	 * @return bool
	 */
	public function forget( $forceAction = null ) {
		 return $this->dataRepository->forget( $this->getEmail(), $forceAction );
	}
}
