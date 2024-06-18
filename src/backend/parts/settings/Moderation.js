/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';

import {
	Button,
	Disabled,
	Panel,
	PanelRow,
	RangeControl
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import { moderationLabels } from '../../utils';

const Moderation = () => {
	return (
		<div className="col-span-6 xl:col-span-4">
			<Panel
				header={ __( 'Coming Soon: Moderation Settings', 'hyve' ) }
			>
				<Disabled>
					<PanelRow>
						<p className="py-4">{ __( 'With the moderation functionality, you can check whether your content is potentially harmful. Users can use it to identify content that might be harmful and take action.', 'hyve' ) }</p>

						<p className="pt-2 pb-4">{ __( 'Occasionally, OpenAI\'s Moderation system may incorrectly flag content as a violation—these are false positives. Such errors can occur because automated systems sometimes lack the necessary context to interpret nuances accurately. If your content is flagged but you believe it adheres to the guidelines, please manually review it. Should you determine it does not violate the content policies, you can also override the moderation decisions.', 'hyve' ) }</p>

						{ Object.keys( moderationLabels ).map( moderation => (
							<RangeControl
								key={ moderation }
								label={ moderationLabels[ moderation ].label }
								help={ moderationLabels[ moderation ].description }
								initialPosition={ 0.5 }
								max={ 1 }
								min={ 0 }
								step={ 0.1 }
								value={ 100 }
								allowReset
								resetFallbackValue={ moderationLabels[ moderation ].default }
								className="py-4"
								onChange={ () => {} }
							/>
						) ) }
					</PanelRow>

					<PanelRow>
						<Button
							variant="primary"
							className="mt-2"
							onClick={ () => {} }
						>
							{ __( 'Save', 'hyve' ) }
						</Button>
					</PanelRow>
				</Disabled>
			</Panel>
		</div>
	);
};

export default Moderation;
