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

const Assistant = () => {
	return (
		<div className="col-span-6 xl:col-span-4">
			<Panel
				header={ __( 'Coming Soon: Assistant Settings', 'hyve' ) }
			>
				<Disabled>
					<PanelRow>
						<RangeControl
							label={ __( 'Temperature', 'hyve' ) }
							help={ __( 'What sampling temperature to use, between 0 and 2. Higher values like 0.8 will make the output more random, while lower values like 0.2 will make it more focused and deterministic. We generally recommend altering this or temperature but not both.', 'hyve' ) }
							initialPosition={ 1 }
							max={ 2 }
							min={ 0.1 }
							step={ 0.1 }
							value={ 1 }
							onChange={ () => {} }
						/>
					</PanelRow>

					<PanelRow>
						<RangeControl
							label={ __( 'Top P', 'hyve' ) }
							help={ __( 'What sampling temperature to use, between 0 and 2. Higher values like 0.8 will make the output more random, while lower values like 0.2 will make it more focused and deterministic.', 'hyve' ) }
							initialPosition={ 1 }
							max={ 1 }
							min={ 0.1 }
							step={ 0.1 }
							value={ 1 }
							onChange={ () => {} }
						/>
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

export default Assistant;
