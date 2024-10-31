/**
 * Gutenberg Blocks
 *
 * All blocks related JavaScript files should be imported here.
 * You can create a new block folder in this dir and include code
 * for that block here as well.
 *
 * All blocks should be included here since this is the file that
 * Webpack is compiling as the input file.
 */

// Update satoshipay category icon
// Can't be done via PHP whle creating the category
import { SvgIcon } from './helpers'
const { dispatch, select } = wp.data
const categories = select('core/blocks')
                        .getCategories()
                        .map(category => (
                            category.slug === 'satoshipay'
                            ? {...category, icon: <SvgIcon type="satoshipay" size="20px" />}
                            : category
                        ))
dispatch('core/blocks').setCategories(categories)

// Importing all blocks
import './block-article-paywall'
import './block-paid-media'
import './block-paid-file'
import './block-donation'
