import './Button.scss'

const getButtonClassname = ({ isSolid }) => {
    if ( isSolid ) return 'solid'
    return 'default'
}

export default ({
    value,
    children,
    className = '',
    style = {},
    isDefault = false,
    isSolid = false,
    onClick = f => f
}) => (
    <button
        className={ `sp-button ${getButtonClassname({ isSolid })}` }
        style={ style }
        onClick={ onClick }>
        { value || children }
    </button>
)
