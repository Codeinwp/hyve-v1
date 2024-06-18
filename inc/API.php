<?php
/**
 * API class.
 * 
 * @package Codeinwp/Hyve
 */

namespace ThemeIsle\Hyve;

use ThemeIsle\Hyve\Main, ThemeIsle\Hyve\OpenAI, ThemeIsle\Hyve\DB_Table, ThemeIsle\Hyve\Cosine_Similarity;

/**
 * API class.
 */
class API {
	/**
	 * API namespace.
	 *
	 * @var string
	 */
	private $namespace = 'hyve';

	/**
	 * API version.
	 *
	 * @var string
	 */
	private $version = 'v1';

	/**
	 * Instance of DB_Table class.
	 *
	 * @var object
	 */
	private $table;

	/**
	 * Error messages.
	 * 
	 * @var array
	 */
	private $errors = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->table = new DB_Table();

		$this->errors = array(
			'invalid_api_key' => __( 'Incorrect API key provided.', 'hyve' ),
			'missing_scope'   => __( ' You have insufficient permissions for this operation.', 'hyve' ),
		);

		$this->register_route();
	}

	/**
	 * Get endpoint.
	 * 
	 * @return string
	 */
	public function get_endpoint() {
		return $this->namespace . '/' . $this->version;
	}

	/**
	 * Register hooks and actions.
	 * 
	 * @return void
	 */
	private function register_route() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API route
	 * 
	 * @return void
	 */
	public function register_routes() {
		$namespace = $this->namespace . '/' . $this->version;

		$routes = array(
			'settings'                => array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_settings' ),
				),
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'args'     => array(
						'data' => array(
							'required'          => true,
							'type'              => 'object',
							'validate_callback' => function ( $param ) {
								return is_array( $param );
							},
						),
					),
					'callback' => array( $this, 'update_settings' ),
				),
			),
			'license'                 => array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'args'     => array(
						'key'    => array(
							'type'              => 'string',
							'sanitize_callback' => function ( $param ) {
								return (string) esc_attr( $param );
							},
							'validate_callback' => function ( $param ) {
								return is_string( $param );
							},
						),
						'action' => array(
							'type'              => 'string',
							'sanitize_callback' => function ( $param ) {
								return (string) esc_attr( $param );
							},
							'validate_callback' => function ( $param ) {
								return in_array( $param, array( 'activate', 'deactivate' ), true );
							},
						),
					),
					'callback' => array( $this, 'license' ),
				),
			),
			'data'                    => array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'args'     => array(
						'offset' => array(
							'required' => false,
							'type'     => 'integer',
							'default'  => 0,
						),
						'type'   => array(
							'required' => false,
							'type'     => 'string',
							'default'  => 'any',
						),
						'search' => array(
							'required' => false,
							'type'     => 'string',
						),
						'status' => array(
							'required' => false,
							'type'     => 'string',
						),
					),
					'callback' => array( $this, 'get_data' ),
				),
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'args'     => array(
						'action' => array(
							'required' => false,
							'type'     => 'string',
						),
						'data'   => array(
							'required' => true,
							'type'     => 'object',
						),
					),
					'callback' => array( $this, 'add_data' ),
				),
				array(
					'methods'  => \WP_REST_Server::DELETABLE,
					'args'     => array(
						'id' => array(
							'required' => true,
							'type'     => 'integer',
						),
					),
					'callback' => array( $this, 'delete_data' ),
				),
			),
			'knowledge'               => array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'args'     => array(
						'offset' => array(
							'required' => false,
							'type'     => 'integer',
							'default'  => 0,
						),
						'search' => array(
							'required' => false,
							'type'     => 'string',
						),
					),
					'callback' => array( $this, 'get_knowledge' ),
				),
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'args'     => array(
						'data'   => array(
							'required' => true,
							'type'     => 'object',
						),
						'action' => array(
							'required' => false,
							'type'     => 'string',
						),
					),
					'callback' => array( $this, 'add_knowledge' ),
				),
			),
			'knowledge/(?P<id>[\w]+)' => array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'args'     => array(
						'data'   => array(
							'required' => true,
							'type'     => 'object',
						),
						'action' => array(
							'required' => false,
							'type'     => 'string',
						),
					),
					'callback' => array( $this, 'update_knowledge' ),
				),
				array(
					'methods'  => \WP_REST_Server::DELETABLE,
					'callback' => array( $this, 'delete_knowledge' ),
				),
			),
			'chat'                    => array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'args'                => array(
						'run_id'    => array(
							'required' => true,
							'type'     => 'string',
						),
						'thread_id' => array(
							'required' => true,
							'type'     => 'string',
						),
					),
					'callback'            => array( $this, 'get_chat' ),
					'permission_callback' => function ( $request ) {
						$nonce = $request->get_header( 'x_wp_nonce' );
						return wp_verify_nonce( $nonce, 'wp_rest' );
					},
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'args'                => array(
						'message'   => array(
							'required' => true,
							'type'     => 'string',
						),
						'thread_id' => array(
							'required' => false,
							'type'     => 'string',
						),
					),
					'callback'            => array( $this, 'send_chat' ),
					'permission_callback' => function ( $request ) {
						$nonce = $request->get_header( 'x_wp_nonce' );
						return wp_verify_nonce( $nonce, 'wp_rest' );
					},
				),
			),
		);

		foreach ( $routes as $route => $args ) {
			foreach ( $args as $key => $arg ) {
				if ( ! isset( $args[ $key ]['permission_callback'] ) ) {
					$args[ $key ]['permission_callback'] = function () {
						return current_user_can( 'manage_options' );
					};
				}
			}

			register_rest_route( $namespace, '/' . $route, $args );
		}
	}

	/**
	 * Get settings.
	 * 
	 * @return \WP_REST_Response
	 */
	public function get_settings() {
		$settings = Main::get_settings();
		return rest_ensure_response( $settings );
	}

	/**
	 * Get Error Message.
	 * 
	 * @param \WP_Error $error Error.
	 * 
	 * @return string
	 */
	public function get_error_message( $error ) {
		if ( isset( $this->errors[ $error->get_error_code() ] ) ) {
			return $this->errors[ $error->get_error_code() ];
		}

		return $error->get_error_message();
	}

	/**
	 * Update settings.
	 * 
	 * @param \WP_REST_Request $request Request object.
	 * 
	 * @return \WP_REST_Response
	 */
	public function update_settings( $request ) {
		$data     = $request->get_param( 'data' );
		$settings = Main::get_settings();
		$updated  = array();

		foreach ( $data as $key => $datum ) {
			if ( ! array_key_exists( $key, $settings ) || $settings[ $key ] === $datum ) {
				continue;
			}

			$updated[ $key ] = $datum;
		}

		if ( empty( $updated ) ) {
			return rest_ensure_response( array( 'error' => __( 'No settings to update.', 'hyve' ) ) );
		}

		$validation = array(
			'api_key'         => function ( $value ) {
				return is_string( $value );
			},
			'chat_enabled'    => function ( $value ) {
				return is_bool( $value );
			},
			'welcome_message' => function ( $value ) {
				return is_string( $value );
			},
		);

		foreach ( $updated as $key => $value ) {
			if ( ! $validation[ $key ]( $value ) ) {
				return rest_ensure_response(
					array(
						// translators: %s: option key.
						'error' => sprintf( __( 'Invalid value: %s', 'hyve' ), $key ),
					)
				);
			}
		}

		foreach ( $updated as $key => $value ) {
			$settings[ $key ] = $value;

			if ( 'api_key' === $key && ! empty( $value ) ) {
				$openai    = new OpenAI( $value );
				$valid_api = $openai->setup_assistant();
	
				if ( is_wp_error( $valid_api ) ) {
					return rest_ensure_response( array( 'error' => $this->get_error_message( $valid_api ) ) );
				}

				$settings['assistant_id'] = $valid_api;
			}
		}

		update_option( 'hyve_settings', $settings );

		return rest_ensure_response( __( 'Settings updated.', 'hyve' ) );
	}

	/**
	 * Toggle License
	 * 
	 * @param \WP_REST_Request $request Request object.
	 * 
	 * @return \WP_REST_Response
	 */
	public function license( $request ) {
		$data = $request->get_param( 'data' );

		if ( ! isset( $data['key'] ) || ! isset( $data['action'] ) ) {
			return new \WP_REST_Response(
				array(
					'message' => __( 'Invalid Action. Please refresh the page and try again.', 'hyve' ),
					'success' => false,
				)
			);
		}

		$response = apply_filters( 'themeisle_sdk_license_process_hyve', $data['key'], $data['action'] );

		if ( is_wp_error( $response ) ) {
			return new \WP_REST_Response(
				array(
					'message' => $response->get_error_message(),
					'success' => false,
				)
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'activate' === $data['action'] ? __( 'Activated.', 'hyve' ) : __( 'Deactivated', 'hyve' ),
				'license' => array(
					'key'        => apply_filters( 'product_hyve_license_key', 'free' ),
					'valid'      => apply_filters( 'product_hyve_license_status', false ),
					'expiration' => Main::get_license_expiration_date(),
				),
			)
		);
	}

	/**
	 * Get data.
	 * 
	 * @param \WP_REST_Request $request Request object.
	 * 
	 * @return \WP_REST_Response
	 */
	public function get_data( $request ) {
		$args = array(
			'post_type'      => $request->get_param( 'type' ),
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'fields'         => 'ids',
			'offset'         => $request->get_param( 'offset' ),
			'meta_query'     => array(
				array(
					'key'     => '_hyve_added',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_hyve_moderation_failed',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$search = $request->get_param( 'search' );

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$status = $request->get_param( 'status' );

		if ( 'included' === $status ) {
			$args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => '_hyve_added',
					'value'   => '1',
					'compare' => '=',
				),
				array(
					'key'     => '_hyve_moderation_failed',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		if ( 'pending' === $status ) {
			$args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => '_hyve_needs_update',
					'value'   => '1',
					'compare' => '=',
				),
				array(
					'key'     => '_hyve_moderation_failed',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		if ( 'moderation' === $status ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_hyve_moderation_failed',
					'value'   => '1',
					'compare' => '=',
				),
			);
		}

		$query = new \WP_Query( $args );

		$posts_data = array();
		
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post_id ) {
				$post_data = array(
					'ID'      => $post_id,
					'title'   => get_the_title( $post_id ),
					'content' => apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) ),
				);

				if ( 'moderation' === $status ) {
					$review = get_post_meta( $post_id, '_hyve_moderation_review', true );
	
					if ( ! is_array( $review ) || empty( $review ) ) {
						$review = array();
					}

					$post_data['review'] = $review;
				}

				$posts_data[] = $post_data;
			}
		}

		$db = new DB_Table();

		$posts = array(
			'posts'       => $posts_data,
			'more'        => $query->found_posts > 20,
			'totalChunks' => $db->get_count(),
		);
		
		return rest_ensure_response( $posts );
	}

	/**
	 * Moderate data.
	 * 
	 * @param array|string $chunks Data to moderate.
	 * @param int          $id     Post ID.
	 * 
	 * @return true|array|\WP_Error
	 */
	private function moderate( $chunks, $id = null ) {
		if ( $id ) {
			$moderated = get_transient( 'hyve_moderate_post_' . $id );

			if ( false !== $moderated ) {
				return is_array( $moderated ) ? $moderated : true;
			}
		}

		$openai               = new OpenAI();
		$results              = array();
		$return               = true;
		$settings             = Main::get_settings();
		$moderation_threshold = array(
			'sexual'                 => 80,
			'hate'                   => 70,
			'harassment'             => 70,
			'self-harm'              => 50,
			'sexual/minors'          => 50,
			'hate/threatening'       => 60,
			'violence/graphic'       => 80,
			'self-harm/intent'       => 50,
			'self-harm/instructions' => 50,
			'harassment/threatening' => 60,
			'violence'               => 70,
		);

		if ( ! is_array( $chunks ) ) {
			$chunks = array( $chunks );
		}

		foreach ( $chunks as $chunk ) {
			$moderation = $openai->moderate( $chunk );

			if ( is_wp_error( $moderation ) ) {
				return $moderation;
			}

			if ( true !== $moderation && is_object( $moderation ) ) {
				$results[] = $moderation;
			}
		}

		if ( ! empty( $results ) ) {
			$flagged = array();
	
			foreach ( $results as $result ) {
				$categories = $result->categories;
	
				foreach ( $categories as $category => $flag ) {
					if ( ! $flag ) {
						continue;
					}

					if ( ! isset( $moderation_threshold[ $category ] ) || $result->category_scores->$category < ( $moderation_threshold[ $category ] / 100 ) ) {
						continue;
					}

					if ( ! isset( $flagged[ $category ] ) ) {
						$flagged[ $category ] = $result->category_scores->$category;
						continue;
					}
	
					if ( $result->category_scores->$category > $flagged[ $category ] ) {
						$flagged[ $category ] = $result->category_scores->$category;
					}
				}
			}

			if ( empty( $flagged ) ) {
				$return = true;
			} else {
				$return = $flagged;
			}
		}

		if ( $id ) {
			set_transient( 'hyve_moderate_post_' . $id, $return, MINUTE_IN_SECONDS );
		}

		return $return;
	}

	/**
	 * Add data.
	 * 
	 * @param \WP_REST_Request $request Request object.
	 * 
	 * @return \WP_REST_Response
	 */
	public function add_data( $request ) {
		$data    = $request->get_param( 'data' );
		$content = apply_filters( 'the_content', get_post_field( 'post_content', $data['post_id'] ) );
		$chunks  = str_split( $content, 2000 );

		$moderation = $this->moderate( $chunks, $data['post_id'] );

		if ( is_wp_error( $moderation ) ) {
			return rest_ensure_response( array( 'error' => $this->get_error_message( $moderation ) ) );
		}

		if ( true !== $moderation && 'override' !== $request->get_param( 'action' ) ) {
			update_post_meta( $data['post_id'], '_hyve_moderation_failed', 1 );
			update_post_meta( $data['post_id'], '_hyve_moderation_review', $moderation );

			return rest_ensure_response(
				array(
					'error'  => __( 'The content failed moderation policies.', 'hyve' ),
					'code'   => 'content_failed_moderation',
					'review' => $moderation,
				)
			);
		}

		if ( 'update' === $request->get_param( 'action' ) ) {
			$this->table->delete_by_post_id( $data['post_id'] );
			delete_post_meta( $data['post_id'], '_hyve_needs_update' );
		}

		$this->table->insert( $data );

		update_post_meta( $data['post_id'], '_hyve_added', 1 );
		delete_post_meta( $data['post_id'], '_hyve_moderation_failed' );
		delete_post_meta( $data['post_id'], '_hyve_moderation_review' );

		$this->table->process_posts();

		return rest_ensure_response( true );
	}

	/**
	 * Get Knowledge.
	 * 
	 * @param \WP_REST_Request $request Request object.
	 * 
	 * @return \WP_REST_Response
	 */
	public function get_knowledge( $request ) {
		$args = array(
			'post_type'      => 'hyve_docs',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'fields'         => 'ids',
			'offset'         => $request->get_param( 'offset' ),
		);

		$search = $request->get_param( 'search' );

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$query = new \WP_Query( $args );

		$posts_data = array();
		
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post_id ) {
				$content = get_post_field( 'post_content', $post_id );
				$content = wp_strip_all_tags( $content );

				$posts_data[] = array(
					'ID'      => $post_id,
					'title'   => get_the_title( $post_id ),
					'content' => $content,
				);
			}
		}

		$db = new DB_Table();
		
		$posts = array(
			'posts'       => $posts_data,
			'more'        => $query->found_posts > 20,
			'totalChunks' => $db->get_count(),
		);
		
		return rest_ensure_response( $posts );
	}

	/**
	 * Add data.
	 * 
	 * @param \WP_REST_Request $request Request object.
	 * 
	 * @return \WP_REST_Response
	 */
	public function add_knowledge( $request ) {
		$data       = $request->get_param( 'data' );
		$chunks     = str_split( $data['post_content'], 2000 );
		$moderation = $this->moderate( $chunks );

		if ( is_wp_error( $moderation ) ) {
			return rest_ensure_response( array( 'error' => $this->get_error_message( $moderation ) ) );
		}

		if ( true !== $moderation && 'override' !== $request->get_param( 'action' ) ) {
			return rest_ensure_response(
				array(
					'error'  => __( 'The content failed moderation policies.', 'hyve' ),
					'code'   => 'content_failed_moderation',
					'review' => $moderation,
				)
			);
		}

		$post_id = wp_insert_post(
			array(
				'post_title'   => $data['post_title'],
				'post_content' => $data['post_content'],
				'post_status'  => 'publish',
				'post_type'    => 'hyve_docs',
			) 
		);

		if ( ! $post_id ) {
			return rest_ensure_response( array( 'error' => __( 'Failed to insert post.', 'hyve' ) ) );
		}

		$data['post_id'] = $post_id;

		$this->table->insert( $data );

		update_post_meta( $post_id, '_hyve_added', 1 );

		$this->table->process_posts();

		return rest_ensure_response( $post_id );
	}

	/**
	 * Update knowledge.
	 * 
	 * @param \WP_REST_Request $request Request object.
	 * 
	 * @return \WP_REST_Response
	 */
	public function update_knowledge( $request ) {
		$id         = $request->get_param( 'id' );
		$data       = $request->get_param( 'data' );
		$chunks     = str_split( $data['post_content'], 2000 );
		$moderation = $this->moderate( $chunks );

		if ( is_wp_error( $moderation ) ) {
			return rest_ensure_response( array( 'error' => $this->get_error_message( $moderation ) ) );
		}

		if ( true !== $moderation && 'override' !== $request->get_param( 'action' ) ) {
			return rest_ensure_response(
				array(
					'error'  => __( 'The content failed moderation policies.', 'hyve' ),
					'code'   => 'content_failed_moderation',
					'review' => $moderation,
				)
			);
		}

		$post_id = wp_update_post(
			array(
				'ID'           => $id,
				'post_title'   => $data['post_title'],
				'post_content' => $data['post_content'],
			) 
		);

		if ( ! $post_id ) {
			return rest_ensure_response( array( 'error' => __( 'Failed to update post.', 'hyve' ) ) );
		}

		$this->table->delete_by_post_id( $id );

		$data['post_id'] = $post_id;

		$this->table->insert( $data );

		update_post_meta( $post_id, '_hyve_added', 1 );

		$this->table->process_posts();

		return rest_ensure_response( $post_id );
	}

	/**
	 * Delete knowledge.
	 * 
	 * @param \WP_REST_Request $request Request object.
	 * 
	 * @return \WP_REST_Response
	 */
	public function delete_knowledge( $request ) {
		$id = $request->get_param( 'id' );

		$this->table->delete_by_post_id( $id );

		$delete = wp_delete_post( $id, true );

		if ( ! $delete ) {
			return rest_ensure_response( array( 'error' => __( 'Failed to delete post.', 'hyve' ) ) );
		}

		return rest_ensure_response( true );
	}

	/**
	 * Delete data.
	 * 
	 * @param \WP_REST_Request $request Request object.
	 * 
	 * @return \WP_REST_Response
	 */
	public function delete_data( $request ) {
		$id = $request->get_param( 'id' );

		$this->table->delete_by_post_id( $id );

		delete_post_meta( $id, '_hyve_added' );
		delete_post_meta( $id, '_hyve_needs_update' );
		delete_post_meta( $id, '_hyve_moderation_failed' );
		delete_post_meta( $id, '_hyve_moderation_review' );
		return rest_ensure_response( true );
	}

	/**
	 * Get chat.
	 * 
	 * @param \WP_REST_Request $request Request object.
	 * 
	 * @return \WP_REST_Response
	 */
	public function get_chat( $request ) {
		$run_id    = $request->get_param( 'run_id' );
		$thread_id = $request->get_param( 'thread_id' );

		$openai = new OpenAI();

		$status = $openai->get_status( $run_id, $thread_id );

		if ( is_wp_error( $status ) ) {
			return rest_ensure_response( array( 'error' => $this->get_error_message( $status ) ) );
		}

		if ( 'completed' !== $status ) {
			return rest_ensure_response( array( 'status' => $status ) );
		}

		$messages = $openai->get_messages( $thread_id );

		if ( is_wp_error( $messages ) ) {
			return rest_ensure_response( array( 'error' => $this->get_error_message( $messages ) ) );
		}

		$messages = array_filter(
			$messages,
			function ( $message ) use ( $run_id ) {
				return $message->run_id === $run_id;
			} 
		);

		$message = reset( $messages )->content[0]->text->value;

		return rest_ensure_response(
			array(
				'status'  => $status,
				'message' => $message,
			) 
		);
	}

	/**
	 * Send chat.
	 * 
	 * @param \WP_REST_Request $request Request object.
	 * 
	 * @return \WP_REST_Response
	 */
	public function send_chat( $request ) {
		$message    = $request->get_param( 'message' );
		$moderation = $this->moderate( $message );

		if ( true !== $moderation ) {
			return rest_ensure_response( array( 'error' => __( 'Message was flagged.', 'hyve' ) ) );
		}

		$openai         = new OpenAI();
		$message_vector = $openai->create_embeddings( $message );
		$message_vector = reset( $message_vector );
		$message_vector = $message_vector->embedding;

		if ( is_wp_error( $message_vector ) ) {
			return rest_ensure_response( array( 'error' => __( 'No embeddings found.', 'hyve' ) ) );
		}

		$hash = md5( strtolower( $message ) );
		set_transient( 'hyve_message_' . $hash, $message_vector, MINUTE_IN_SECONDS );

		$posts = $this->table->get_by_status( 'processed' );

		$embeddings_with_cosine_distance_sorted = array_map(
			function ( $row ) use ( $message_vector ) {
				$embeddings = json_decode( $row->embeddings, true );

				if ( ! is_array( $embeddings ) ) {
					return array(
						'post_id'      => $row->post_id,
						'distance'     => 0,
						'token_count'  => $row->token_count,
						'post_title'   => $row->post_title,
						'post_content' => $row->post_content,
					);
				}

				$distance = Cosine_Similarity::calculate( $message_vector, $embeddings );

				return array(
					'post_id'      => $row->post_id,
					'distance'     => $distance,
					'token_count'  => $row->token_count,
					'post_title'   => $row->post_title,
					'post_content' => $row->post_content,
				);
			},
			$posts 
		);

		usort(
			$embeddings_with_cosine_distance_sorted,
			function ( $a, $b ) {
				if ( $a['distance'] < $b['distance'] ) {
					return 1;
				} elseif ( $a['distance'] > $b['distance'] ) {
					return -1;
				} else {
					return 0;
				}
			} 
		);

		$embeddings_with_cosine_distance_sorted = array_filter(
			$embeddings_with_cosine_distance_sorted,
			function ( $row ) {
				return $row['distance'] > 0.4;
			} 
		);

		$max_tokens_length  = 2000;
		$curr_tokens_length = 0;
		$article_context    = '';

		foreach ( $embeddings_with_cosine_distance_sorted as $row ) {
			$curr_tokens_length += $row['token_count'];
			if ( $curr_tokens_length < $max_tokens_length ) {
				$article_context .= "\n ===START POST=== " . $row['post_title'] . ' - ' . $row['post_content'] . ' ===END POST===';
			}
		}

		if ( $request->get_param( 'thread_id' ) ) {
			$thread_id = $request->get_param( 'thread_id' );
		} else {
			$thread_id = $openai->create_thread();
		}

		if ( is_wp_error( $thread_id ) ) {
			return rest_ensure_response( array( 'error' => $this->get_error_message( $thread_id ) ) );
		}

		$query_run = $openai->create_run(
			array(
				array(
					'role'    => 'user',
					'content' => 'START QUESTION: ' . $message . ' :END QUESTION',
				),
				array(
					'role'    => 'user',
					'content' => 'START CONTEXT: ' . $article_context . ' :END CONTEXT',
				),
			),
			$thread_id
		);

		if ( is_wp_error( $query_run ) ) {
			if ( strpos( $this->get_error_message( $query_run ), 'No thread found with id' ) !== false ) {
				$thread_id = $openai->create_thread();

				if ( is_wp_error( $thread_id ) ) {
					return rest_ensure_response( array( 'error' => $this->get_error_message( $thread_id ) ) );
				}

				$query_run = $openai->create_run(
					array(
						array(
							'role'    => 'user',
							'content' => 'Question: ' . $message,
						),
						array(
							'role'    => 'user',
							'content' => 'Context: ' . $article_context,
						),
					),
					$thread_id
				);

				if ( is_wp_error( $query_run ) ) {
					return rest_ensure_response( array( 'error' => $this->get_error_message( $query_run ) ) );
				}
			}
		}

		return rest_ensure_response(
			array(
				'thread_id' => $thread_id,
				'query_run' => $query_run,
			) 
		);
	}
}
