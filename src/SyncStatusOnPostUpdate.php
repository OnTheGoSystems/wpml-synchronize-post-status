<?php

namespace WPML\Core;

use WPML\FP\Fns;
use WPML\FP\Obj;
use function WPML\FP\pipe;

class SyncStatusOnPostUpdate {
	private $allowed_post_types;

	/**
	 * SyncStatusOnPostUpdate constructor.
	 */
	public function __construct() {
		$this->allowed_post_types = [];
		if ( defined( "WPML_SYNCHRONIZE_POST_STATUS_POST_TYPES" ) ) {
			if ( is_string( WPML_SYNCHRONIZE_POST_STATUS_POST_TYPES ) ) {
				$this->allowed_post_types =  explode( ',', WPML_SYNCHRONIZE_POST_STATUS_POST_TYPES );
			} else {
				$this->allowed_post_types = WPML_SYNCHRONIZE_POST_STATUS_POST_TYPES;
			}
		}
		$this->allowed_post_types = apply_filters( 'wpml_synchronize_post_status_post_types', $this->allowed_post_types );
	}

	public function init_hooks() {
		add_action( 'transition_post_status', [ $this, 'on_post_status_change' ], 10, 3 );
	}

	/**
	 * @param string   $new_status
	 * @param string   $old_status
	 * @param \WP_Post $post
	 */
	public function on_post_status_change( $new_status, $old_status, $post ) {
		$getPostsToUpdate = pipe(
			\WPML\Element\API\PostTranslations::getIfOriginal(),
			Fns::reject( Obj::prop( 'original' ) ),
			Fns::map( Obj::prop( 'element_id' ) )
		);

		// Prevent running logic if the old and new status are the same
		if ( $new_status == $old_status ) {
			return;
		}

		// The current post is not the original or has no translations
		if ( ! $getPostsToUpdate( $post->ID ) ) {
			return;
		}

		if ( count( $this->allowed_post_types ) > 0 && ! in_array( $post->post_type, $this->allowed_post_types, true ) ) {
			return;
		}

		global $sitepress;

		$element_type = 'post_' . $post->post_type;
		if ( $post->ID == $sitepress->get_original_element_id( $post->ID, $element_type ) ) {
			$trid         = $sitepress->get_element_trid( $post->ID, $element_type );
			$translations = $sitepress->get_element_translations( $trid, $element_type );

			foreach ( $translations as $source_language_code => $translation_data ) {
				if ( (int) $translation_data->element_id !== $post->ID ) {
					wp_update_post( [ 'ID' => $translation_data->element_id, 'post_status' => $new_status ] );
				}
			}
		}
	}
}
