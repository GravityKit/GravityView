import attributes from './config';
import Inspector from './inspector';
import icon from 'AssetSources/js/icon';

const { registerBlockType } = wp.blocks;
const { Fragment } = wp.element;
const { ServerSideRender, SelectControl } = wp.components;
const { __ } = wp.i18n;

/**
 * Register block
 */
const blockName = 'gv-blocks/field';

export default registerBlockType( blockName, {
	category: 'gravityview',
	title: __( 'GravityView Field', 'gv-blocks' ),
	icon,
	keywords: [ 'gv', __( 'GravityView', 'gv-blocks' ) ],
	attributes,
	transforms: {
		from: [
			{
				type: 'shortcode',
				tag: [ 'gvfield' ],
				attributes: {
					view_id: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.view_id;
						},
					},
					entry_id: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.entry_id;
						},
					},
					field_id: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.field_id;
						},
					},
					custom_label: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.custom_label;
						},
					},
				},
			},
		],
	},
	edit: props => {
		const { attributes, setAttributes } = props;
		const viewLists = [
			{
				value: '',
				label: __( 'Select a View', 'gv-blocks' ),
			},
			...GV_BLOCKS.view_list,
		];

		return [
			<Inspector { ...{ setAttributes, ...props } } />,
			<Fragment>
				{
					( ! attributes.preview || attributes.view_id === '' || attributes.view_id === 'Select a View' || attributes.entry_id === '' || attributes.field_id === '' ) &&
					<div className="gravityview-shortcode-preview">
						<img src={ `${ GV_BLOCKS.img_url }gv-logo.png` } alt={ __( 'GravityView', 'gv-blocks' ) } />
						<div className="field-container">
							<SelectControl
								value={ attributes.view_id }
								options={ viewLists }
								onChange={ view_id => {
									setAttributes( {
										view_id,
									} );
								} }
							/>
						</div>
					</div>
				}
				{
					( attributes.preview && attributes.view_id !== '' && attributes.view_id !== 'Select a View' && attributes.entry_id !== '' && attributes.field_id !== '' ) &&
					<ServerSideRender
						block={ blockName }
						attributes={ attributes }
					/>
				}

			</Fragment>,

		];
	},
	save() {
		// Rendering in PHP
		return null;
	},
} );

