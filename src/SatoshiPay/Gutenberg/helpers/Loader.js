import SvgIcon from './SvgIcon'

// Display animated loading icon
export default ({ iconWidth = 100, iconHeight = 40, iconColor = '#35CEFF', mode = 'fill' }) => {
    const containerStyle = {
        position: 'absolute',
        top: 0,
        right: 0,
        bottom: 0,
        left: 0,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: 'rgba(255, 255, 255, 0.8)',
        zIndex: 5,
    }
    return (
        <div style={containerStyle}>
            <SvgIcon
                type="loader"
                width={iconWidth}
                height={iconHeight}
                fill={iconColor}
                preserveAspectRatio="none"
            />
        </div>
    )
}
