import SvgIcon from './SvgIcon'

const ContainerStyle = style => ({
	position: 'relative',
	minHeight: '62px',
	...style
})

const PayButtonStyle = style => ({
	lineHeight: '40px',
	borderRadius: '5px',
    padding: '0 20px',
    color: '#fff',
	backgroundColor: '#35CEFF',
    fontWeight: 'bold',
    cursor: 'pointer',
	outline: 'none',
	zIndex: '10',
    ...style
})

const PayButtonIconStyle = {
    fill: 'rgba(255, 255, 255, 0.8)',
    marginRight: '5px',
    verticalAlign: 'middle'
}

const types = {
	paywall: (
		<SvgIcon
			type="eye"
			style={PayButtonIconStyle}
			width="18"
			height="12"
		/>
	),
	image: (
		<SvgIcon
			type="eye"
			style={PayButtonIconStyle}
			width="18"
			height="12"
		/>
	),
	audio: (
		<SvgIcon
			type="audio"
			style={PayButtonIconStyle}
			size="15"
		/>
	),
	video: (
		<SvgIcon
			type="play"
			style={PayButtonIconStyle}
			size="15"
		/>
	),
	file: (
		<SvgIcon
			type="folder"
			style={PayButtonIconStyle}
			size="15"
		/>
	),
	donation: (
		<span
			className="dashicons dashicons-heart"
			style={PayButtonIconStyle}></span>
	),
}

const blockLetter = <div style={{ display: 'inline-block', height: '18px', width: '10px', background: '#d4e8ec', marginBottom: '2px' }} />
const blockSpace = <div style={{ display: 'inline-block', height: '18px', width: '10px', background: 'transparent', marginBottom: '2px' }} />

const paywallBlockText = [...Array(300)].map((letter, i) => (
	Math.random() > 0.1 || i < 5 ? blockLetter : blockSpace
))

const Button = ({type, price, style}) => (
    <button style={PayButtonStyle(style)}>
        {types[type]} Pay {price || 0}
    </button>
)

export default (props) => (
	<div style={ContainerStyle(props.containerStyle)}>
		<Button {...props} style={{...props.style, position: 'absolute', top: '10px', left: '10px'}} />
		{
			props.type === 'paywall'
			? paywallBlockText
			: props.children
		}
	</div>
)
