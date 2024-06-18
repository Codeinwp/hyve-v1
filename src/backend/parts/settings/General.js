/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';

import apiFetch from '@wordpress/api-fetch';

import {
	BaseControl,
	Button,
	Disabled,
	Modal,
	Panel,
	PanelRow,
	TextareaControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption
} from '@wordpress/components';

import {
	useState
} from '@wordpress/element';

import {
	useDispatch,
	useSelect
} from '@wordpress/data';

const General = () => {
	const settings = useSelect( ( select ) => select( 'hyve' ).getSettings() );
	const { setSetting } = useDispatch( 'hyve' );

	const { createNotice } = useDispatch( 'core/notices' );

	const [ isSaving, setIsSaving ] = useState( false );
	const [ isOpen, setIsOpen ] = useState( false );

	const onSave = async() => {
		setIsSaving( true );

		try {
			const response = await apiFetch({
				path: `${ window.hyve.api }/settings`,
				method: 'POST',
				data: {
					data: settings
				}
			});

			if ( response.error ) {
				throw new Error( response.error );
			}

			createNotice(
				'success',
				__( 'Settings saved.', 'hyve' ),
				{
					type: 'snackbar',
					isDismissible: true
				});
		} catch ( error ) {
			createNotice(
				'error',
				error,
				{
					type: 'snackbar',
					isDismissible: true
				});
		}

		setIsSaving( false );
	};

	return (
		<div className="col-span-6 xl:col-span-4">
			<Panel
				header={ __( 'General Settings', 'hyve' ) }
			>
				<PanelRow>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						isBlock
						label={ __( 'Enable Chat', 'hyve' ) }
						value={ Boolean( settings.chat_enabled ) }
						onChange={ newValue => setSetting( 'chat_enabled', Boolean( newValue ) ) }
					>
						<ToggleGroupControlOption
							aria-label={ __( 'Enable Chat', 'hyve' ) }
							label={ __( 'Enable', 'hyve' ) }
							showTooltip
							value={ true }
						/>
						<ToggleGroupControlOption
							aria-label={ __( 'Enable Chat', 'hyve' ) }
							label={ __( 'Disable', 'hyve' ) }
							showTooltip
							value={ false }
						/>
					</ToggleGroupControl>
				</PanelRow>

				<PanelRow>
					<TextareaControl
						label={ __( 'Welcome Message', 'hyve' ) }
						help={ __( 'This message will be displayed when the chat is opened.', 'hyve' ) }
						value={ settings.welcome_message || '' }
						disabled={ isSaving }
						onChange={ ( newValue ) => setSetting( 'welcome_message', newValue ) }
					/>
				</PanelRow>

				<PanelRow>
					<Disabled>
						<TextareaControl
							label={ __( 'Coming Soon: Default Message', 'hyve' ) }
							help={ __( 'This message will return when the chat is unable to find an answer.', 'hyve' ) }
							value={ __( 'Sorry, I\'m not able to help with that.', 'hyve' ) }
							onChange={ ( newValue ) => {} }
						/>
					</Disabled>
				</PanelRow>

				<PanelRow>
					<BaseControl
						label={ __( 'Pre-defined Questions', 'hyve' ) }
						help={ __( 'These questions will be displayed in the chat to get the conversation started.', 'hyve' ) }
						className="hyve-base-button-control"
					>
						<Button
							variant="secondary"
							isBusy={ isSaving }
							disabled={ isSaving }
							onClick={ () => setIsOpen( true )}
						>
							{ __( 'Edit', 'hyve' ) }
						</Button>
					</BaseControl>
				</PanelRow>

				{ isOpen && (
					<Modal
						title={ __( 'Coming Soon: Pre-defined Questions', 'hyve' ) }
						onRequestClose={ () => setIsOpen( false ) }
						size="medium"
					>
						<div className="overflow-y-auto max-h-96">
							{ __( 'An upcoming feature will soon enable you to use conversation starters by displaying up to five pre-defined questions to initiate discussions in the chat. Please look out for this update.', 'hyve' )}
						</div>

						<div className="flex gap-4 justify-end mt-4">
							<Button
								variant="secondary"
								onClick={ () => setIsOpen( false ) }
							>
								{ __( 'Close', 'hyve' ) }
							</Button>
						</div>
					</Modal>
				) }

				<PanelRow>
					<Button
						variant="primary"
						isBusy={ isSaving }
						disabled={ isSaving }
						className="mt-2"
						onClick={ onSave }
					>
						{ __( 'Save', 'hyve' ) }
					</Button>
				</PanelRow>
			</Panel>
		</div>
	);
};

export default General;
