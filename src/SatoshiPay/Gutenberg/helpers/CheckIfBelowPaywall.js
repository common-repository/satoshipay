const { select, dispatch } = wp.data
const editorData = select('core/editor')
const editorDispatch = dispatch('core/editor')

import { Notice } from './'

export default ({ clientId }) => {
    // get the blocks in the current post
	const blocksList = editorData.getBlocks()

    // get the current block index using the passed clientId
	const currentBlockIndex = editorData.getBlockIndex(clientId)

    // get the current block name using passed clientId - used to define error text
	const currentBlockName = blocksList.find(block => block.clientId === clientId).name

    // get all blocks above the current block
    const blocksAboveCurrentBlock = blocksList.slice(0, currentBlockIndex)

	// look for a paywall block above the current block and return the index
	const paywallAboveCurrentBlockIndex = blocksAboveCurrentBlock.findIndex(({ name }) => name === 'satoshipay/block-article-paywall')

	// get the rootClientId - used for moving the block position
	const rootClientId = editorData.getBlockRootClientId(clientId)

	const moveBlockAbovePaywall = () => {
		// Move the current block above the paywall
		editorDispatch.moveBlockToPosition(clientId, rootClientId, rootClientId, paywallAboveCurrentBlockIndex)
		editorDispatch.updateBlockAttributes(clientId, { forceUpdateDummy: Math.random() })
	}

	const buttonContainerStyle = {
		display: 'flex',
		justifyContent: 'space-between',
		alignItems: 'center',
	}

	const moveButtonStyle = {
		background: 'none',
		border: 'none',
		fontSize: '14px',
		lineHeight: '14px',
		padding: '3px 0',
		cursor: 'pointer',
		color: '#D05D64',
		outline: 'none',
		height: '24px',
		fontWeight: 'bold',
	}

	const moveButtonIconStyle = {
		display: 'inline-block',
		fontSize: '14px',
		lineHeight: '16px',
		verticalAlign: 'middle',
	}

	return (
		<div>
			{
				paywallAboveCurrentBlockIndex >= 0
				? (
                    <Notice status="error">
						<div style={buttonContainerStyle}>
							<span>This block is below another paywall.</span>
							<button style={moveButtonStyle} onClick={moveBlockAbovePaywall}>Move out <span class="dashicons dashicons-arrow-up-alt2" style={moveButtonIconStyle}></span></button>
						</div>
                    </Notice>
                )
				: null
			}
		</div>
	)
}
