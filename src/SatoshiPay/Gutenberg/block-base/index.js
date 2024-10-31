/**
* BLOCK: block-name
*/

import './style.scss';
import './editor.scss';

const { registerBlockType } = wp.blocks
const {
	TextControl, CheckboxControl, SelectControl, Button,
	SVG, Path, G, Rect
} = wp.components
const { withState } = wp.compose
const { MediaUpload, MediaUploadCheck, MediaPlaceholder, MediaPlaceholderInnerBlocks } = wp.editor
const { Fragment } = wp.element
const { select, dispatch } = wp.data
const { __ } = wp.i18n

const editorData = select('core/editor')
const editorDispatch = dispatch('core/editor')

import {
	If, PayButton, SatoshiResizableBox,
	SvgIcon, CheckIfBelowPaywall, Notice
} from '../helpers'

import {
	jsonToFormData, makeAjaxRequest,
	getSvgSolidColor, limitString
} from '../../Utils'

/**
* Register: a Gutenberg Block.
*
* Registers a new block provided a unique name and an object defining its
* behavior. Once registered, the block is made editor as an option to any
* editor interface where blocks are implemented.
*
* @link https://wordpress.org/gutenberg/handbook/block-api/
* @param  {string}   name     Block name.
* @param  {Object}   settings Block settings.
* @return {?WPBlock}          The block, if it has been successfully
*                             registered; otherwise `undefined`.
*/
registerBlockType( 'satoshipay/block-block-name', {
	// Block name. Block names must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.
	title: __( 'Block Name' ), // Block title.
	icon: 'money', // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: 'satoshipay', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	attributes: {
		numberAttr: {
			type: 'number'
		},
		stringAttr: {
			type: 'string'
		},
		boolAttr: {
			type: 'boolean',
			default: false
		},
		arrayAttr: {
			type: 'array'
		}
	},
	keywords: [
		__( 'article — satoshiPay block' ),
		__( 'satoshiPay' ),
		__( 'paywall' ),
	],
	edit( { className, attributes, setAttributes, isSelected, toggleSelection, clientId } ) {
		return (
			<div className={ `spgb ${className}` }>
				Editor View
			</div>
		);
	},
	save( { attributes } ) {
		return (
			<div>
				Consumer View
			</div>
		);
	},
} );
