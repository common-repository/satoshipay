// Force refresh all blocks in the current post by updating a dummy attribute
export default () => {
	wp.data.select('core/editor')
	.getBlocks()
	.filter(({name}, index) => {
		// Only refresh satoshipay blocks, and ignore paywall to avoid infinite loop
		return name.startsWith('satoshipay/') && name !== 'satoshipay/block-article-paywall'
	})
	.forEach(({clientId: id}) => {
		// Set dummy attribute with random number to force update
		wp.data.dispatch('core/editor').updateBlockAttributes(id, {forceUpdateDummy: Math.random()})
	})
}
