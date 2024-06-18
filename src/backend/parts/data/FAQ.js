/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';

import {
	Disabled,
	Panel,
	PanelRow
} from '@wordpress/components';

const Custom = () => {
	return (
		<div className="col-span-6 xl:col-span-4">
			<Panel
				header={ __( 'Coming Soon: FAQ', 'hyve' ) }
			>
				<Disabled>
					<PanelRow>
						<p className="py-4">{ __( 'The FAQ captures frequently asked questions that went unanswered by our chatbot, providing you with a valuable insight into what your users are seeking. This feature allows you to review these queries and decide whether to incorporate them into your bot\'s knowledge base. By actively updating your FAQ, you can continuously refine your chatbot\'s ability to address user needs effectively and enhance their interactive experience. These aren\'t updated instantly.', 'hyve' ) }</p>

						<div className="relative pt-4 pb-8 overflow-x-auto">

							<div className="flex flex-col">
								<div className="bg-gray-50 px-6 py-3 text-left text-xs text-gray-700 uppercase">
									<div className="flex">
										<div className="flex-1">{ __( 'Title', 'hyve' ) }</div>
										<div className="w-1/6">{ __( 'Count', 'hyve' ) }</div>
										<div className="w-1/6 flex justify-center">{ __( 'Action', 'hyve' ) }</div>
									</div>
								</div>
								<div className="flex flex-col">
									<div className="flex justify-center py-4">
										{__( 'No data found.', 'hyve' )}
									</div>
								</div>
							</div>
						</div>
					</PanelRow>
				</Disabled>
			</Panel>
		</div>
	);
};

export default Custom;
