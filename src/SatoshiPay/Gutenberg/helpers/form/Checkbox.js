import './Checkbox.scss'

export default ({
    label,
    id = `sp_select_${Math.random()}`,
    onChange = f => f,
    checked
}) => (
    <div className="sp-checkbox">
        <input
            className="sp-checkbox__input"
            id={ id }
            type="checkbox"
            checked={ checked }
            onChange={ e => onChange(e.target.checked) }
        />
        {
            label &&
            <label
                className="sp-checkbox__label"
                htmlFor={ id }>
                { label }
            </label>
        }
    </div>
)
