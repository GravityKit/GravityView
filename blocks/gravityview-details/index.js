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
export default registerBlockType( 'gravityview/gravityview-details', {
	category: 'gravityview',
	title: __( 'GravityView View Details', 'gv-gutenberg' ),
	icon,
	keywords: [ 'gv', __( 'GravityView View Details', 'gv-gutenberg' ) ],
	attributes,
	transforms: {
		from: [
			{
				type: 'shortcode',
				tag: [ 'gravityview' ],
				attributes: {
					id: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.id;
						},
					},
					detail: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.detail;
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
					( ! attributes.preview || attributes.id === '' || attributes.id === 'Select a View' ) &&
					<div className="gravity-view-shortcode-preview">
						<img src={ `${ GV_GUTENBERG.img_url }logo.png` } alt={ __( 'GravityView', 'gv-gutenberg' ) } />
						<div className="field-container">
							<SelectControl
								value={ attributes.id }
								options={ viewLists }
								onChange={ id => {
									setAttributes( {
										id,
									} );
								} }
							/>
						</div>
					</div>
				}
				{
					( attributes.preview && attributes.id !== '' && attributes.id !== 'Select a View' ) &&
					<ServerSideRender
						block="gravityview/gravityview-details"
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

