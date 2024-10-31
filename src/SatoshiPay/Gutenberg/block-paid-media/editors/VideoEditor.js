const { Fragment } = wp.element
import { VideoEditorFocused, VideoEditorUnfocused } from '../edit-views'
import { If, CheckIfBelowPaywall } from '../../helpers'

// Paid video editor
export default ( props ) => {
	const { isSelected } = props

	return (
		<Fragment>
			{/* Check if this block is below a paywall */}
			<CheckIfBelowPaywall { ...props } />

			{/* Block is selected (focused) */}
			<If condition={isSelected}>
				<VideoEditorFocused { ...props } />
			</If>

			{/* Block is not selected (unfocused) */}
			<If condition={!isSelected}>
				<VideoEditorUnfocused { ...props } />
			</If>
		</Fragment>
	)
}
