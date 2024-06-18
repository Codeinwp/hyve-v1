/**
 * External dependencies.
 */
import hash from 'object-hash';

/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';

import apiFetch from '@wordpress/api-fetch';

import {
	Notice,
	Panel,
	PanelRow,
	SearchControl,
	SelectControl
} from '@wordpress/components';

import {
	useDispatch,
	useSelect
} from '@wordpress/data';

import {
	useEffect,
	useState
} from '@wordpress/element';

import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies.
 */
import { onProcessData } from '../../utils';

import PostsTable from '../PostsTable';

import ModerationReview from '../ModerationReview';

const excludeTypes = [ 'attachment' ];

let postTypes = window.hyve.postTypes.filter( ( postType ) => ! excludeTypes.includes( postType.value ) );

postTypes.unshift({
	label: __( 'All', 'hyve' ),
	value: 'any'
});

const AddData = () => {
	const [ posts, setPosts ] = useState({});
	const [ processedPosts, setProcessedPosts ] = useState([]);
	const [ hasMore, setHasMore ] = useState( false );
	const [ isLoading, setLoading ] = useState( true );
	const [ isUpdating, setUpdating ] = useState([]);
	const [ isModerationModalOpen, setModerationModalOpen ] = useState( false );
	const [ post, setPost ] = useState( null );

	const [ query, setQuery ] = useState({
		type: 'any',
		search: ''
	});

	const { setTotalChunks } = useDispatch( 'hyve' );
	const hasReachedLimit = useSelect( ( select ) => select( 'hyve' ).hasReachedLimit() );

	const fetchData = async() => {
		setLoading( true );

		const queryHash = hash( query );

		const response = await apiFetch({
			path: addQueryArgs( `${ window.hyve.api }/data`, {
				offset: posts[ queryHash ]?.length || 0,
				...query
			})
		});

		setLoading( false );

		setPosts({
			...posts,
			[ queryHash ]: ( posts[ queryHash ] || []).concat( response.posts )
		});

		setHasMore( response.more );
		setTotalChunks( response?.totalChunks );
	};

	const onProcess = async( id ) => {
		setUpdating( prev => [ ...prev, id ]);
		const post = posts[hash( query )].find( post => post.ID === id );

		await onProcessData({
			post,
			onSuccess: () => {
				setUpdating( prev => prev.filter( postId => postId !== id ) );
				setProcessedPosts( prev => [ ...prev, id ]);
			},
			onError: ( error ) => {
				if ( 'content_failed_moderation' === error?.code && undefined !== error.review ) {
					const newPost = {
						...post,
						review: error.review
					};

					setPost( newPost );
					setModerationModalOpen( true );
				}

				setUpdating( prev => prev.filter( postId => postId !== id ) );
			}
		});
	};

	useEffect( () => {
		const handler = setTimeout( () => fetchData(), 1000 );
		return () => clearTimeout( handler );
	}, [ query ]);

	const onChangeQuery = ( key, value ) => {
		setQuery({
			...query,
			[ key ]: value
		});
	};

	return (
		<>
			<div className="col-span-6 xl:col-span-4">
				<Panel
					header={ __( 'Add Data', 'hyve' ) }
				>
					<PanelRow>
						{ hasReachedLimit && (
							<Notice
								status="warning"
								isDismissible={ false }
							>
								{ __( 'You have reached the limit of posts that can be added to the knowledge base. Please delete existing posts if you wish to add more.', 'hyve' ) }
							</Notice>
						) }

						<p className="py-4">{ __( 'Select posts that are informative, engaging, and relevant. These will be the building blocks that empower your chat assistant to deliver precise and helpful responses. Whether it is answering FAQs or diving into detailed explanations, the content you choose here will shape how effectively your assistant interacts with users.', 'hyve' ) }</p>

						<div className="relative pt-4 pb-8 overflow-x-auto">
							<div className="flex gap-4 pb-2">
								<div className="w-1/4">
									<SelectControl
										label={ __( 'Post Type', 'hyve' ) }
										hideLabelFromVision={ true }
										className="h-10"
										options={ postTypes }
										onChange={ e => onChangeQuery( 'type', e )}
									/>
								</div>

								<div className="w-3/4">
									<SearchControl
										label={ __( 'Search for Posts', 'hyve' ) }
										value={ query.search }
										onChange={ e => onChangeQuery( 'search', e )}
									/>
								</div>
							</div>

							<PostsTable
								posts={ posts[ hash( query ) ]?.filter( post => ! processedPosts.includes( post.ID ) ) || []}
								isLoading={ isLoading }
								hasMore={ hasMore }
								onFetch={ fetchData }
								onAction={ onProcess }
								actionLabel={ __( 'Add', 'hyve' ) }
								isBusy={ isUpdating }
								isDisabled={ hasReachedLimit }
							/>
						</div>
					</PanelRow>
				</Panel>
			</div>

			<ModerationReview
				post={ post }
				isOpen={ isModerationModalOpen }
				onClose={ () => {
					setModerationModalOpen( false );
					setUpdating( prev => prev.filter( postId => postId !== post.ID ) );
				} }
			/>
		</>
	);
};

export default AddData;
