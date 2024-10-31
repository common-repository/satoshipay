const { Fragment } = wp.element
import { AudioEditorFocused, AudioEditorUnfocused } from '../edit-views'
import { If, CheckIfBelowPaywall } from '../../helpers'

// Paid audio editor
export default ( props ) => {
	const { isSelected } = props

	return (
		<Fragment>
			{/* Check if this block is below a paywall */}
			<CheckIfBelowPaywall { ...props } />

			{/* Block is selected (focused) */}
			<If condition={isSelected}>
				<AudioEditorFocused { ...props } />
			</If>

			{/* Block is not selected (unfocused) */}
			<If condition={!isSelected}>
				<AudioEditorUnfocused { ...props } />
			</If>
		</Fragment>
	)
}
