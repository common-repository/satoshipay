const coverPreviewStyle = {
	marginTop: '15px',
	position: 'relative',
}

const coverPreviewHeaderStyle = {
	fontSize: '8px',
	lineHeight: '10px',
	color: 'rgba(86, 93, 102, 0.5)',
	marginBottom: '5px',
	fontWeight: 'bold',
	textTransform: 'uppercase',
}

const solidPayButtonStyle = {
	position: 'absolute',
	left: '10px',
	top: '25px',
	height: '15px',
	width: '35px',
	borderRadius: '3px',
	background: '#35CEFF'
}

// Handle the layout of the media editor preview
export default ({ children, label = 'Preview' }) => (
	<div style={ coverPreviewStyle }>
		<div style={ coverPreviewHeaderStyle }>{ label }</div>
		<div style={ solidPayButtonStyle } />
		{children}
	</div>
)
