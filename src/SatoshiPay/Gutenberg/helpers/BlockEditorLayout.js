const mediaEditorLayoutStyles = {
	display: 'flex',
	marginTop: '35px',
}
const mediaEditorChildStyles = ({ alignRight = false }, isLast) => ({
	marginRight: alignRight && isLast ? '0' : '20px',
	marginLeft: alignRight ? 'auto' : '0',
	display: 'flex',
	alignItems: 'center',
})

// Handle the style of the media editor layout
export default ({ children }) => (
	<div style={mediaEditorLayoutStyles}>
		{
			(children.length ? children : [children]).map((child, index, arr) => (
				<div style={mediaEditorChildStyles(child.props, index + 1 === arr.length)}>
					{child}
				</div>
			))
		}
	</div>
)
