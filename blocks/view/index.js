import attributes from './config';
import Inspector from './inspector';
import icon from 'AssetSources/js/icon';
import SelectViewItem from 'AssetSources/js/view-selector';

const { registerBlockType } = wp.blocks;
const { Fragment } = wp.element;
const { ServerSideRender } = wp.components;
const { __ } = wp.i18n;

/**
 * Register block
 */
const blockName = 'gv-blocks/view';
export default registerBlockType( blockName, {
	category: 'gravityview',
	title: __( 'GravityView View', 'gv-blocks' ),
	icon,
	keywords: [ 'gv', __( 'GravityView', 'gv-blocks' ) ],
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
					page_size: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.page_size;
						},
					},
					sort_field: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.sort_field;
						},
					},
					sort_direction: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.sort_direction;
						},
					},
					search_field: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.search_field;
						},
					},
					search_value: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.search_value;
						},
					},
					search_operator: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.search_operator;
						},
					},
					start_date: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.start_date;
						},
					},
					end_date: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.end_date;
						},
					},
					class: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.class;
						},
					},
					offset: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.offset;
						},
					},
					single_title: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.single_title;
						},
					},
					back_link_label: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.back_link_label;
						},
					},
					post_id: {
						type: 'string',
						shortcode: ( ref ) => {
							return ref.named.post_id;
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
		return [
			<Inspector { ...{ setAttributes, ...props } } />,
			<Fragment>
				{
					( ! attributes.preview || attributes.id === '' || attributes.id === 'Select a View' ) &&
					<div className="gravityview-shortcode-preview">
						<img src={ `${ GV_BLOCKS.img_url }gv-logo.png` } alt={ __( 'GravityView', 'gv-blocks' ) } />
						<div className="field-container">
							<SelectViewItem  { ...{ setAttributes, ...props } } />
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
			</Fragment>,
		];
	},
	save() {
		// Rendering in PHP
		return null;
	},
} );
