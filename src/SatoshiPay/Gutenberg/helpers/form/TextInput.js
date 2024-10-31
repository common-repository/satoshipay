import './TextInput.scss'

export default ({
    type = 'text',
    label,
    placeholder = '',
    affix = '',
    id = `sp_textInput_${Math.random()}`,
    onChange = f => f,
    value,
    size = 'small',
    ...rest
}) => (
    <div className={`sp-textInput ${size}`}>
        {
            label &&
            <label
                htmlFor={id}
                className="sp-textInput__label">{ label }</label>
        }
        <input
            type={ type }
            placeholder={ placeholder }
            id={ id }
            className="sp-textInput__input"
            onChange={ e => onChange(e.target.value) }
            value={ value }
            { ...rest }
        />
        {
            affix &&
            <span className="sp-textInput__affix">{ affix }</span>
        }
    </div>
)
