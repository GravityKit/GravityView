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
export default registerBlockType( 'gravityview/gventry', {
	category: 'gravityview',
	title: __( 'GravityView Entry', 'gv-gutenberg' ),
	icon,
	keywords: [ 'gv', __( 'GravityView', 'gv-gutenberg' ) ],
	attributes,
	transforms: {
		from: [
			{
				type: 'shortcode',
				tag: [ 'gventry' ],
				attributes: {
					view_id: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.view_id;
						},
					},
					id: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.id;
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
				label: __( 'Select a View', 'gv-gutenberg' ),
			},
			...GV_GUTENBERG.view_list,
		];

		return [
			<Inspector { ...{ setAttributes, ...props } } />,
			<Fragment>
				{
					( ! attributes.preview || attributes.view_id === '' || attributes.view_id === 'Select a View' || attributes.id === '' ) &&
					<div className="gravity-view-shortcode-preview">
						<img src={ `${ GV_GUTENBERG.img_url }logo.png` } alt={ __( 'GravityView', 'gv-gutenberg' ) } />
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
					( attributes.preview && attributes.view_id !== '' && attributes.view_id !== 'Select a View' && attributes.id !== '' ) &&
					<ServerSideRender
						block="gravityview/gventry"
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

