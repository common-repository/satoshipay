import './Select.scss'
import If from '../If'

export default ({
    label,
    placeholder = '',
    id = `sp_select_${Math.random()}`,
    options = [],
    onSelect = f => f,
    value,
    size = 'small'
}) => (
    <div className={ `sp-select ${size}` }>
        {
            label &&
            <label
                htmlFor={ id }
                className="sp-select__label">
                { label }
            </label>
        }
        <select
            className="sp-select__input"
            id={ id }
            value={ value }
            onChange={ e => onSelect(e.target.value) }>
            <If condition={ !!placeholder }>
                <option
                    value={ null }
                    disabled
                    hidden
                    selected={!value ? 'selected' : ''}>
                    { placeholder }
                </option>
            </If>
            {
                options.map(option => (
                    <option
                        value={ option.value }>
                        { option.label }
                    </option>
                ))
            }
        </select>
    </div>
)
