<?php
/**
 * OpenAI class.
 * 
 * @package Codeinwp/Hyve
 */

namespace ThemeIsle\Hyve;

use ThemeIsle\Hyve\Main;

/**
 * OpenAI class.
 */
class OpenAI {
	/**
	 * Base URL.
	 * 
	 * @var string
	 */
	private static $base_url = 'https://api.openai.com/v1/';

	/**
	 * API Key.
	 * 
	 * @var string
	 */
	private $api_key;

	/**
	 * Assistant ID.
	 * 
	 * @var string
	 */
	private $assistant_id;

	/**
	 * Constructor.
	 * 
	 * @param string $api_key API Key.
	 */
	public function __construct( $api_key = '' ) {
		$settings           = Main::get_settings();
		$this->api_key      = ! empty( $api_key ) ? $api_key : ( isset( $settings['api_key'] ) ? $settings['api_key'] : '' );
		$this->assistant_id = isset( $settings['assistant_id'] ) ? $settings['assistant_id'] : '';
	}

	/**
	 * Setup Assistant.
	 * 
	 * @return string|\WP_Error
	 */
	public function setup_assistant() {
		$assistant = $this->retrieve_assistant();

		if ( is_wp_error( $assistant ) ) {
			return $assistant;
		}

		if ( ! $assistant ) {
			return $this->create_assistant();
		}

		return $assistant;
	}

	/**
	 * Create Assistant.
	 * 
	 * @return string|\WP_Error
	 */
	public function create_assistant() {
		$response = $this->request(
			'assistants',
			array(
				'instructions' => 'Assistant Role & Concise Response Guidelines: As a Support Assistant, provide precise, to-the-point answers based on the provided context, adhering to these principles: 1. Direct Answers: If the context directly addresses the user\'s question, deliver a succinct response with essential details and necessary code snippets. Avoid extraneous information, but make sure to include all important information. 2. Related Information: For indirectly related questions, synthesize context information to form a concise, relevant reply. Clearly outline the connections made, using as few words as possible. 3. Out-of-Scope Queries: If a question is outside the context, simply state, "This is beyond my current knowledge." If the user sends you a greeting, you can reply with a greeting and offer to assist. However, do not reply to anything that falls outside of the context window.. Responses should be chat-friendly: relevant, straightforward, and without unnecessary elaboration to ensure clarity and efficiency in support.',
				'name'         => 'Chatbot by Hyve',
				'model'        => 'gpt-3.5-turbo-0125',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( isset( $response->id ) ) {
			$this->assistant_id = $response->id;
			return $response->id;
		}

		return new \WP_Error( 'unknown_error', __( 'An error occurred while creating the assistant.', 'hyve' ) );
	}

	/**
	 * Retrieve Assistant.
	 * 
	 * @return string|\WP_Error|false
	 */
	public function retrieve_assistant() {
		if ( ! $this->assistant_id ) {
			return false;
		}

		$response = $this->request( 'assistants/' . $this->assistant_id );

		if ( is_wp_error( $response ) ) {
			if ( strpos( $response->get_error_message(), 'No assistant found' ) !== false ) {
				return false;
			}

			return $response;
		}

		if ( isset( $response->id ) ) {
			return $response->id;
		}

		return false;
	}

	/**
	 * Create Embeddings.
	 * 
	 * @param string|array $content Content.
	 * @param string       $model   Model.
	 * 
	 * @return mixed
	 */
	public function create_embeddings( $content, $model = 'text-embedding-3-small' ) {
		$response = $this->request(
			'embeddings',
			array(
				'input' => $content,
				'model' => $model,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( isset( $response->data ) ) {
			return $response->data;
		}

		return new \WP_Error( 'unknown_error', __( 'An error occurred while creating the embeddings.', 'hyve' ) );
	}

	/**
	 * Create a Thread.
	 * 
	 * @param array $params Parameters.
	 * 
	 * @return string|\WP_Error
	 */
	public function create_thread( $params = array() ) {
		$response = $this->request(
			'threads',
			$params
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( isset( $response->id ) ) {
			return $response->id;
		}

		return new \WP_Error( 'unknown_error', __( 'An error occurred while creating the thread.', 'hyve' ) );
	}

	/**
	 * Send Message.
	 * 
	 * @param string $message Message.
	 * @param string $thread  Thread.
	 * @param string $role    Role.
	 * 
	 * @return true|\WP_Error
	 */
	public function send_message( $message, $thread, $role = 'assistant' ) {
		$response = $this->request(
			'threads/' . $thread . '/messages',
			array(
				'role'    => $role,
				'content' => $message,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( isset( $response->id ) ) {
			return true;
		}

		return new \WP_Error( 'unknown_error', __( 'An error occurred while sending the message.', 'hyve' ) );
	}

	/**
	 * Create a run
	 * 
	 * @param array  $messages Messages.
	 * @param string $thread  Thread.
	 * 
	 * @return string|\WP_Error
	 */
	public function create_run( $messages, $thread ) {
		$settings = Main::get_settings();

		$response = $this->request(
			'threads/' . $thread . '/runs',
			array(
				'assistant_id'        => $this->assistant_id,
				'additional_messages' => $messages,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response->id ) || ( isset( $response->status ) && 'queued' !== $response->status ) ) {
			return new \WP_Error( 'unknown_error', __( 'An error occurred while creating the run.', 'hyve' ) );
		}

		return $response->id;
	}

	/**
	 * Get Run Status.
	 * 
	 * @param string $run_id Run ID.
	 * @param string $thread Thread.
	 * 
	 * @return string|\WP_Error
	 */
	public function get_status( $run_id, $thread ) {
		$response = $this->request( 'threads/' . $thread . '/runs/' . $run_id, array(), 'GET' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( isset( $response->status ) ) {
			return $response->status;
		}

		return new \WP_Error( 'unknown_error', __( 'An error occurred while getting the run status.', 'hyve' ) );
	}

	/**
	 * Get Thread Messages.
	 * 
	 * @param string $thread Thread.
	 * 
	 * @return mixed
	 */
	public function get_messages( $thread ) {
		$response = $this->request( 'threads/' . $thread . '/messages', array(), 'GET' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( isset( $response->data ) ) {
			return $response->data;
		}

		return new \WP_Error( 'unknown_error', __( 'An error occurred while getting the messages.', 'hyve' ) );
	}

	/**
	 * Create Moderation Request.
	 * 
	 * @param string $message Message.
	 * 
	 * @return true|object|\WP_Error
	 */
	public function moderate( $message ) {
		$response = $this->request(
			'moderations',
			array(
				'input' => $message,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( isset( $response->results ) ) {
			$result = reset( $response->results );

			if ( isset( $result->flagged ) && $result->flagged ) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Create Request.
	 * 
	 * @param string $endpoint Endpoint.
	 * @param array  $params   Parameters.
	 * @param string $method   Method.
	 * 
	 * @return mixed
	 */
	private function request( $endpoint, $params = array(), $method = 'POST' ) {
		if ( ! $this->api_key ) {
			return (object) array(
				'error'   => true,
				'message' => 'API key is missing.',
			);
		}

		$body = wp_json_encode( $params );

		$response = '';

		if ( 'POST' === $method ) {
			$response = wp_remote_post(
				self::$base_url . $endpoint,
				array(
					'headers'     => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $this->api_key,
						'OpenAI-Beta'   => 'assistants=v2',
					), 
					'body'        => $body,
					'method'      => 'POST',
					'data_format' => 'body',
				) 
			);
		}

		if ( 'GET' === $method ) {
			$url  = self::$base_url . $endpoint;
			$args = array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
			);

			if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
				$response = vip_safe_wp_remote_get( $url, '', 3, 1, 20, $args );
			} else {
				$response = wp_remote_get( $url, $args ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
			}
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		} else {
			$body = wp_remote_retrieve_body( $response );
			$body = json_decode( $body );

			if ( isset( $body->error ) ) {
				if ( isset( $body->error->message ) ) {
					return new \WP_Error( isset( $body->error->code ) ? $body->error->code : 'unknown_error', $body->error->message );
				}

				return new \WP_Error( 'unknown_error', __( 'An error occurred while processing the request.', 'hyve' ) );
			}

			return $body;
		}
	}
}
