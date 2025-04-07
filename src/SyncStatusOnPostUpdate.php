<?php

namespace WPML\Core;

use WPML\FP\Fns;
use WPML\FP\Obj;
use function WPML\FP\pipe;

class SyncStatusOnPostUpdate {

	/** @var string[]|null */
	private $allowed_post_types;
	/** @var string[]|null */
	private $disallowed_post_types;
	/** @var int[] */
	private $idsToSkip = [];

	public function init_hooks() {
		add_action( 'transition_post_status', [ $this, 'on_post_status_change' ], 10, 3 );
	}

	/**
	 * @return string[]
	 */
	private function set_allowed_post_types() {
		if ( null === $this->allowed_post_types ) {
			$this->allowed_post_types = [];
			if ( defined( "WPML_SYNCHRONIZE_POST_STATUS_POST_TYPES" ) ) {
				if ( is_string( WPML_SYNCHRONIZE_POST_STATUS_POST_TYPES ) ) {
					$this->allowed_post_types =  explode( ',', WPML_SYNCHRONIZE_POST_STATUS_POST_TYPES );
				} elseif ( is_array( WPML_SYNCHRONIZE_POST_STATUS_POST_TYPES ) ) {
					$this->allowed_post_types = WPML_SYNCHRONIZE_POST_STATUS_POST_TYPES;
				}
			}
			$this->allowed_post_types = apply_filters( 'wpml_synchronize_post_status_post_types', $this->allowed_post_types );
		}
		return $this->allowed_post_types;
	}

	/**
	 * @return string[]
	 */
	private function set_disallowed_post_types() {
		if ( null === $this->disallowed_post_types ) {
			$this->disallowed_post_types = apply_filters( 'wpml_synchronize_post_status_post_types_exclude', [] );
		}
		return $this->disallowed_post_types;
	}

	/**
	 * @param string   $new_status
	 * @param string   $old_status
	 * @param \WP_Post $post
	 */
	public function on_post_status_change( $new_status, $old_status, $post ) {
		// Prevent running the logic if the old and new status are the same.
		if ( $new_status === $old_status ) {
			return;
		}

		// Prevent running the logic if the post was inserted anew using the wp_insert_post function:
		// most probably, it does not have a language assigned yet, and translations are created with the same status as their originals.
		if ( 'new' === $old_status ) {
			return;
		}

		// This is a translation, and the original post was already processed.
		if ( in_array( (int) $post->ID, $this->idsToSkip, true ) ) {
			return;
		}

		// Include selected post types, or all, and skip selected post types.
		$this->set_allowed_post_types();
		if ( count( $this->allowed_post_types ) > 0 && ! in_array( $post->post_type, $this->allowed_post_types, true ) ) {
			return;
		}
		$this->set_disallowed_post_types();
		if ( in_array( $post->post_type, $this->disallowed_post_types, true ) ) {
			return;
		}

		$getPostsToUpdate = pipe(
			\WPML\Element\API\PostTranslations::getIfOriginal(),
			Fns::reject( Obj::prop( 'original' ) ),
			Fns::map( Obj::prop( 'element_id' ) )
		);

		// The current post is not the original or has no translations.
		$translationIds = $getPostsToUpdate( $post->ID );
		if ( ! $translationIds ) {
			return;
		}

		$translationIds  = array_map( 'intval', $translationIds );
		$this->idsToSkip = array_merge( $this->idsToSkip, $translationIds );

		// Update the status in the database.
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts}
				SET post_status = %s
				WHERE ID IN  (" . wpml_prepare_in( $translationIds, '%d' ) . ")",
				$new_status
			)
		);

		// Avoid recursion.
		remove_action( 'transition_post_status', [ $this, 'on_post_status_change' ], 10 );
		foreach ( $translationIds as $translationId ) {
			$translationPost = get_post( $translationId );
			if ( $translationPost ) {
				// Trigger the actions related to the transitioning of a post's status.
				wp_transition_post_status( $new_status, $old_status, $translationPost );
			}
		}
		add_action( 'transition_post_status', [ $this, 'on_post_status_change' ], 10, 3 );
	}
}
