const { Fragment } = wp.element
import { ImageEditorFocused, ImageEditorUnfocused } from '../edit-views'
import { If, CheckIfBelowPaywall } from '../../helpers'

// Paid image editor
export default ( props ) => {
	const { isSelected } = props

	return (
		<Fragment>
			{/* Check if this block is below a paywall */}
			<CheckIfBelowPaywall { ...props } />

			{/* Block is selected (focused) */}
			<If condition={isSelected}>
				<ImageEditorFocused { ...props } />
			</If>

			{/* Block is not selected (unfocused) */}
			<If condition={!isSelected}>
				<ImageEditorUnfocused { ...props } />
			</If>
		</Fragment>
	)
}
