import React from 'react'
import SvgIcon from './SvgIcon'
const { ResizableBox } = wp.components

const ResizeCursor = () => <SvgIcon type='resize-cursor' size='20px' />

// reusable resizable box component
export default ({ children, setAttributes, size, toggleSelection }) => {

	// Disable block selection (focus) to avoid block being deselected while resizing
	toggleSelection(false)

	return (
		<ResizableBox
			size={ size }
			minHeight="50"
			minWidth="50"
			onResizeStop={ ( event, direction, elt, delta ) => {
				setAttributes( {
					mediaHeight: parseInt( size.height + delta.height, 10 ),
					mediaWidth: parseInt( size.width + delta.width, 10 ),
				} );
			} }
			handleComponent={ {
				bottomRight: ResizeCursor,
			} }
			enable={ {
				bottomRight: true
			} }
			lockAspectRatio
			>
				{children}
			</ResizableBox>
		)
	}
