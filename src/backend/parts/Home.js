/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';

import {
	Button,
	Panel,
	PanelRow
} from '@wordpress/components';

import {
	useDispatch,
	useSelect
} from '@wordpress/data';

const Home = () => {
	const hasAPI = useSelect( ( select ) => select( 'hyve' ).hasAPI() );

	const { setRoute } = useDispatch( 'hyve' );

	return (
		<div className="col-span-6 xl:col-span-4">
			<Panel
				header={ __( 'Dashboard', 'hyve' ) }
			>
				<PanelRow>
					<div className="hyve-video">
						<iframe width="560" height="315" className="py-4" src="https://www.youtube.com/embed/XpOpLafnwGE?si=AQiES6dhSMuTACsM" title="YouTube video player" frameBorder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerPolicy="strict-origin-when-cross-origin" allowFullScreen></iframe>
					</div>

					<p className="py-2">{ __( 'Welcome to Hyve! Designed to seamlessly integrate AI chat into your WordPress site, this plugin allows you to craft a personalized chat experience using your own posts and pages. Enjoy engaging with your website visitors through Hyve!', 'hyve' ) }</p>

					{ ! hasAPI && (
						<>
							<p className="py-2">{ __( 'To begin using the Hyve plugin, you\'ll need an OpenAI API key. This key enables Hyve to communicate with OpenAI\'s powerful language models, ensuring you get the best possible responses.', 'hyve' ) }</p>

							<Button
								variant="primary"
								className="mt-2"
								onClick={ () => setRoute( 'advanced' ) }
							>
								{ __( 'Setup API Key', 'hyve' ) }
							</Button>
						</>
					)}

					{ hasAPI && (
						<Button
							variant="primary"
							className="mt-2"
							onClick={ () => setRoute( 'data' ) }
						>
							{ __( 'Get Started', 'hyve' ) }
						</Button>
					) }
				</PanelRow>
			</Panel>
		</div>
	);
};

export default Home;
