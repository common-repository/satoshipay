/**
* BLOCK: paywall
*/

const { registerBlockType } = wp.blocks

import config from './_config'
import edit from './_edit'
import save from './_save'

import './editor.scss';
import './style.scss';

/**
* Register: Article Paywall Gutenberg Block.
*
* @param  {string}   name     Block name.
* @param  {Object}   settings Block settings.
* @return {?WPBlock}          The block, if it has been successfully
*                             registered; otherwise `undefined`.
*/
registerBlockType( 'satoshipay/block-article-paywall', {
	...config,
	edit,
	save,
} );
