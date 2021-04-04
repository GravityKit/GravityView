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
const blockName = 'gv-blocks/view-details';

export default registerBlockType( blockName, {
	category: 'gravityview',
	title: __( 'GravityView View Details', 'gravityview' ),
	icon,
	keywords: [ 'gv', __( 'GravityView View Details', 'gravityview' ) ],
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
				label: __( 'Select a View', 'gravityview' ),
			},
			...GV_BLOCKS.view_list,
		];

		return (
			<Fragment>
				<Inspector { ...{ setAttributes, ...props } } />
				<Fragment>
					{
						( !attributes.preview || attributes.id === '' || attributes.id === 'Select a View' ) &&
						<div className="gravityview-shortcode-preview">
							<img src={ `${ GV_BLOCKS.img_url }gv-logo.png` } alt={ __( 'GravityView', 'gravityview' ) }/>
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
							block={ blockName }
							attributes={ attributes }
						/>
					}
				</Fragment>
			</Fragment>
		);
	},
	save() {
		// Rendering in PHP
		return null;
	},
} );

